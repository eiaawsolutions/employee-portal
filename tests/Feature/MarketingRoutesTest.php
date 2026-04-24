<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Smoke test for the Session 6 marketing surfaces.
 *
 * Verifies every apex-only marketing route renders (HTTP 200) and that
 * the EnsureApex middleware 404s the same routes when a tenant is bound.
 *
 * Binding a fake tenant for the 404 test avoids needing a real tenant row
 * in the test DB (which is sqlite:memory — tenant tables aren't in the
 * schema for this env).
 */
class MarketingRoutesTest extends TestCase
{
    /** @dataProvider marketingRoutes */
    public function test_marketing_routes_render_on_apex(string $name): void
    {
        $this->get(route($name))->assertOk();
    }

    public function test_ensure_apex_middleware_404s_on_tenant_subdomain(): void
    {
        // Simulate resolution of a tenant (what ResolveTenant does on a tenant subdomain)
        // by binding the container key the middleware checks for.
        $this->app->instance('current_tenant', (object) ['id' => 1, 'slug' => 'acme']);

        $this->get(route('marketing.landing'))->assertNotFound();
        $this->get(route('marketing.pricing'))->assertNotFound();
        $this->get(route('marketing.features'))->assertNotFound();
        $this->get(route('marketing.security'))->assertNotFound();
        $this->get(route('marketing.faq'))->assertNotFound();
        $this->get(route('marketing.find-workspace'))->assertNotFound();
        $this->get(route('signup.form'))->assertNotFound();
    }

    public static function marketingRoutes(): array
    {
        return [
            ['marketing.landing'],
            ['marketing.pricing'],
            ['marketing.features'],
            ['marketing.security'],
            ['marketing.faq'],
            ['marketing.find-workspace'],
        ];
    }
}
