<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantUsageDaily;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * meter:tenant-usage
 *
 * Walk every tenant, compute the cheap aggregate snapshot, write one row
 * per tenant per day to `tenant_usage_daily`. Idempotent: re-running on
 * the same date upserts. Designed to run once a day from the scheduler;
 * also runnable on-demand to backfill or refresh.
 *
 * Privacy: every value computed here is an aggregate (count, sum, max).
 * No tenant business data leaves the tenant scope. See
 * app/Support/PlatformAdminVisibility.php for the contract.
 *
 * Cost shape: O(N tenants) cheap COUNT(*) queries + one filesystem scan
 * per tenant. Soft-deleted tenants are skipped. Should run in < 30s for
 * a few hundred tenants on a warm Postgres.
 */
class MeterTenantUsage extends Command
{
    protected $signature = 'meter:tenant-usage
                            {--tenant= : Restrict to a single tenant slug or id (for backfill / debug)}';

    protected $description = 'Snapshot per-tenant aggregate usage (rows, storage, AI 30d) for HQ visibility.';

    public function handle(): int
    {
        $today = now()->toDateString();
        $thirtyDaysAgo = now()->subDays(30);

        $query = Tenant::query();
        if ($filter = $this->option('tenant')) {
            $query->where(function ($q) use ($filter) {
                $q->where('slug', $filter)->orWhere('id', $filter);
            });
        }

        $tenants = $query->get();
        $this->info("Metering {$tenants->count()} tenant(s) for {$today}…");

        $count = 0;
        foreach ($tenants as $tenant) {
            try {
                $snapshot = $this->snapshotFor($tenant, $thirtyDaysAgo);

                TenantUsageDaily::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'usage_date' => $today],
                    $snapshot,
                );
                $count++;
            } catch (\Throwable $e) {
                // Don't let one bad tenant break the whole run.
                Log::error('meter:tenant-usage failed for tenant', [
                    'tenant_id'   => $tenant->id,
                    'tenant_slug' => $tenant->slug,
                    'error'       => $e->getMessage(),
                ]);
                $this->warn("  ! {$tenant->slug}: {$e->getMessage()}");
            }
        }

        $this->info("Done. {$count} snapshot(s) written.");
        return self::SUCCESS;
    }

    /**
     * Build the aggregate snapshot for one tenant. All queries are scoped
     * to the tenant's RLS context so cross-tenant leakage is structurally
     * impossible — even if the meter logic has a bug.
     */
    private function snapshotFor(Tenant $tenant, \Carbon\CarbonInterface $thirtyDaysAgo): array
    {
        return TenantContext::run($tenant, function () use ($tenant, $thirtyDaysAgo) {
            // Counts on small pivot/employee tables.
            $usersCount     = $tenant->users()->count();
            $employeesCount = (int) DB::table('employees')->where('tenant_id', $tenant->id)->count();

            // Total rows across the major tenant-scoped tables. Cheap on
            // Postgres because each is a single COUNT(*) with the
            // tenant_id index. Skips tables that don't exist (some plans
            // never get accounting/payroll tables provisioned).
            $rowCountTotal = $this->countRowsAcrossTenantTables($tenant->id);

            // AI mirror — last 30 days from the per-call log. Sum is the
            // canonical truth; the daily rollup is denormalised here for
            // the HQ overview.
            $ai = DB::table('ai_usage_daily')
                ->where('tenant_id', $tenant->id)
                ->where('usage_date', '>=', $thirtyDaysAgo->toDateString())
                ->selectRaw('
                    COALESCE(SUM(request_count), 0)                            as requests,
                    COALESCE(SUM(input_tokens + output_tokens
                               + cache_read_tokens + cache_write_tokens), 0)   as tokens,
                    COALESCE(SUM(cost_usd), 0)                                 as cost_usd
                ')
                ->first();

            // Storage — best-effort estimate. Sums file sizes in both the
            // public and private disks under the tenant's prefix. Falls
            // back to 0 if the disk doesn't expose a size or the prefix
            // is empty.
            $storageMb = $this->estimateStorageMb($tenant);

            // Last-active heuristic — most recent of (any user login, any
            // AI conversation). Used to spot churn risk in the HQ overview.
            $lastLogin = (int) DB::table('users')
                ->where('tenant_id', $tenant->id)
                ->whereNotNull('last_login_at')
                ->max(DB::raw('EXTRACT(EPOCH FROM last_login_at)'));
            $lastAi = (int) DB::table('ai_conversations')
                ->where('tenant_id', $tenant->id)
                ->max(DB::raw('EXTRACT(EPOCH FROM created_at)'));
            $lastActiveTs = max($lastLogin, $lastAi);
            $lastActiveAt = $lastActiveTs > 0
                ? \Carbon\Carbon::createFromTimestamp($lastActiveTs)
                : null;

            return [
                'users_count'        => $usersCount,
                'employees_count'    => $employeesCount,
                'db_row_count_total' => $rowCountTotal,
                'storage_mb'         => $storageMb,
                // Email volume gap — no email-log table yet. When the
                // delivery log lands, sum it here over the trailing 30d.
                'emails_sent_30d'    => 0,
                'ai_requests_30d'    => (int) $ai->requests,
                'ai_tokens_30d'      => (int) $ai->tokens,
                'ai_cost_usd_30d'    => (float) $ai->cost_usd,
                'last_active_at'     => $lastActiveAt,
            ];
        });
    }

    private function countRowsAcrossTenantTables(int $tenantId): int
    {
        $total = 0;
        foreach (\App\Support\PlatformAdminVisibility::COUNTABLE_TENANT_TABLES as $table) {
            // information_schema check so missing/dropped tables don't
            // explode the meter on an older snapshot.
            if (!Schema::hasTable($table)) continue;

            try {
                $total += (int) DB::table($table)
                    ->where('tenant_id', $tenantId)
                    ->count();
            } catch (\Throwable) {
                // Some tables may not have a tenant_id column (rare).
                // Silently skip; the gap is documented in the visibility
                // file.
            }
        }
        return $total;
    }

    private function estimateStorageMb(Tenant $tenant): int
    {
        $prefix = "tenants/{$tenant->id}/";
        $totalBytes = 0;

        foreach (['local', 'public'] as $disk) {
            try {
                $files = Storage::disk($disk)->allFiles($prefix);
                foreach ($files as $file) {
                    $totalBytes += (int) Storage::disk($disk)->size($file);
                }
            } catch (\Throwable) {
                // Disk not configured / prefix doesn't exist — skip.
            }
        }

        return (int) round($totalBytes / 1_048_576);
    }
}
