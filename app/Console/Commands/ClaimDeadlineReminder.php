<?php

namespace App\Console\Commands;

use App\Mail\ClaimReminderMail;
use App\Models\ExpenseClaim;
use App\Models\ExpenseClaimPolicy;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ClaimDeadlineReminder extends Command
{
    protected $signature = 'claims:remind';
    protected $description = 'Send claim submission deadline reminders — iterates every active tenant.';

    public function handle(): int
    {
        $totalSent = 0;

        TenantContext::forEach(function (Tenant $tenant) use (&$totalSent) {
            $sent = $this->processTenant($tenant);
            if ($sent > 0) {
                $this->line("→ Tenant [{$tenant->id}] {$tenant->slug} — {$sent} reminder(s) sent");
            }
            $totalSent += $sent;
        });

        $this->info("Done. Total reminders sent: {$totalSent}");
        return self::SUCCESS;
    }

    private function processTenant(Tenant $tenant): int
    {
        $policy = ExpenseClaimPolicy::forCompany();
        $deadlineDay = $policy->submission_deadline_day ?? 20;
        $reminderDays = $policy->reminder_days_before ?? 3;

        $now = now();
        $deadlineDate = $now->copy()->setDay(min($deadlineDay, $now->daysInMonth));
        $reminderDate = $deadlineDate->copy()->subDays($reminderDays);

        // Only send within the configured reminder window
        if ($now->lt($reminderDate) || $now->gt($deadlineDate)) {
            return 0;
        }

        $draftClaims = ExpenseClaim::where('year', $now->year)
            ->where('month', $now->month)
            ->where('status', 'draft')
            ->where('item_count', '>', 0)
            ->with('employee.user')
            ->get();

        $sent = 0;
        foreach ($draftClaims as $claim) {
            $employee = $claim->employee;
            $email = $employee?->user?->work_email ?? $employee?->user?->email ?? null;

            if (!$email) {
                continue;
            }

            Mail::to($email)->queue(new ClaimReminderMail($claim, $deadlineDate));
            $sent++;
        }

        return $sent;
    }
}
