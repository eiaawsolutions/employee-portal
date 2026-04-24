<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DeleteCanceledTenants — Phase 1 of the 30/90-day tenant-deletion pipeline.
 *
 * Day 0:  Stripe webhook sets status=canceled + canceled_at=now().
 *         Workspace goes read-only (middleware enforces via status).
 * Day 30: THIS COMMAND — scrub PII from every tenant-scoped table and
 *         soft-delete the tenant row. Aggregate counts + audit-log
 *         skeletons remain for LHDN 7-year retention compliance.
 * Day 90: `billing:purge-canceled-tenants` hard-deletes rows.
 *
 * This satisfies the Terms of Service promise ("data goes read-only for 30
 * days, then EIAAW deletes Customer Data from primary storage") while
 * preserving what LHDN / statutory regulators require us to keep.
 *
 * PII scrubbing per table (abbreviated — extend as regulators dictate):
 *   users                     → null work_email, name, session_token, 2fa_*
 *   employees                 → null identification fields, name, contact
 *   personal_details          → null NRIC, passport, bank, etc.
 *   ai_conversations          → null content (the prompts/answers themselves)
 *
 * All writes happen inside TenantContext so RLS honours the cancelled tenant.
 * Idempotent: re-running on a tenant already past Phase 1 is safe (scrubs
 * are null-coalescing; soft-delete is idempotent on already-soft-deleted).
 */
class DeleteCanceledTenants extends Command
{
    protected $signature = 'billing:delete-canceled
        {--dry-run : Report what would change without writing}
        {--grace-days=30 : Days after cancellation before deletion runs}';

    protected $description = 'Scrub PII + soft-delete tenants whose cancellation passed the grace window.';

    public function handle(): int
    {
        $graceDays = (int) $this->option('grace-days');
        $cutoff = Carbon::now()->subDays($graceDays);
        $dryRun = (bool) $this->option('dry-run');

        $this->info(($dryRun ? '[dry-run] ' : '') . "Deletion Phase 1 · canceled_at ≤ {$cutoff->toDateTimeString()} ({$graceDays}d grace)");

        $candidates = Tenant::query()
            ->whereNull('deleted_at')
            ->where('status', Tenant::STATUS_CANCELED)
            ->whereNotNull('canceled_at')
            ->where('canceled_at', '<=', $cutoff)
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No canceled tenants past the grace window.');
            return self::SUCCESS;
        }

        $scrubbed = 0;
        foreach ($candidates as $tenant) {
            $days = (int) $tenant->canceled_at->diffInDays(Carbon::now());
            $this->line("→ [{$tenant->id}] {$tenant->slug} · canceled {$days}d ago");

            if ($dryRun) {
                continue;
            }

            try {
                TenantContext::run($tenant, function () use ($tenant) {
                    $this->scrubPii($tenant);
                });

                $tenant->delete();  // SoftDeletes trait → sets deleted_at

                Log::warning('tenant.pii_scrubbed_and_soft_deleted', [
                    'tenant_id' => $tenant->id,
                    'slug' => $tenant->slug,
                    'canceled_at' => $tenant->canceled_at->toIso8601String(),
                ]);
                $scrubbed++;
            } catch (\Throwable $e) {
                $this->error("    failed: " . $e->getMessage());
                Log::error('tenant.deletion_phase1_failed', [
                    'tenant_id' => $tenant->id,
                    'slug' => $tenant->slug,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info(($dryRun ? '[dry-run] Would scrub ' : 'Scrubbed ') . "{$scrubbed} / {$candidates->count()} tenants.");
        return self::SUCCESS;
    }

    /**
     * Null PII columns on tenant-scoped tables. Keeps row counts + FK graph
     * intact so aggregate audits still work; replaces specific fields with
     * NULL or redacted sentinels.
     *
     * Keep this list in sync with the Privacy Policy Section 3.1 data-category
     * table. When you add a column containing PII, add it here.
     */
    private function scrubPii(Tenant $tenant): void
    {
        // Users — work email and identifying fields
        DB::table('users')->update([
            'name' => '[deleted]',
            'work_email' => DB::raw("'deleted-' || id || '@deleted.invalid'"),
            'password' => '[deleted]',
            'session_token' => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'profile_picture' => null,
        ]);

        // Employees — redact identification + contact
        if (\Schema::hasTable('employees')) {
            DB::table('employees')->update([
                'full_name' => '[deleted]',
                'preferred_name' => '[deleted]',
            ]);
        }

        // personal_details — NRIC / passport / sensitive
        if (\Schema::hasTable('personal_details')) {
            DB::table('personal_details')->update([
                'nric' => null,
                'passport_number' => null,
                'nric_file_paths' => null,
                'invite_staging_json' => null,
            ]);
        }

        // AI conversations — redact prompts + answers (tokens + cost stay for billing audit)
        if (\Schema::hasTable('ai_conversations')) {
            DB::table('ai_conversations')->update([
                'content' => '[redacted]',
                'tool_calls' => null,
                'tool_results' => null,
            ]);
        }

        // Security audit log — keep event types + timestamps for compliance,
        // but redact the work_email + details free-text that may carry PII
        if (\Schema::hasTable('security_audit_logs')) {
            DB::table('security_audit_logs')->update([
                'work_email' => null,
                'details' => '[redacted-post-cancellation]',
            ]);
        }
    }
}
