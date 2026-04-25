<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Platform-level diagnostics for EIAAW staff.
 *
 * Routes mounted under `/superadmin/diagnostics/*` behind the
 * EnsurePlatformAdmin middleware. Read-only; no secrets returned.
 */
class PlatformDiagnosticsController extends Controller
{
    /**
     * Session + tenant-domain config snapshot.
     *
     * Use this when investigating a /login → 2FA → /login loop. The cookie
     * domain must be `.{tenant_domain}` (leading dot) so the post-login
     * bounce from the marketing apex to a tenant subdomain carries the
     * session cookie. See commit 21b3f17.
     */
    public function sessionDomain(Request $request): JsonResponse
    {
        $tenantDomain  = strtolower((string) config('eiaaw.tenant_domain', ''));
        $marketingHost = strtolower((string) config('eiaaw.marketing_host', ''));
        $sessionDomain = strtolower((string) config('session.domain', ''));
        $expected      = $tenantDomain === '' ? '' : '.' . ltrim($tenantDomain, '.');

        return response()->json([
            'environment'     => app()->environment(),
            'request_host'    => strtolower($request->getHost()),
            'marketing_host'  => $marketingHost,
            'tenant_domain'   => $tenantDomain,
            'session' => [
                'domain'         => $sessionDomain ?: '(unset)',
                'domain_expected'=> $expected,
                'domain_correct' => $sessionDomain === $expected && $expected !== '',
                'secure_cookie'  => (bool) config('session.secure'),
                'same_site'      => (string) config('session.same_site'),
                'driver'         => (string) config('session.driver'),
                'lifetime_min'   => (int) config('session.lifetime'),
            ],
            'fix_if_incorrect' => $expected
                ? "Set SESSION_DOMAIN={$expected} in Railway env and redeploy."
                : 'APP_TENANT_DOMAIN is not set — set it before SESSION_DOMAIN.',
        ]);
    }
}
