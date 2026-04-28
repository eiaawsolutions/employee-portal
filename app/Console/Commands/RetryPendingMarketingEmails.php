<?php

namespace App\Console\Commands;

use App\Services\MarketingLeadNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sweep marketing_contacts for rows where the sales notification email
 * never landed and retry — with a hard cap of 5 attempts per row.
 *
 * Scheduling: every 10 minutes (see App\Console\Kernel).
 *
 * Why: every row is a paying-customer lead. SMTP can blip (Gmail throttle,
 * DNS, network). Without this sweep a single transient failure means a
 * silent lost lead. With this sweep, the worst case is a ~10-minute delay
 * before sales@ sees the row.
 */
class RetryPendingMarketingEmails extends Command
{
    protected $signature   = 'marketing:retry-pending-emails {--limit=50}';
    protected $description = 'Retry sales notification emails for marketing_contacts rows where delivery failed.';

    public function handle(MarketingLeadNotifier $notifier): int
    {
        $limit = (int) $this->option('limit');

        $rows = DB::table('marketing_contacts')
            ->whereNull('emailed_at')
            ->where('email_attempts', '<', MarketingLeadNotifier::MAX_ATTEMPTS)
            ->where('created_at', '>=', now()->subDay()) // only retry the last 24h; older = human triage
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        if ($rows->isEmpty()) {
            $this->info('Nothing to retry.');
            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;
        foreach ($rows as $id) {
            if ($notifier->send($id)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        $this->info("Retried {$rows->count()} rows — sent: {$sent}, still failing: {$failed}");

        // Stalled rows = at MAX_ATTEMPTS but never sent. These need human triage.
        $stalled = DB::table('marketing_contacts')
            ->whereNull('emailed_at')
            ->where('email_attempts', '>=', MarketingLeadNotifier::MAX_ATTEMPTS)
            ->count();

        if ($stalled > 0) {
            // CRITICAL severity — appears prominently in logs/Sentry. The
            // operator MUST act on these manually (rows are saved in the
            // DB and viewable via SQL or an admin dashboard).
            Log::critical('marketing_contacts.stalled_emails', [
                'stalled_count' => $stalled,
                'recommendation' => 'Inspect the marketing_contacts table for rows where emailed_at IS NULL AND email_attempts >= 5. Forward to sales@ manually.',
            ]);
            $this->warn("⚠ {$stalled} marketing_contacts rows have hit MAX_ATTEMPTS and need manual triage.");
        }

        return self::SUCCESS;
    }
}
