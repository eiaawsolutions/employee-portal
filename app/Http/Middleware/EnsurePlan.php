<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsurePlan — gate routes by the tenant's plan feature list.
 *
 * Usage:
 *   Route::middleware('plan:finance.accounting')->group(fn() => ...);
 *   Route::middleware('plan:it.assets')->group(fn() => ...);
 *   Route::middleware('plan:auth.sso_saml')->group(fn() => ...);
 *
 * The feature key is matched against Tenant::hasFeature() which reads
 * the plan's features[] list from config/plans.php. If the tenant's plan
 * doesn't include the feature, the user is redirected to the upgrade
 * prompt with the required feature in the query string.
 *
 * Must run AFTER ResolveTenant (current_tenant bound) AND after auth
 * (user is logged in). Marketing surfaces don't need plan-gating.
 *
 * Gracefully handles:
 *   - no current_tenant bound (e.g. platform-admin tools) → 403
 *   - suspended tenant → handled upstream by ResolveTenant (402)
 *   - XHR / JSON requests → 403 JSON instead of redirect
 */
class EnsurePlan
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (!app()->bound('current_tenant')) {
            abort(403, 'Plan check requires an active tenant context.');
        }

        $tenant = app('current_tenant');

        if ($tenant->hasFeature($feature)) {
            return $next($request);
        }

        // Feature not available on this plan. JSON for XHR, redirect for full page.
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'error' => 'upgrade_required',
                'message' => "Your current plan ({$tenant->plan}) does not include this feature.",
                'feature' => $feature,
            ], 403);
        }

        return redirect()
            ->route('upgrade-required')
            ->with('required_feature', $feature)
            ->with('current_plan', $tenant->plan);
    }
}
