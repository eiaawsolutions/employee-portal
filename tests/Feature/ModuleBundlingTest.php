<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Locks the Session 11 four-module bundling into place.
 *
 *   M1 Employee Journey  → Starter+
 *   M2 Asset Management  → Growth+
 *   M3 HRM               → Scale+
 *   M4 Finance           → Growth+
 *
 * Any future edit to config/plans.php that moves a feature across tiers
 * must update this test intentionally. That's the whole point.
 */
class ModuleBundlingTest extends TestCase
{
    /** @dataProvider bundlingMatrix */
    public function test_feature_is_included_in_expected_tiers(string $feature, array $expectedTiers): void
    {
        foreach (['starter', 'growth', 'scale', 'enterprise'] as $tier) {
            $features = config("plans.{$tier}.features", []);
            $hasFeature = in_array($feature, $features, true);
            $shouldHave = in_array($tier, $expectedTiers, true);

            $this->assertSame(
                $shouldHave,
                $hasFeature,
                "plan '{$tier}' " . ($shouldHave ? 'MUST' : 'MUST NOT') . " include feature '{$feature}'"
            );
        }
    }

    public static function bundlingMatrix(): array
    {
        $m1 = ['starter', 'growth', 'scale', 'enterprise'];     // M1 starts at Starter
        $m2 = ['growth', 'scale', 'enterprise'];                // M2 starts at Growth
        $m3 = ['scale', 'enterprise'];                          // M3 starts at Scale
        $m4 = ['growth', 'scale', 'enterprise'];                // M4 starts at Growth
        $ent = ['enterprise'];

        return [
            // M1 — Employee Journey
            'M1 core.employees'      => ['core.employees', $m1],
            'M1 core.onboarding'     => ['core.onboarding', $m1],
            'M1 core.offboarding'    => ['core.offboarding', $m1],

            // M2 — Asset Management
            'M2 it.assets'           => ['it.assets', $m2],
            'M2 it.aarf'             => ['it.aarf', $m2],
            'M2 it.offboarding'      => ['it.offboarding', $m2],

            // M3 — HRM
            'M3 core.leave'          => ['core.leave', $m3],
            'M3 core.attendance'     => ['core.attendance', $m3],
            'M3 hr.eclaim'           => ['hr.eclaim', $m3],
            'M3 hr.payroll'          => ['hr.payroll', $m3],
            'M3 hr.payslips'         => ['hr.payslips', $m3],
            'M3 hr.ea_forms'         => ['hr.ea_forms', $m3],

            // M4 — Finance
            'M4 finance.accounting'  => ['finance.accounting', $m4],

            // Enterprise-only extensions
            'Enterprise sso_saml'    => ['auth.sso_saml', $ent],
            'Enterprise sso_oidc'    => ['auth.sso_oidc', $ent],
            'Enterprise audit_export'=> ['audit.export', $ent],
            'Enterprise dedicated_db'=> ['infra.dedicated_db', $ent],
        ];
    }

    public function test_starter_price_is_six_usd(): void
    {
        $this->assertSame(6, config('plans.starter.price_usd_monthly'));
    }

    public function test_growth_price_is_fourteen_usd(): void
    {
        $this->assertSame(14, config('plans.growth.price_usd_monthly'));
    }

    public function test_scale_price_is_twentynine_usd(): void
    {
        $this->assertSame(29, config('plans.scale.price_usd_monthly'));
    }

    public function test_enterprise_price_is_custom(): void
    {
        $this->assertNull(config('plans.enterprise.price_usd_monthly'));
    }

    public function test_eiaaw_pricing_is_usd_only(): void
    {
        $currency = config('eiaaw.pricing.currency');
        $this->assertIsArray($currency);
        $this->assertSame('USD', $currency['code']);

        // No MYR currency slot anywhere in tier config
        foreach (['starter', 'growth', 'scale'] as $tier) {
            $stripePrices = config("eiaaw.pricing.tiers.{$tier}.stripe_prices");
            $this->assertArrayNotHasKey('MYR', (array) $stripePrices);
            $this->assertArrayHasKey('monthly', (array) $stripePrices);
            $this->assertArrayHasKey('annual', (array) $stripePrices);
        }
    }
}
