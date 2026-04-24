<?php

namespace App\Console\Commands;

use App\Mail\SecurityAuditMail;
use App\Models\SecurityAuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SecurityAuditReport extends Command
{
    protected $signature   = 'security:audit-report';
    protected $description = 'Email IT teams a digest of security events from the last hour — iterates every active tenant.';

    public function handle(): void
    {
        $since = now()->subHour();
        $totalEvents = 0;

        TenantContext::forEach(function (Tenant $tenant) use ($since, &$totalEvents) {
            $count = $this->processTenant($tenant, $since);
            if ($count > 0) {
                $this->line("→ Tenant [{$tenant->id}] {$tenant->slug} — {$count} event(s) reported");
            }
            $totalEvents += $count;
        });

        if ($totalEvents > 0) {
            $this->info("Security audit reports sent across all tenants — {$totalEvents} event(s) total.");
        }
    }

    private function processTenant(Tenant $tenant, \Carbon\Carbon|\DateTimeInterface $since): int
    {
        $events = SecurityAuditLog::where('emailed', false)
            ->where('created_at', '>=', $since)
            ->orderBy('created_at')
            ->get();

        if ($events->isEmpty()) {
            return 0;
        }

        $recipients = User::whereIn('role', ['it_manager', 'it_executive', 'it_intern'])
            ->where('is_active', true)
            ->pluck('work_email')
            ->filter()
            ->unique()
            ->values();

        if ($recipients->isEmpty()) {
            Log::warning("SecurityAuditReport: tenant {$tenant->id} has security events but no active IT staff to notify.");
            return 0;
        }

        $periodLabel = $since->setTimezone('Asia/Kuala_Lumpur')->format('d/m/Y H:i')
            . ' – ' . now()->setTimezone('Asia/Kuala_Lumpur')->format('H:i') . ' MYT';

        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(new SecurityAuditMail($events, $periodLabel));
            } catch (\Throwable $e) {
                Log::warning("SecurityAuditReport tenant {$tenant->id}: failed to send to {$email}: " . $e->getMessage());
            }
        }

        SecurityAuditLog::whereIn('id', $events->pluck('id'))->update(['emailed' => true]);

        return $events->count();
    }
}
