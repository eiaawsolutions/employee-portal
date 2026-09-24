<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Legal routes render on the apex and 404 on tenant subdomains, same
 * contract as the other marketing surfaces, and carry final copy.
 */
class LegalRoutesTest extends TestCase
{
    public function test_terms_renders_on_apex(): void
    {
        $r = $this->get(route('marketing.terms'));
        $r->assertOk();
        $r->assertSee('Governing law');
        $r->assertDontSee('Pre-launch placeholder');
    }

    public function test_privacy_renders_on_apex(): void
    {
        $r = $this->get(route('marketing.privacy'));
        $r->assertOk();
        $r->assertSee('Notis Privasi');
        $r->assertDontSee('Pre-launch placeholder');
    }

    public function test_dpa_renders_on_apex(): void
    {
        $r = $this->get(route('marketing.dpa'));
        $r->assertOk();
        $r->assertSee('Sub-processors');
        $r->assertDontSee('Pre-launch placeholder');
    }

    public function test_legal_routes_404_on_tenant_subdomain(): void
    {
        $this->app->instance('current_tenant', (object) ['id' => 1, 'slug' => 'acme']);

        $this->get(route('marketing.terms'))->assertNotFound();
        $this->get(route('marketing.privacy'))->assertNotFound();
        $this->get(route('marketing.dpa'))->assertNotFound();
    }
}
