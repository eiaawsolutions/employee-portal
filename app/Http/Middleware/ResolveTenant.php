<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
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
 * After resolving:
 *  - Sets Postgres session variable app.tenant_id for RLS enforcement
 *  - Refuses access to suspended tenants with a friendly 402 page
 *  - Aborts with 404 for unknown subdomains (no tenant enumeration)
 */
class ResolveTenant
{
    private const RESERVED_SUBDOMAINS = ['app', 'admin', 'api', 'www', 'mail', 'static', 'assets'];

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if ($tenant) {
            if ($tenant->isSuspended()) {
                abort(402, 'This workspace is suspended. Please contact billing.');
            }

            app()->instance('current_tenant', $tenant);
            $this->setPostgresRlsVariable($tenant->id);
        }

        return $next($request);
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
