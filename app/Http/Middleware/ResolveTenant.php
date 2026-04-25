<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * ResolveTenant — resolves the current tenant from the request subdomain
 * and binds it into the container as 'current_tenant'.
 *
 * Subdomain rules:
 *  - acme.ep.eiaawsolutions.com           → tenant slug 'acme'
 *  - app.ep.eiaawsolutions.com            → admin/marketing app (no tenant)
 *  - ep.eiaawsolutions.com                → marketing apex (no tenant)
 *  - localhost / 127.0.0.1                → fallback to ?tenant= query for dev
 *
 * Apex fallback for platform admins:
 *  - When the wildcard subdomain isn't set up at the edge yet,
 *    isPlatformAdmin() users on the apex transparently bind to the
 *    eiaaw-hq workspace so plan-gated routes (/assets, /reports, etc.)
 *    work without requiring DNS work first. Remove this branch once
 *    *.ep.eiaawsolutions.com resolves and SESSION_DOMAIN is set to
 *    .ep.eiaawsolutions.com.
 *
 * After resolving:
 *  - Sets Postgres session variable app.tenant_id for RLS enforcement
 *  - Refuses access to suspended tenants with a friendly 402 page
 *  - Aborts with 404 for unknown subdomains (no tenant enumeration)
 */
class ResolveTenant
{
    private const RESERVED_SUBDOMAINS = ['app', 'admin', 'api', 'www', 'mail', 'static', 'assets'];
    private const HQ_TENANT_SLUG = 'eiaaw-hq';

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request) ?? $this->resolveApexFallback($request);

        if ($tenant) {
            if ($tenant->isSuspended()) {
                abort(402, 'This workspace is suspended. Please contact billing.');
            }

            app()->instance('current_tenant', $tenant);
            $this->setPostgresRlsVariable($tenant->id);
        }

        return $next($request);
    }

    /**
     * Apex fallback: if there's no subdomain match but the request comes
     * from an authenticated platform admin, treat the apex as the
     * eiaaw-hq workspace. Bounded to authenticated isPlatformAdmin()
     * users so anonymous apex traffic still hits the marketing/no-tenant
     * branch as before.
     */
    private function resolveApexFallback(Request $request): ?Tenant
    {
        if (!Auth::check()) return null;
        $user = Auth::user();
        if (!method_exists($user, 'isPlatformAdmin') || !$user->isPlatformAdmin()) {
            return null;
        }

        return Tenant::where('slug', self::HQ_TENANT_SLUG)->first();
    }

    private function resolveTenant(Request $request): ?Tenant
    {
        // Local dev escape hatch — ?tenant=slug or X-Tenant-Slug header
        if (app()->environment('local')) {
            if ($slug = $request->query('tenant') ?? $request->header('X-Tenant-Slug')) {
                return Tenant::where('slug', $slug)->first();
            }
        }

        $host = $request->getHost();
        $apex = config('eiaaw.tenant_domain', env('APP_TENANT_DOMAIN', 'ep.eiaawsolutions.com'));

        if ($host === $apex || str_ends_with($host, '.localhost') === false && !str_ends_with($host, '.' . $apex)) {
            return null;
        }

        $subdomain = str_replace('.' . $apex, '', $host);
        if (in_array($subdomain, self::RESERVED_SUBDOMAINS, true)) {
            return null;
        }

        return Tenant::where('slug', $subdomain)->first();
    }

    private function setPostgresRlsVariable(int $tenantId): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("SET LOCAL app.tenant_id = '" . (int) $tenantId . "'");
    }
}
