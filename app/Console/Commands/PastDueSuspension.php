<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * PastDueSuspension — suspends tenants whose payment has been past_due for
 * longer than the grace period (default 3 days). Runs daily at 02:30.
 *
 * Trigger: StripeWebhookController sets subscription_status=past_due AND
 * records past_due_at on `invoice.payment_failed`. On success the same
 * webhook clears past_due_at. This command fires when past_due_at is
 * older than the grace window AND the tenant is still active.
 *
 * Grace default: 3 days. Override per env: `PAST_DUE_GRACE_DAYS`.
 *
 * Effect:
 *   - status → suspended
 *   - suspended_at set to now()
 *   - suspension_reason set to human-readable string
 *   - (ResolveTenant middleware returns 402 on suspended tenants)
 *
 * Idempotent: re-running is safe; tenants already in STATUS_SUSPENDED are skipped.
 */
class PastDueSuspension extends Command
{
    protected $signature   = 'billing:past-due-suspend {--dry-run : Report what would change without writing}';
    protected $description = 'Suspend tenants whose payment has been past_due for longer than the grace period.';

    public function handle(): int
    {
        $graceDays = (int) env('PAST_DUE_GRACE_DAYS', 3);
        $cutoff = Carbon::now()->subDays($graceDays);
        $dryRun = (bool) $this->option('dry-run');

        $this->info(($dryRun ? '[dry-run] ' : '') . "Scanning past_due tenants older than {$graceDays}d (cutoff: {$cutoff->toDateTimeString()})");

        $candidates = Tenant::query()
            ->where('subscription_status', 'past_due')
            ->whereNotNull('past_due_at')
            ->where('past_due_at', '<=', $cutoff)
            ->where('status', Tenant::STATUS_ACTIVE)
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No tenants exceed the past_due grace period.');
            return self::SUCCESS;
        }

        $suspended = 0;
        foreach ($candidates as $tenant) {
            $daysPastDue = (int) $tenant->past_due_at->diffInDays(Carbon::now());
            $this->line("→ [{$tenant->id}] {$tenant->slug} · past_due for {$daysPastDue}d");

            if ($dryRun) {
                continue;
            }

            $tenant->update([
                'status' => Tenant::STATUS_SUSPENDED,
                'suspended_at' => Carbon::now(),
                'suspension_reason' => "Payment past_due for {$daysPastDue} days (grace window: {$graceDays}d).",
            ]);

            Log::warning('tenant suspended for non-payment', [
                'tenant_id' => $tenant->id,
                'slug' => $tenant->slug,
                'past_due_since' => $tenant->past_due_at->toIso8601String(),
                'days_past_due' => $daysPastDue,
                'grace_days' => $graceDays,
            ]);

            $suspended++;
        }

        $this->info(($dryRun ? '[dry-run] Would suspend ' : 'Suspended ') . "{$suspended} / {$candidates->count()} tenants.");
        return self::SUCCESS;
    }
}
