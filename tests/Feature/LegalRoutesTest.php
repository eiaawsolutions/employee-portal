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

    public function test_legal_pages_carry_the_agreed_commitments(): void
    {
        $terms = $this->get(route('marketing.terms'))->getContent();
        $this->assertStringContainsString('not registered for Sales and Service Tax', $terms);
        $this->assertStringContainsString('for business use', $terms);

        $privacy = $this->get(route('marketing.privacy'))->getContent();
        $this->assertStringContainsString('within 7 days of notifying the Commissioner', $privacy);
        $this->assertStringContainsString('Cloudflare R2', $privacy);

        $dpa = $this->get(route('marketing.dpa'))->getContent();
        $this->assertStringContainsString('at least 30 days’ notice', $dpa);
        $this->assertStringContainsString('religion', $dpa);
    }

    public function test_superseded_versions_stay_available_but_unindexed(): void
    {
        foreach (['privacy', 'terms', 'dpa'] as $doc) {
            $r = $this->get(route('marketing.legal.archive', ['2026-09-24', $doc]));
            $r->assertOk();
            $r->assertSee('Superseded version');
            $r->assertSee('noindex', false);
        }
        $this->get(route('marketing.legal.archive', ['2026-01-01', 'terms']))->assertNotFound();
        $this->get('/legal/archive/2026-09-24/..%2Fterms')->assertNotFound();
    }

    public function test_legal_routes_404_on_tenant_subdomain(): void
    {
        $this->app->instance('current_tenant', (object) ['id' => 1, 'slug' => 'acme']);

        $this->get(route('marketing.terms'))->assertNotFound();
        $this->get(route('marketing.privacy'))->assertNotFound();
        $this->get(route('marketing.dpa'))->assertNotFound();
    }
}
