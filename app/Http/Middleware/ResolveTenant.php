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
 * Two host concepts (April 2026 layout):
 *  - marketing_host  (ep.eiaawsolutions.com)        → marketing apex, no tenant
 *  - tenant_domain   (eiaawsolutions.com)           → root for tenant subdomains
 *
 * Cloudflare Universal SSL only covers one subdomain level free, so tenants
 * live under bare *.eiaawsolutions.com instead of *.ep.eiaawsolutions.com.
 *
 * Subdomain rules:
 *  - acme.eiaawsolutions.com               → tenant slug 'acme'
 *  - eiaaw-hq.eiaawsolutions.com           → tenant slug 'eiaaw-hq' (HQ workspace)
 *  - ep.eiaawsolutions.com                 → marketing apex (no tenant)
 *  - eiaawsolutions.com / www.…            → corporate site (Netlify, not us)
 *  - ads.eiaawsolutions.com / sa.…         → other EIAAW apps (reserved, not us)
 *  - localhost / 127.0.0.1                 → fallback to ?tenant= query for dev
 *
 * After resolving:
 *  - Sets Postgres session variable app.tenant_id for RLS enforcement
 *  - Refuses access to suspended tenants with a friendly 402 page
 *  - Returns null (= no tenant) for marketing apex + reserved subdomains
 */
class ResolveTenant
{
    /**
     * Subdomain prefixes that are NEVER tenant slugs even though they sit
     * under tenant_domain. Includes other EIAAW apps that share the zone.
     */
    private const RESERVED_SUBDOMAINS = [
        'app', 'admin', 'api', 'www', 'mail', 'static', 'assets',
        'ep',                          // Workforce marketing apex
        'ads', 'sa',                   // Other EIAAW apps living under the same zone
    ];

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

        $host           = strtolower($request->getHost());
        $tenantDomain   = strtolower(config('eiaaw.tenant_domain', env('APP_TENANT_DOMAIN', 'eiaawsolutions.com')));
        $marketingHost  = strtolower(config('eiaaw.marketing_host', env('APP_MARKETING_HOST', 'ep.eiaawsolutions.com')));

        // Marketing apex, the bare tenant root, and Railway's internal
        // hostname all resolve to "no tenant" — they're either marketing
        // pages or the corporate Netlify site (which Railway never serves
        // anyway, but be defensive).
        if ($host === $marketingHost || $host === $tenantDomain) {
            return null;
        }

        // Subdomain check: must end with `.{tenant_domain}` to be a candidate
        // tenant slug. Unrelated hosts (Railway internal, IP addresses,
        // localhost without dev override) fall through to no-tenant.
        if (!str_ends_with($host, '.' . $tenantDomain)) {
            return null;
        }

        // Strip the trailing `.{tenant_domain}` to get the prefix. On bare
        // eiaawsolutions.com this yields the leftmost label; on the legacy
        // ep.eiaawsolutions.com layout it would yield e.g. "acme.ep" which
        // is correctly NOT a real slug — handled by the existence check.
        $subdomain = substr($host, 0, -strlen('.' . $tenantDomain));

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
