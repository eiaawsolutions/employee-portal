<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PurgeCanceledTenants — Phase 2 of the 30/90-day tenant-deletion pipeline.
 *
 * Fires when a tenant has been soft-deleted (deleted_at is set) AND 60+
 * days have passed since Phase 1 scrubbed PII. At this point:
 *   - Personal data has been null-ed (day 30)
 *   - Aggregate shells have been retained for ~60 more days in case of
 *     regulator lookback requests
 *   - NOW we DELETE the rows themselves and the tenant record
 *
 * Default window: 90 days total from cancellation = 30 (grace) + 60 (scrub
 * retention). Override per env: `PURGE_CANCELED_AFTER_DAYS`.
 *
 * Execution:
 *   - Delete rows in every tenant-scoped table via TenantContext (RLS-safe)
 *   - Delete the tenant row itself (forceDelete past SoftDeletes)
 *   - Log the event for audit-trail purposes (the audit log itself is a
 *     tenant-scoped table, so we log BEFORE we purge it)
 *
 * This command is destructive. --dry-run reports without writing.
 * Production runs are locked behind a confirm prompt unless --force.
 */
class PurgeCanceledTenants extends Command
{
    protected $signature = 'billing:purge-canceled
        {--dry-run : Report what would be purged without writing}
        {--force : Skip the destructive-operation confirm prompt}
        {--purge-after-days=90 : Days after cancellation before hard purge (default 90 = 30 scrub + 60 shell retention)}';

    protected $description = 'Hard-delete rows for tenants past the scrub-retention window.';

    public function handle(): int
    {
        $purgeAfter = (int) $this->option('purge-after-days');
        $cutoff = Carbon::now()->subDays($purgeAfter);
        $dryRun = (bool) $this->option('dry-run');

        $this->info(($dryRun ? '[dry-run] ' : '') . "Deletion Phase 2 · canceled_at ≤ {$cutoff->toDateTimeString()} ({$purgeAfter}d total)");

        $candidates = Tenant::onlyTrashed()
            ->where('status', Tenant::STATUS_CANCELED)
            ->whereNotNull('canceled_at')
            ->where('canceled_at', '<=', $cutoff)
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No soft-deleted canceled tenants past the purge window.');
            return self::SUCCESS;
        }

        if (!$dryRun && !$this->option('force')) {
            if (!$this->confirm("About to HARD DELETE {$candidates->count()} tenants + all their rows. Proceed?", false)) {
                $this->warn('Aborted.');
                return self::FAILURE;
            }
        }

        $purged = 0;
        foreach ($candidates as $tenant) {
            $days = (int) $tenant->canceled_at->diffInDays(Carbon::now());
            $this->line("→ [{$tenant->id}] {$tenant->slug} · canceled {$days}d ago · purging");

            if ($dryRun) {
                continue;
            }

            try {
                // Log the purge intent BEFORE we delete the audit log itself.
                // Written to a non-tenant-scoped log channel.
                Log::warning('tenant.hard_purged', [
                    'tenant_id' => $tenant->id,
                    'slug' => $tenant->slug,
                    'canceled_at' => $tenant->canceled_at->toIso8601String(),
                    'purge_after_days' => $purgeAfter,
                ]);

                DB::transaction(function () use ($tenant) {
                    TenantContext::run($tenant, function () use ($tenant) {
                        $this->purgeTenantRows($tenant);
                    });
                    // Finally, purge the tenant itself past the SoftDelete.
                    $tenant->forceDelete();
                });

                $purged++;
            } catch (\Throwable $e) {
                $this->error("    failed: " . $e->getMessage());
                Log::error('tenant.purge_phase2_failed', [
                    'tenant_id' => $tenant->id,
                    'slug' => $tenant->slug,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info(($dryRun ? '[dry-run] Would purge ' : 'Purged ') . "{$purged} / {$candidates->count()} tenants.");
        return self::SUCCESS;
    }

    /**
     * Delete rows in every tenant-scoped table. Postgres RLS inside
     * TenantContext restricts the DELETE to this tenant's rows only.
     *
     * FK ordering matters — delete leaves before parents. For the EIAAW
     * Workforce schema, the safe ordering is: AI + audit first, then HR
     * children (claims/leave/attendance/assets/accounting subsidiaries),
     * then employees + related subject tables, then users.
     *
     * Since RLS + cascade FKs handle most of this automatically at the DB
     * level, we can delete in broad strokes and trust CASCADE to follow.
     */
    private function purgeTenantRows(Tenant $tenant): void
    {
        // Non-PII-bearing observability tables first (already scrubbed in Phase 1).
        foreach ([
            'ai_conversations',
            'ai_usage_daily',
            'security_audit_logs',
        ] as $table) {
            if (\Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        // HR / Finance module data — each module's FK chain cascades.
        foreach ([
            'expense_claims',
            'leave_applications',
            'attendance_records',
            'asset_assignments',
            'aarfs',
            'journal_entries',
            'customers',
            'vendors',
        ] as $table) {
            if (\Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        // Subject tables that hang off employees.
        foreach ([
            'personal_details',
            'work_details',
            'employee_education_histories',
            'employee_spouse_details',
            'employee_emergency_contacts',
            'employee_child_registrations',
            'employee_contracts',
            'employee_histories',
            'onboarding_invites',
            'onboardings',
            'offboardings',
        ] as $table) {
            if (\Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        // Employees + users last.
        if (\Schema::hasTable('employees')) {
            DB::table('employees')->delete();
        }
        if (\Schema::hasTable('tenant_users')) {
            DB::table('tenant_users')->delete();
        }
        if (\Schema::hasTable('users')) {
            DB::table('users')->delete();
        }

        // Subscriptions / Cashier rows belonging to this tenant.
        foreach (['subscriptions', 'subscription_items'] as $table) {
            if (\Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }
}
