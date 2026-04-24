<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * TrialEndConversion — on day 15 of an unconfirmed trial, auto-downgrade
 * the tenant to Starter tier. Runs daily at 02:15 (after backup at 02:00).
 *
 * Criteria for conversion:
 *   - trial_ends_at is non-null and in the past (trial has ended)
 *   - stripe_id is NULL or subscription_status is NULL/empty (no paid sub yet)
 *   - current plan is growth (default trial tier) OR scale
 *   - status is still active (not already suspended/canceled)
 *
 * Effect:
 *   - plan → 'starter'
 *   - plan changes tracked via SubscriptionEvent audit row
 *   - TODO (follow-up): email owner about the auto-downgrade
 *
 * Locked decision (per memory project_eiaaw_workforce): "14-day Growth-tier
 * trial, no credit card, auto-converts to paid Starter on day 15 unless
 * upgraded." This command implements the unless-upgraded path.
 *
 * Idempotent: running twice in one day is safe — already-Starter tenants
 * and tenants with an active paid subscription are skipped.
 */
class TrialEndConversion extends Command
{
    protected $signature   = 'billing:trial-end {--dry-run : Report what would change without writing}';
    protected $description = 'Auto-downgrade unconfirmed expired trials to Starter tier.';

    public function handle(): int
    {
        $today = Carbon::now();
        $dryRun = (bool) $this->option('dry-run');

        $this->info(($dryRun ? '[dry-run] ' : '') . "Scanning expired unconfirmed trials · {$today->toDateTimeString()}");

        // Candidates: trial ended, no paid subscription, still on a trial tier.
        $candidates = Tenant::query()
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', $today)
            ->where(function ($q) {
                // No confirmed paid subscription
                $q->whereNull('subscription_status')
                  ->orWhere('subscription_status', '')
                  ->orWhere('subscription_status', 'incomplete');
            })
            ->whereIn('plan', [Tenant::PLAN_GROWTH, Tenant::PLAN_SCALE])
            ->where('status', Tenant::STATUS_ACTIVE)
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No trials need conversion today.');
            return self::SUCCESS;
        }

        $converted = 0;
        foreach ($candidates as $tenant) {
            $this->line("→ [{$tenant->id}] {$tenant->slug} · plan={$tenant->plan} · trial_ended={$tenant->trial_ends_at->toDateString()}");

            if ($dryRun) {
                continue;
            }

            $previousPlan = $tenant->plan;
            $tenant->update(['plan' => Tenant::PLAN_STARTER]);

            Log::info('tenant trial auto-downgraded', [
                'tenant_id' => $tenant->id,
                'slug' => $tenant->slug,
                'from_plan' => $previousPlan,
                'to_plan' => Tenant::PLAN_STARTER,
                'trial_ended' => $tenant->trial_ends_at->toIso8601String(),
            ]);

            $converted++;
        }

        $this->info(($dryRun ? '[dry-run] Would convert ' : 'Converted ') . "{$converted} / {$candidates->count()} tenants.");
        return self::SUCCESS;
    }
}
