<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Generate a per-request CSP nonce and share with views
        $nonce = Str::random(32);
        app()->instance('csp-nonce', $nonce);
        view()->share('cspNonce', $nonce);

        $response = $next($request);

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevent MIME-type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Referrer policy — same-origin only (tighter than strict-origin-when-cross-origin
        // because referrer leakage across the apex → tenant subdomain boundary is a
        // tenant-identity leak we want to prevent).
        $response->headers->set('Referrer-Policy', 'same-origin');

        // Restrict browser features
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), interest-cohort=()');

        // Cross-origin isolation — defends against Spectre-style side-channels and
        // ensures a tenant subdomain can't be iframed/embedded by another origin.
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        // Each origin gets its own agent cluster (per-origin event loops / JS heap).
        $response->headers->set('Origin-Agent-Cluster', '?1');

        // Content-Security-Policy — nonce-based for scripts, unsafe-inline kept for styles.
        //
        // `unsafe-hashes` is still needed because ~149 inline on* handlers remain across
        // 52 legacy blade views (inherited from the Claritas codebase). They are tracked
        // in FRONTEND-PATTERNS.md and being converted to addEventListener incrementally.
        // When that migration completes this line drops `unsafe-hashes` + `unsafe-inline`
        // from script-src, and `unsafe-inline` from style-src.
        $cspEnforced = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-hashes' https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
            "font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com",
            "img-src 'self' data: blob: https://api.qrserver.com",
            // connect-src 'self' — AI gateway calls out to api.anthropic.com/api.openai.com
            // from the SERVER, never the browser, so the browser only XHRs same-origin.
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "frame-src 'none'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "upgrade-insecure-requests",
        ]);
        $response->headers->set('Content-Security-Policy', $cspEnforced);

        // Content-Security-Policy-Report-Only — mirrors enforced policy but
        // WITHOUT 'unsafe-hashes' so browsers report what would break if we
        // dropped it. Violations go to /csp-report; they don't block. Enables
        // the Session 9+ inline-handler migration to run data-driven.
        $cspReportOnly = str_replace(
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-hashes'",
            "script-src 'self' 'nonce-{$nonce}'",
            $cspEnforced
        ) . '; report-uri /csp-report';
        $response->headers->set('Content-Security-Policy-Report-Only', $cspReportOnly);

        // HSTS — enforce HTTPS for 1 year, include subdomains, and opt in to the
        // browser preload list so even the first visit is https. Requires being
        // on https; only emitted when the request is already secure.
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Remove server identification headers
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        // Prevent caching of pages containing sensitive data (authenticated routes)
        if ($request->user()) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
