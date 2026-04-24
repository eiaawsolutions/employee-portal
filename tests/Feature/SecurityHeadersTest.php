<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Verifies the Session 8 security-header tightening. Uses the landing page
 * as the canary — every marketing response runs through SecurityHeaders.
 *
 * These assertions lock the tightened configuration in place so a future
 * edit can't silently drop a header and weaken the browser side of the
 * defence stack.
 */
class SecurityHeadersTest extends TestCase
{
    public function test_security_headers_on_landing(): void
    {
        $r = $this->get(route('marketing.landing'));
        $r->assertOk();

        $r->assertHeader('X-Frame-Options', 'DENY');
        $r->assertHeader('X-Content-Type-Options', 'nosniff');
        $r->assertHeader('Referrer-Policy', 'same-origin');
        $r->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
        $r->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');
        $r->assertHeader('Origin-Agent-Cluster', '?1');

        $permissions = $r->headers->get('Permissions-Policy');
        $this->assertStringContainsString('camera=()', $permissions);
        $this->assertStringContainsString('microphone=()', $permissions);
        $this->assertStringContainsString('interest-cohort=()', $permissions);
    }

    public function test_csp_locks_down_dangerous_fetch_destinations(): void
    {
        $csp = $this->get(route('marketing.landing'))->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp, 'CSP header must be present');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("frame-src 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
        $this->assertStringContainsString("upgrade-insecure-requests", $csp);
    }

    public function test_csp_has_nonce_for_scripts(): void
    {
        $r = $this->get(route('marketing.landing'));
        $csp = $r->headers->get('Content-Security-Policy');

        $this->assertMatchesRegularExpression(
            "/script-src [^;]*'nonce-[A-Za-z0-9]+'/",
            $csp,
            'script-src must include a per-request nonce'
        );
    }
}
