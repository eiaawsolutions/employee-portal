<?php

namespace Tests\Feature;

use App\Mail\SignupConfirmationMail;
use App\Models\SignupInvite;
use App\Models\Tenant;
use App\Services\Billing\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Fakes\FakeStripeGateway;
use Tests\TestCase;

/**
 * Pay-first signup: plan → details → Stripe Checkout (MYR, no trial) →
 * set password → workspace. Mirrors the Social Media Team flow.
 */
class SignupCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeGateway $stripe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stripe = new FakeStripeGateway();
        $this->app->instance(StripeGateway::class, $this->stripe);
        Mail::fake();
    }

    private function signupPayload(array $overrides = []): array
    {
        return array_merge([
            'work_email'   => 'owner@gmail.com',
            'full_name'    => 'Nur Aisyah',
            'company_name' => 'Acme Sdn Bhd',
            'desired_slug' => 'acme',
            'plan'         => 'growth',
            'period'       => 'monthly',
            'headcount'    => 12,
            'consent'      => '1',
        ], $overrides);
    }

    public function test_signup_redirects_to_stripe_checkout_in_ringgit_with_no_trial(): void
    {
        $response = $this->post(route('signup.start'), $this->signupPayload());

        $response->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_fake_1');

        $session = $this->stripe->createdSessions[0];
        $this->assertSame('subscription', $session['mode']);
        $this->assertSame('owner@gmail.com', $session['customer_email']);
        $this->assertSame(12, $session['line_items'][0]['quantity']);
        $this->assertArrayNotHasKey('trial_period_days', $session['subscription_data'] ?? []);

        $price = $this->stripe->createdPrices[0];
        $this->assertSame('myr', $price['currency']);
        $this->assertSame(5900, $price['unit_amount']);
        $this->assertSame('month', $price['recurring']['interval']);

        $invite = SignupInvite::where('work_email', 'owner@gmail.com')->firstOrFail();
        $this->assertSame((string) $invite->id, $session['metadata']['signup_invite_id']);
        $this->assertSame('cs_test_fake_1', $invite->stripe_checkout_session_id);
        $this->assertSame(12, $invite->seats);
        $this->assertNull($invite->paid_at);
        Mail::assertNothingSent();
    }

    public function test_headcount_below_the_minimum_is_billed_at_five_employees(): void
    {
        $this->post(route('signup.start'), $this->signupPayload(['headcount' => 2]));

        $this->assertSame(5, $this->stripe->createdSessions[0]['line_items'][0]['quantity']);
    }

    public function test_annual_billing_charges_ten_months_per_employee(): void
    {
        $this->post(route('signup.start'), $this->signupPayload(['plan' => 'scale', 'period' => 'annual']));

        $price = $this->stripe->createdPrices[0];
        $this->assertSame(119000, $price['unit_amount']); // RM 119 × 10 months
        $this->assertSame('year', $price['recurring']['interval']);
    }

    public function test_an_existing_price_is_reused_by_lookup_key(): void
    {
        $this->post(route('signup.start'), $this->signupPayload());
        $this->post(route('signup.start'), $this->signupPayload([
            'work_email' => 'second@gmail.com', 'desired_slug' => 'second',
        ]));

        $this->assertCount(1, $this->stripe->createdPrices);
        $this->assertCount(2, $this->stripe->createdSessions);
    }

    public function test_signup_still_requires_consent(): void
    {
        $this->post(route('signup.start'), $this->signupPayload(['consent' => null]))
            ->assertSessionHasErrors('consent');

        $this->assertEmpty($this->stripe->createdSessions);
    }

    public function test_successful_payment_marks_the_invite_paid_and_goes_to_set_password(): void
    {
        $this->post(route('signup.start'), $this->signupPayload());
        $this->stripe->completeSession('cs_test_fake_1');

        $response = $this->get(route('signup.checkout.success', ['session_id' => 'cs_test_fake_1']));

        $invite = SignupInvite::where('work_email', 'owner@gmail.com')->firstOrFail();
        $response->assertRedirect(route('signup.confirm', $invite->confirmation_token));
        $this->assertNotNull($invite->paid_at);
        $this->assertSame('cus_fake_1', $invite->stripe_customer_id);
        $this->assertSame('sub_fake_1', $invite->stripe_subscription_id);
        Mail::assertSent(SignupConfirmationMail::class, 1);
    }

    public function test_an_unpaid_session_does_not_unlock_the_workspace(): void
    {
        $this->post(route('signup.start'), $this->signupPayload());

        $this->get(route('signup.checkout.success', ['session_id' => 'cs_test_fake_1']))
            ->assertRedirect();

        $this->assertNull(SignupInvite::first()->paid_at);
        Mail::assertNothingSent();
    }

    public function test_a_session_that_does_not_belong_to_the_invite_is_rejected(): void
    {
        $this->post(route('signup.start'), $this->signupPayload());
        $invite = SignupInvite::first();
        $invite->update(['stripe_checkout_session_id' => 'cs_someone_else']);
        $this->stripe->completeSession('cs_test_fake_1');

        $this->get(route('signup.checkout.success', ['session_id' => 'cs_test_fake_1']));

        $this->assertNull($invite->fresh()->paid_at);
    }

    public function test_webhook_records_payment_when_the_browser_never_returns(): void
    {
        $this->post(route('signup.start'), $this->signupPayload());
        $session = $this->stripe->completeSession('cs_test_fake_1');

        $this->postSignedWebhook('checkout.session.completed', $session)->assertOk();

        $this->assertNotNull(SignupInvite::first()->paid_at);
        Mail::assertSent(SignupConfirmationMail::class, 1);
    }

    public function test_the_set_password_email_is_sent_once_when_both_paths_fire(): void
    {
        $this->post(route('signup.start'), $this->signupPayload());
        $session = $this->stripe->completeSession('cs_test_fake_1');

        $this->get(route('signup.checkout.success', ['session_id' => 'cs_test_fake_1']));
        $this->postSignedWebhook('checkout.session.completed', $session)->assertOk();

        Mail::assertSent(SignupConfirmationMail::class, 1);
    }

    public function test_an_unsigned_webhook_is_rejected(): void
    {
        $this->post(route('signup.start'), $this->signupPayload());
        $session = $this->stripe->completeSession('cs_test_fake_1');
        config(['cashier.webhook.secret' => 'whsec_test_secret']);

        $this->postJson('/stripe/webhook', [
            'id' => 'evt_forged', 'type' => 'checkout.session.completed', 'data' => ['object' => $session],
        ])->assertForbidden();

        $this->assertNull(SignupInvite::first()->paid_at);
    }

    /** POST a webhook signed exactly as Stripe signs it (t=…,v1=HMAC-SHA256). */
    private function postSignedWebhook(string $type, array $object)
    {
        $secret = 'whsec_test_secret';
        config(['cashier.webhook.secret' => $secret]);

        $payload = json_encode(['id' => 'evt_' . Str::random(10), 'type' => $type, 'data' => ['object' => $object]]);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        return $this->call('POST', '/stripe/webhook', [], [], [], [
            'CONTENT_TYPE'          => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ], $payload);
    }

    public function test_the_password_page_is_closed_until_payment_is_made(): void
    {
        $this->post(route('signup.start'), $this->signupPayload());
        $invite = SignupInvite::first();

        $this->get(route('signup.confirm', $invite->confirmation_token))
            ->assertRedirect(route('signup.form', ['plan' => 'growth']));

        $this->post(route('signup.confirm.submit', $invite->confirmation_token), [
            'password' => 'a-strong-password-123', 'password_confirmation' => 'a-strong-password-123',
        ])->assertRedirect(route('signup.form', ['plan' => 'growth']));

        $this->assertDatabaseMissing('tenants', ['slug' => 'acme']);
    }

    public function test_confirming_a_paid_signup_provisions_a_paid_workspace_without_a_trial(): void
    {
        $this->post(route('signup.start'), $this->signupPayload());
        $this->stripe->completeSession('cs_test_fake_1');
        $this->get(route('signup.checkout.success', ['session_id' => 'cs_test_fake_1']));
        $invite = SignupInvite::first();

        $this->post(route('signup.confirm.submit', $invite->confirmation_token), [
            'password' => 'a-strong-password-123', 'password_confirmation' => 'a-strong-password-123',
        ])->assertRedirect();

        $tenant = Tenant::withoutGlobalScopes()->where('slug', 'acme')->firstOrFail();
        $this->assertNull($tenant->trial_ends_at);
        $this->assertSame('MYR', $tenant->billing_currency);
        $this->assertSame('active', $tenant->subscription_status);
        $this->assertSame('cus_fake_1', $tenant->stripe_id);
        $this->assertSame('sub_fake_1', $tenant->stripe_subscription_id);
        $this->assertSame(12, $tenant->plan_seats);
    }

    public function test_resubmitting_a_paid_email_resends_the_link_instead_of_charging_again(): void
    {
        $this->post(route('signup.start'), $this->signupPayload());
        $this->stripe->completeSession('cs_test_fake_1');
        $this->get(route('signup.checkout.success', ['session_id' => 'cs_test_fake_1']));
        $token = SignupInvite::first()->confirmation_token;

        $this->post(route('signup.start'), $this->signupPayload(['desired_slug' => 'other']))
            ->assertRedirect(route('signup.sent'));

        $this->assertCount(1, $this->stripe->createdSessions);
        $this->assertSame($token, SignupInvite::first()->confirmation_token);
        Mail::assertSent(SignupConfirmationMail::class, 2);
    }
}
