<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsurePlatformAdmin — gates EIAAW-staff-only routes (Integrations page,
 * platform-wide ops). Distinct from per-tenant superadmin role.
 */
class EnsurePlatformAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->isPlatformAdmin()) {
            abort(404);
        }
        return $next($request);
    }
}
