<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * tenancy:check-rls — fails (exit 1) if any of the SaaS isolation invariants
 * are broken on the live database. Run as part of the deploy pipeline so a
 * misconfigured Postgres role can't silently bypass RLS in production.
 *
 * Checks:
 *   1. DB driver is pgsql (RLS doesn't exist on MySQL)
 *   2. Connected role does NOT have rolbypassrls = true
 *   3. eiaaw_current_tenant_id() function exists
 *   4. Every retrofitted table has rowsecurity = true AND forcerowsecurity = true
 *   5. Every retrofitted table has a USING tenant_id policy
 *
 * Exit 0 if all checks pass; exit 1 with a summary if any fail.
 */
class CheckTenancyRls extends Command
{
    protected $signature   = 'tenancy:check-rls';
    protected $description = 'Fail if any SaaS isolation invariant is broken (run in CI/deploy pipeline).';

    private array $fails = [];
    private array $passes = [];

    public function handle(): int
    {
        $driver = DB::connection()->getDriverName();
        if ($driver !== 'pgsql') {
            $this->error("FAIL: connected to {$driver}, expected pgsql.");
            $this->error("If this is the legacy Claritas MySQL deployment, this command is not applicable.");
            return 1;
        }
        $this->passes[] = 'DB driver is pgsql';

        $this->checkBypassRls();
        $this->checkHelperFunctionExists();
        $this->checkTablesHaveRls();
        $this->checkTablesHavePolicies();

        $this->newLine();
        foreach ($this->passes as $p) {
            $this->line("  <fg=green>✓</> {$p}");
        }
        foreach ($this->fails as $f) {
            $this->line("  <fg=red>✗</> {$f}");
        }
        $this->newLine();
        $this->info(count($this->passes) . ' passed, ' . count($this->fails) . ' failed.');

        return count($this->fails) === 0 ? 0 : 1;
    }

    private function checkBypassRls(): void
    {
        $row = DB::selectOne("
            SELECT rolname, rolbypassrls, rolsuper
            FROM pg_roles WHERE rolname = current_user
        ");

        if (!$row) {
            $this->fails[] = "Could not read current role from pg_roles.";
            return;
        }

        if ($row->rolbypassrls) {
            $this->fails[] = "Current role '{$row->rolname}' has BYPASSRLS = true. RLS will be silently ignored. "
                . "Either revoke the BYPASSRLS attribute (ALTER ROLE \"{$row->rolname}\" NOBYPASSRLS) or connect "
                . "as a different non-superuser application role.";
        } else {
            $this->passes[] = "Current role '{$row->rolname}' does not bypass RLS";
        }

        if ($row->rolsuper) {
            $this->fails[] = "Current role '{$row->rolname}' is a superuser. Postgres superusers bypass RLS in addition "
                . "to the BYPASSRLS attribute. Connect as a non-superuser application role in production.";
        } else {
            $this->passes[] = "Current role '{$row->rolname}' is not a superuser";
        }
    }

    private function checkHelperFunctionExists(): void
    {
        $exists = DB::selectOne("
            SELECT 1 AS yes FROM pg_proc WHERE proname = 'eiaaw_current_tenant_id'
        ");

        if ($exists) {
            $this->passes[] = 'eiaaw_current_tenant_id() helper function exists';
        } else {
            $this->fails[] = 'eiaaw_current_tenant_id() helper function MISSING — RLS policies will fail. '
                . 'Run: php artisan migrate (the function is installed by '
                . '2026_05_02_000001_tenancy_users_and_helpers).';
        }
    }

    /**
     * Tables with a tenant_id column that are intentionally NOT RLS-protected.
     * Document any addition here with rationale.
     *
     *   subscription_events — Stripe webhook log; webhooks arrive without
     *     tenant context, so the handler must write before tenant resolution.
     *     Cross-tenant access is gated at the controller layer.
     *
     *   tenant_usage_daily — platform-admin aggregate snapshot (one row per
     *     tenant per day, aggregates only). Read CROSS-TENANT by the HQ
     *     Overview dashboard (HqOverviewController), which is gated to EIAAW
     *     staff via the EnsurePlatformAdmin middleware. A per-tenant RLS
     *     policy (tenant_id = eiaaw_current_tenant_id()) would break that
     *     cross-tenant read, so isolation for this table is enforced at the
     *     route/controller layer instead. No row-level tenant data is stored
     *     here (see 2026_05_09_000001_create_tenant_usage_daily_table).
     */
    private const INTENTIONALLY_NO_RLS = [
        'subscription_events',
        'tenant_usage_daily',
    ];

    private function checkTablesHaveRls(): void
    {
        $excluded = "'" . implode("','", self::INTENTIONALLY_NO_RLS) . "'";
        $unprotected = DB::select("
            SELECT t.relname
            FROM pg_class t
            JOIN pg_namespace n ON n.oid = t.relnamespace
            WHERE n.nspname = 'public'
              AND t.relkind = 'r'
              AND t.relname NOT IN ({$excluded})
              AND EXISTS (
                  SELECT 1 FROM information_schema.columns c
                  WHERE c.table_schema = 'public' AND c.table_name = t.relname AND c.column_name = 'tenant_id'
              )
              AND (NOT t.relrowsecurity OR NOT t.relforcerowsecurity)
            ORDER BY t.relname
        ");

        if (count($unprotected) === 0) {
            $this->passes[] = 'Every table with a tenant_id column has ENABLE + FORCE ROW LEVEL SECURITY';
        } else {
            $names = array_map(fn ($r) => $r->relname, $unprotected);
            $this->fails[] = 'Tables with tenant_id but RLS disabled: ' . implode(', ', $names);
        }
    }

    private function checkTablesHavePolicies(): void
    {
        $excluded = "'" . implode("','", self::INTENTIONALLY_NO_RLS) . "'";
        $unprotected = DB::select("
            SELECT t.relname
            FROM pg_class t
            JOIN pg_namespace n ON n.oid = t.relnamespace
            WHERE n.nspname = 'public'
              AND t.relkind = 'r'
              AND t.relname NOT IN ({$excluded})
              AND EXISTS (
                  SELECT 1 FROM information_schema.columns c
                  WHERE c.table_schema = 'public' AND c.table_name = t.relname AND c.column_name = 'tenant_id'
              )
              AND NOT EXISTS (
                  SELECT 1 FROM pg_policy p WHERE p.polrelid = t.oid
              )
            ORDER BY t.relname
        ");

        if (count($unprotected) === 0) {
            $this->passes[] = 'Every table with a tenant_id column has at least one RLS policy';
        } else {
            $names = array_map(fn ($r) => $r->relname, $unprotected);
            $this->fails[] = 'Tables with tenant_id but no RLS policy: ' . implode(', ', $names);
        }
    }
}
