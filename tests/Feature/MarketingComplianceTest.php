<?php

namespace Tests\Feature;

use App\Mail\FindWorkspaceMail;
use App\Mail\SignupConfirmationMail;
use App\Models\SignupInvite;
use App\Services\Billing\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Fakes\FakeStripeGateway;
use Tests\TestCase;

/**
 * The public marketing surface of ep.eiaawsolutions.com must meet the EIAAW
 * APAC privacy baseline (prior opt-in for tracking, unticked consent on
 * every form, a real EN + BM privacy notice) and only say what the product
 * actually does. These tests pin that contract.
 */
class MarketingComplianceTest extends TestCase
{
    use RefreshDatabase;

    private const MARKETING_ROUTES = [
        'marketing.landing', 'marketing.features', 'marketing.pricing',
        'marketing.security', 'marketing.faq', 'marketing.find-workspace',
        'marketing.privacy', 'marketing.terms', 'marketing.dpa',
    ];

    // ── Tracking only after opt-in ──────────────────────────────────────

    public function test_marketing_pages_load_the_consent_gate_and_no_raw_pixel(): void
    {
        foreach (self::MARKETING_ROUTES as $name) {
            $html = $this->get(route($name))->assertOk()->getContent();
            $this->assertStringContainsString('/consent.js', $html, "$name: consent gate missing");
            $this->assertStringNotContainsString("fbq('init'", $html, "$name: pixel initialised before consent");
            $this->assertStringNotContainsString('facebook.com/tr?', $html, "$name: noscript beacon present");
            $this->assertStringContainsString('data-cookie-settings', $html, "$name: no way to reopen cookie choices");
        }
    }

    public function test_signup_form_is_consent_gated(): void
    {
        $html = $this->get(route('signup.form', ['plan' => 'growth']))->assertOk()->getContent();
        $this->assertStringContainsString('/consent.js', $html);
        $this->assertStringNotContainsString("fbq('init'", $html);
    }

    public function test_app_and_auth_views_carry_no_tracking(): void
    {
        foreach ([
            'layouts/app.blade.php',
            'auth/login.blade.php',
            'auth/partials/_shell-head.blade.php',
            'signup/confirm.blade.php',
        ] as $view) {
            $src = file_get_contents(resource_path('views/'.$view));
            $this->assertStringNotContainsString('fbq(', $src, "$view still loads the Meta Pixel");
            $this->assertStringNotContainsString('facebook.com/tr', $src, "$view still has the pixel beacon");
        }
    }

    public function test_csp_only_allows_meta_on_the_marketing_host(): void
    {
        $marketing = $this->get(route('marketing.landing'))->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('connect.facebook.net', $marketing);

        $login = $this->get(route('login'))->headers->get('Content-Security-Policy');
        $this->assertStringNotContainsString('facebook', $login);
    }

    // ── Legal pages are real ────────────────────────────────────────────

    public function test_legal_pages_are_final_not_placeholders(): void
    {
        foreach (['marketing.privacy', 'marketing.terms', 'marketing.dpa'] as $name) {
            $html = $this->get(route($name))->assertOk()->getContent();
            $this->assertStringNotContainsString('Pre-launch placeholder', $html, $name);
            $this->assertStringNotContainsString('Do not rely on it', $html, $name);
        }
    }

    public function test_privacy_notice_covers_pdpa_essentials_in_english_and_malay(): void
    {
        $html = $this->get(route('marketing.privacy'))->assertOk()->getContent();

        foreach (['id="cookies"', 'id="bm"', 'Notis Privasi', 'Pesuruhjaya Perlindungan Data Peribadi',
                  '202603133419', 'Anthropic', 'Stripe', 'Resend', 'Railway', 'Cloudflare', 'Singapore',
                  'Meta', 'eiaawsolutions@gmail.com'] as $needle) {
            $this->assertStringContainsString($needle, $html, "privacy notice missing: $needle");
        }
        $this->assertStringContainsString('lang="ms"', $html);
    }

    // ── Consent on forms, with a stored record ──────────────────────────

    public function test_contact_form_rejects_submissions_without_consent(): void
    {
        $this->postJson(route('marketing.contact'), [
            'name' => 'Aisha', 'email' => 'aisha@example.com', 'message' => 'Pricing for 40 staff?',
        ])->assertStatus(422)->assertJsonPath('fields.consent.0', fn ($m) => is_string($m));

        $this->assertSame(0, DB::table('marketing_contacts')->count());
    }

