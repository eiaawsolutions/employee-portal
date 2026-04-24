<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * TenantContext — utility for running code within a specific tenant's context
 * outside the HTTP request lifecycle (where ResolveTenant middleware does it).
 *
 * Used by:
 *  - Scheduled commands that need to iterate across all active tenants
 *  - Queue jobs that need to operate on a specific tenant's data
 *  - Tests that need to set up data for two tenants and switch between them
 *  - Webhook handlers (Stripe, OAuth callbacks) where the tenant is identified
 *    by something other than the subdomain
 *
 * The context is bound into app('current_tenant') AND the Postgres session
 * variable app.tenant_id is set, so both the application-layer TenantScope
 * AND Postgres RLS policies activate.
 *
 * Usage:
 *
 *   TenantContext::run($tenant, function () {
 *       Employee::all();   // scoped to $tenant
 *   });
 *
 *   TenantContext::forEach(function (Tenant $t) {
 *       Employee::whereDate('start_date', today())->each(...);
 *   });
 *
 *   TenantContext::asNone(function () {
 *       // Cross-tenant work — e.g. platform admin reports.
 *       // Requires withoutGlobalScope on every model query.
 *   });
 */
class TenantContext
{
    /**
     * Run a callback inside the context of the given tenant.
     * Restores the previous context (or none) when done, even on exception.
     */
    public static function run(Tenant $tenant, callable $callback): mixed
    {
        $previous = app()->bound('current_tenant') ? app('current_tenant') : null;

        app()->instance('current_tenant', $tenant);
        self::setPostgresVar($tenant->id);

        try {
            return $callback();
        } finally {
            if ($previous) {
                app()->instance('current_tenant', $previous);
                self::setPostgresVar($previous->id);
            } else {
                app()->forgetInstance('current_tenant');
                self::setPostgresVar(null);
            }
        }
    }

    /**
     * Iterate every active, non-suspended tenant. The callback receives the
     * current Tenant and runs inside that tenant's context.
     *
     * Skips:
     *  - suspended tenants (subscription past_due → suspended)
     *  - canceled tenants (no longer paying)
     *  - soft-deleted tenants
     *
     * Use this for scheduled commands like employees:activate that need to run
     * the same logic for every paying tenant once per tick.
     */
    public static function forEach(callable $callback): void
    {
        Tenant::query()
            ->where('status', Tenant::STATUS_ACTIVE)
            ->orderBy('id')
            ->each(function (Tenant $tenant) use ($callback) {
                self::run($tenant, fn () => $callback($tenant));
            });
    }

    /**
     * Run a callback with NO tenant context — used by platform-admin code that
     * intentionally crosses tenants (e.g. billing reports). Calling code MUST
     * use Model::withoutGlobalScope(TenantScope::class) on every Eloquent
     * query inside; otherwise queries return zero rows because the global
     * scope finds no current_tenant binding.
     *
     * Postgres RLS will also fail-closed (eiaaw_current_tenant_id() returns 0)
     * unless the calling code uses a connection role that BYPASSES RLS, OR
     * sets app.tenant_id to a sentinel value that policies allow. Plan to
     * design that explicitly when the platform admin tooling lands.
     */
    public static function asNone(callable $callback): mixed
    {
        $previous = app()->bound('current_tenant') ? app('current_tenant') : null;

        app()->forgetInstance('current_tenant');
        self::setPostgresVar(null);

        try {
            return $callback();
        } finally {
            if ($previous) {
                app()->instance('current_tenant', $previous);
                self::setPostgresVar($previous->id);
            }
        }
    }

    private static function setPostgresVar(?int $tenantId): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        if ($tenantId === null) {
            DB::statement("SELECT set_config('app.tenant_id', '', false)");
        } else {
            DB::statement("SELECT set_config('app.tenant_id', '" . (int) $tenantId . "', false)");
        }
    }
}
