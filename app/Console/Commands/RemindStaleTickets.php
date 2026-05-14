<?php

namespace App\Console\Commands;

use App\Mail\TicketReminderMail;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Notifications\TicketReminderNotification;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/**
 * Hourly cron — sends a reminder for any non-archived ticket idle 24+ hours.
 *
 * Recipients:
 *   - PIC if assigned
 *   - All active department managers if not assigned
 *
 * Throttled to one reminder per 24h per ticket via tickets.last_reminder_sent_at.
 *
 * Iterates every active tenant. No unregisteredManagersForNotification path
 * (Claritas-specific staging concept — not in EIAAW's simpler tenant model).
 */
class RemindStaleTickets extends Command
{
    protected $signature = 'tickets:remind-stale';

    protected $description = 'Email + bell-notify the PIC (or department managers) for tickets idle 24+ hours, across all tenants.';

    public function handle(): void
    {
        $totalSent = 0;
        $totalSkipped = 0;
        $totalAutoPended = 0;

        TenantContext::forEach(function (Tenant $tenant) use (&$totalSent, &$totalSkipped, &$totalAutoPended) {
            [$sent, $skipped, $autoPended] = $this->processTenant();
            if ($sent > 0 || $autoPended > 0) {
                $this->line("→ Tenant [{$tenant->id}] {$tenant->slug} — sent: {$sent}, skipped: {$skipped}, auto-pended: {$autoPended}");
            }
            $totalSent += $sent;
            $totalSkipped += $skipped;
            $totalAutoPended += $autoPended;
        });

        $this->info("Done. Sent: {$totalSent}, Skipped: {$totalSkipped}, Auto-Pended: {$totalAutoPended}");
    }

    /** @return array{0:int,1:int,2:int}  [sent, skipped, autoPended] */
    private function processTenant(): array
    {
        $threshold = now()->subHours(24);

        // Latest message timestamp per ticket — used to compute last-activity.
        $latestMessageTimes = TicketMessage::selectRaw('ticket_id, MAX(created_at) as last_msg_at')
            ->groupBy('ticket_id')
            ->pluck('last_msg_at', 'ticket_id');

        $candidates = Ticket::with(['creator', 'assignee'])
            ->whereIn('status', Ticket::ACTIVE_STATUSES)
            ->where(function ($q) use ($threshold) {
                $q->whereNull('last_reminder_sent_at')
                    ->orWhere('last_reminder_sent_at', '<', $threshold);
            })
            ->get();

        $sent = 0;
        $skipped = 0;
        $autoPended = 0;

        foreach ($candidates as $ticket) {
            $lastMsgAt = $latestMessageTimes[$ticket->id] ?? null;
            $lastActivity = $this->latestOf([
                $ticket->updated_at,
                $lastMsgAt ? Carbon::parse($lastMsgAt) : null,
            ]);

            if (! $lastActivity || $lastActivity->gt($threshold)) {
                continue;
            }

            // Auto-transition Open → Pending for un-PIC'd tickets idle 24h+.
            if ($ticket->status === 'Open' && empty($ticket->assigned_to)) {
                $ticket->update(['status' => 'Pending']);
                $ticket->refresh();
                $autoPended++;
            }

            $recipients = $this->resolveRecipients($ticket);
            $isUnassigned = is_null($ticket->assigned_to);

            if ($recipients->isEmpty()) {
                $skipped++;

                continue;
            }

            try {
                foreach ($recipients as $r) {
                    Mail::to($r->work_email)->queue(new TicketReminderMail($ticket, $r, $lastActivity, $isUnassigned));
                }
                Notification::send($recipients, new TicketReminderNotification($ticket, $lastActivity, $isUnassigned));

                $ticket->update(['last_reminder_sent_at' => now()]);
                $sent++;
            } catch (\Exception $e) {
                Log::warning("Ticket reminder failed for {$ticket->ticket_number}: ".$e->getMessage());
                $skipped++;
            }
        }

        return [$sent, $skipped, $autoPended];
    }

    /**
     * PIC if assigned; otherwise all active department managers (no interns).
     */
    private function resolveRecipients(Ticket $ticket)
    {
        if ($ticket->assigned_to) {
            $pic = $ticket->assignee;

            return $pic && $pic->is_active && $pic->work_email ? collect([$pic]) : collect();
        }

        return $ticket->managersForNotification()
            ->whereNotNull('work_email')
            ->get();
    }

    private function latestOf(array $times): ?Carbon
    {
        $valid = collect($times)->filter()->map(fn ($t) => $t instanceof Carbon ? $t : Carbon::parse($t));

        return $valid->isEmpty() ? null : $valid->max();
    }
}