    public function test_contact_form_stores_the_consent_record(): void
    {
        Mail::fake();

        $this->postJson(route('marketing.contact'), [
            'name' => 'Aisha', 'email' => 'aisha@example.com', 'message' => 'Pricing for 40 staff?',
            'consent' => true,
        ])->assertOk();

        $row = DB::table('marketing_contacts')->first();
        $this->assertSame(config('eiaaw.privacy_version'), $row->consent_version);
        $this->assertNotNull($row->consent_at);
    }

    public function test_signup_requires_agreement_to_terms_and_privacy(): void
    {
        Mail::fake();

        $this->post(route('signup.start'), $this->signupPayload())
            ->assertSessionHasErrors('consent');

        $this->assertSame(0, SignupInvite::count());
    }

    public function test_signup_records_when_and_to_which_version_the_user_agreed(): void
    {
        Mail::fake();
        $this->app->instance(StripeGateway::class, new FakeStripeGateway());

        // Pay-first: consent is recorded, then the visitor goes to Stripe
        // Checkout; the set-password email only follows payment.
        $response = $this->post(route('signup.start'), $this->signupPayload() + ['consent' => '1']);
        $this->assertStringStartsWith('https://checkout.stripe.com/', $response->headers->get('Location'));

        $invite = SignupInvite::firstOrFail();
        $this->assertSame(config('eiaaw.privacy_version'), $invite->consent_version);
        $this->assertNotNull($invite->consent_at);
        Mail::assertNotSent(SignupConfirmationMail::class);
    }

    // ── Find-workspace actually emails ──────────────────────────────────

    public function test_find_workspace_emails_the_owner_their_workspace_links(): void
    {
        $invite = SignupInvite::create([
            'work_email' => 'owner@example.com', 'full_name' => 'Owner', 'company_name' => 'Acme',
            'desired_slug' => 'acmeco', 'plan' => 'growth',
            'confirmation_token' => Str::random(48), 'expires_at' => now()->addDay(),
            'paid_at' => now(), 'seats' => 5,
        ]);
        $this->post(route('signup.confirm.submit', $invite->confirmation_token), [
            'password' => 'a-strong-password-123', 'password_confirmation' => 'a-strong-password-123',
        ])->assertRedirect();

        Mail::fake();

        $this->post(route('marketing.find-workspace.lookup'), ['work_email' => 'owner@example.com'])->assertOk();

        Mail::assertSent(FindWorkspaceMail::class, function (FindWorkspaceMail $m) {
            return $m->hasTo('owner@example.com')
                && $m->workspaces->pluck('slug')->contains('acmeco');
        });
    }

    public function test_find_workspace_gives_the_same_answer_for_unknown_emails(): void
    {
        Mail::fake();

        $html = $this->post(route('marketing.find-workspace.lookup'), ['work_email' => 'nobody@example.com'])
            ->assertOk()->getContent();

        Mail::assertNothingSent();
        $this->assertStringContainsString('If that email belongs to a workspace', $html);
    }

    // ── One canonical host ──────────────────────────────────────────────

    public function test_www_host_redirects_permanently_to_the_marketing_host(): void
    {
        $this->get('https://www.ep.eiaawsolutions.com/pricing?x=1')
            ->assertStatus(301)
            ->assertRedirect('https://ep.eiaawsolutions.com/pricing?x=1');
    }

    public function test_sign_in_and_signup_pages_are_not_indexed(): void
    {
        // /login on the marketing host redirects to find-workspace; the view itself renders on tenant hosts.
        $this->assertStringContainsString('noindex', file_get_contents(resource_path('views/auth/login.blade.php')));
        $this->assertStringContainsString('noindex', $this->get(route('signup.form', ['plan' => 'starter']))->getContent());
    }

    // ── Structured data agrees with the page and the parent entity ──────

