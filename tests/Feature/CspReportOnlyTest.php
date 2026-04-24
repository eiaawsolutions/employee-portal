<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Verifies the Session 9 CSP-Report-Only header is attached and its
 * script-src does NOT contain 'unsafe-hashes' — that's the whole point
 * of the report-only parallel policy: to measure what WOULD break if we
 * dropped 'unsafe-hashes' from the enforced CSP.
 *
 * The enforced CSP still contains 'unsafe-hashes' (149 inline handlers
 * remain) — SecurityHeadersTest covers the enforced policy's contract.
 */
class CspReportOnlyTest extends TestCase
{
    public function test_report_only_header_is_emitted(): void
    {
        $r = $this->get(route('marketing.landing'));
        $r->assertOk();

        $reportOnly = $r->headers->get('Content-Security-Policy-Report-Only');
        $this->assertNotNull($reportOnly, 'Content-Security-Policy-Report-Only must be emitted');
    }

    public function test_report_only_drops_unsafe_hashes(): void
    {
        $r = $this->get(route('marketing.landing'));
        $reportOnly = $r->headers->get('Content-Security-Policy-Report-Only');

        $scriptSrc = $this->extractDirective($reportOnly, 'script-src');
        $this->assertNotNull($scriptSrc);
        $this->assertStringNotContainsString('unsafe-hashes', $scriptSrc,
            'Report-only CSP must drop unsafe-hashes so we can measure the migration delta');
        $this->assertStringContainsString("nonce-", $scriptSrc,
            'Report-only CSP must still include the per-request nonce for legitimate scripts');
    }

    public function test_report_only_points_at_csp_report_endpoint(): void
    {
        $r = $this->get(route('marketing.landing'));
        $reportOnly = $r->headers->get('Content-Security-Policy-Report-Only');

        $this->assertStringContainsString('report-uri /csp-report', $reportOnly);
    }

    public function test_enforced_csp_still_has_unsafe_hashes_until_migration_done(): void
    {
        // Explicit sanity test — if someone drops 'unsafe-hashes' from the enforced
        // CSP without migrating the 149 inline handlers first, this test fails
        // loudly as a safety net.
        $r = $this->get(route('marketing.landing'));
        $enforced = $r->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('unsafe-hashes', $enforced,
            'Enforced CSP still needs unsafe-hashes until the 149-handler migration completes (see FRONTEND-PATTERNS.md)');
    }

    private function extractDirective(string $csp, string $name): ?string
    {
        foreach (explode(';', $csp) as $part) {
            $part = trim($part);
            if (str_starts_with($part, $name . ' ')) {
                return substr($part, strlen($name) + 1);
            }
        }
        return null;
    }
}
