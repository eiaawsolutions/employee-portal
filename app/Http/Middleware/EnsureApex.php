<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureApex — 404 when a marketing route is hit on a tenant subdomain.
 *
 * Marketing surfaces (/, /pricing, /features, /security, /find-workspace, /faq,
 * /signup*) only exist at the apex host (ep.eiaawsolutions.com). If a request
 * reaches them on {slug}.ep.eiaawsolutions.com we 404 — tenants already have
 * their own app and signup doesn't apply to them.
 *
 * Relies on ResolveTenant having already run. When on the apex, current_tenant
 * is NOT bound; on a tenant subdomain it IS. No subdomain enumeration happens
 * here — ResolveTenant owns that concern.
 */
class EnsureApex
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->bound('current_tenant')) {
            abort(404);
        }

        return $next($request);
    }
}