    public function test_landing_structured_data_links_to_the_parent_entity(): void
    {
        $blocks = $this->jsonLd($this->get(route('marketing.landing'))->getContent());
        $types = array_map(fn ($b) => $b['@type'] ?? null, $blocks);

        $this->assertNotContains('LocalBusiness', $types);

        $app = collect($blocks)->firstWhere('@type', 'SoftwareApplication');
        $this->assertSame('https://eiaawsolutions.com/products.html#workforce', $app['@id']);
        $this->assertSame('https://eiaawsolutions.com/#organization', $app['publisher']['@id']);
        $this->assertSame('AggregateOffer', $app['offers']['@type']);
        $this->assertSame('MYR', $app['offers']['priceCurrency']);
        $this->assertEquals(25, $app['offers']['lowPrice']);
        $this->assertEquals(119, $app['offers']['highPrice']);
    }

    public function test_faq_schema_mirrors_the_visible_faq_exactly(): void
    {
        foreach (['marketing.landing', 'marketing.faq', 'marketing.pricing'] as $name) {
            $html = $this->get(route($name))->getContent();
            $faq = collect($this->jsonLd($html))->firstWhere('@type', 'FAQPage');
            $this->assertNotNull($faq, "$name has no FAQPage schema");

            $visible = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5);
            foreach ($faq['mainEntity'] as $qa) {
                $this->assertStringContainsString($qa['name'], $visible, "$name: question not visible");
                $this->assertStringContainsString($qa['acceptedAnswer']['text'], $visible, "$name: answer differs from page");
            }
        }
    }

    // ── Only claims the code backs ──────────────────────────────────────

    public function test_marketing_copy_makes_no_unsupported_claims(): void
    {
        $surfaces = [];
        foreach (['marketing.landing', 'marketing.features', 'marketing.pricing', 'marketing.security', 'marketing.faq'] as $name) {
            $surfaces[$name] = html_entity_decode(strip_tags($this->get(route($name))->getContent()), ENT_QUOTES | ENT_HTML5);
        }
        $surfaces['llms.txt'] = file_get_contents(public_path('llms.txt'));

        $unsupported = [
            'PERKESO, HRDC',            // no statutory file exports exist
            'SCIM',                     // no SCIM provisioning
            'GST',                      // Malaysia abolished GST in 2018
            'TLS 1.3 enforced',         // edge still accepts TLS 1.2
            'Hallucinations are caught',
            'Never hallucinates',
            'Slack',                    // no Slack integration or channel support
            'Llama',                    // no self-hosted model option
            'MYR (primary)',            // pricing is MYR-only
            'Up to 50 users',           // never true
            '14-day',                   // no free trial — paid at checkout
            'no credit card',           // checkout takes a card up front
            'US$',                      // pricing is MYR
            '/emp/mo USD',
            'Anomaly detection',
            'Dunning',
            'Delta payslips',
            'weekly retained 12 months',
            'takes one click',
        ];

        foreach ($surfaces as $where => $text) {
            foreach ($unsupported as $claim) {
                $this->assertStringNotContainsStringIgnoringCase($claim, $text, "$where still claims: $claim");
            }
        }
    }

    public function test_crawler_files_are_current(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));
        $this->assertStringContainsString('Claude-SearchBot', $robots);
        $this->assertStringNotContainsString('Allow: /signup', $robots);

        $llms = file_get_contents(public_path('llms.txt'));
        $this->assertStringContainsString('acme.ep.eiaawsolutions.com', $llms);
        $this->assertStringContainsString('https://eiaawsolutions.com', $llms);

        $this->assertFileExists(public_path('.well-known/security.txt'));
    }

    public function test_voice_agent_explains_recording_before_the_call(): void
    {
        $html = $this->get(route('marketing.landing'))->getContent();
        $this->assertStringContainsString('id="ep-voice-notice"', $html);
    }

    // ── helpers ─────────────────────────────────────────────────────────

    private function signupPayload(): array
    {
        return [
            'work_email' => 'founder@gmail.com',
            'full_name' => 'Nur Aisyah',
            'company_name' => 'Kedai Maju',
            'desired_slug' => 'kedaimaju',
            'plan' => 'growth',
            'period' => 'monthly',
            'headcount' => 8,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function jsonLd(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json"[^>]*>(.*?)</script>#s', $html, $m);
        $blocks = [];
        foreach ($m[1] as $raw) {
            $decoded = json_decode($raw, true);
            $this->assertIsArray($decoded, 'invalid JSON-LD: '.substr($raw, 0, 120));
            $blocks = array_merge($blocks, isset($decoded['@graph']) ? $decoded['@graph'] : [$decoded]);
        }

        return $blocks;
    }
}
