<?php

namespace App\Services\Billing;

use App\Mail\SignupConfirmationMail;
use App\Models\SignupInvite;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Pay-first signup checkout (mirrors the Social Media Team flow):
 *
 *   SignupController@start  → start()          → Stripe Checkout (MYR, no trial)
 *   success URL / webhook   → recordPayment()  → invite paid + set-password email
 *   SignupController@confirm                   → workspace provisioned
 *
 * Prices are per active employee, billed as a per-unit subscription whose
 * quantity is the headcount entered at signup (minimum config('plans.*.min_seats')).
 * Stripe Prices are found by lookup key — which embeds currency, amount and
 * interval — and created on first use, so a price change never reuses a
 * stale Price and no price-ID env vars are needed.
 */
class SignupCheckout
{
    public const CURRENCY = 'myr';

    public const PERIODS = ['monthly' => 'month', 'annual' => 'year'];

    public function __construct(private readonly StripeGateway $stripe)
    {
    }

    /** Price per employee per billing period, in sen. */
    public function unitAmount(string $plan, string $period): int
    {
        $monthly = config("eiaaw.pricing.tiers.{$plan}.monthly_myr");
        if (!is_numeric($monthly)) {
            throw new \InvalidArgumentException("Plan '{$plan}' has no self-serve price.");
        }

        $months = $period === 'annual' ? 12 - (int) config('eiaaw.pricing.annual_months_free', 0) : 1;

        return (int) round($monthly * 100) * $months;
    }

    /** Billed employees: the declared headcount, never below the plan minimum. */
    public function quantity(string $plan, int $headcount): int
    {
        return max((int) config("plans.{$plan}.min_seats", 1), $headcount);
    }

    /** Stripe Price lookup key — currency, amount and period are all part of it. */
    public function lookupKey(string $plan, string $period): string
    {
        return sprintf('eiaaw_workforce_%s_%s_%d_%s', $plan, self::CURRENCY, $this->unitAmount($plan, $period), $period);
    }

    public function priceId(string $plan, string $period): string
    {
        $interval = self::PERIODS[$period] ?? throw new \InvalidArgumentException("Unknown period '{$period}'.");
        $amount = $this->unitAmount($plan, $period);
        $lookupKey = $this->lookupKey($plan, $period);

        return Cache::rememberForever("stripe_price:{$lookupKey}", function () use ($lookupKey, $plan, $amount, $interval) {
            return $this->stripe->findPriceIdByLookupKey($lookupKey)
                ?? $this->stripe->createPrice([
                    'lookup_key'   => $lookupKey,
                    'currency'     => self::CURRENCY,
                    'unit_amount'  => $amount,
                    'recurring'    => ['interval' => $interval],
                    // Pre-SST price, matching the marketed number. See the SMT
                    // billing config for the SST rationale.
                    'tax_behavior' => 'exclusive',
                    'product_data' => [
                        'name'     => 'EIAAW Workforce — ' . config("eiaaw.pricing.tiers.{$plan}.name") . ' (per active employee)',
                        'metadata' => ['plan' => $plan],
                    ],
                    'metadata' => ['plan' => $plan],
                ]);
        });
    }

    /**
     * Create the Stripe Checkout Session for an invite and remember it on the
     * invite. Returns the Stripe-hosted checkout URL.
     */
    public function start(SignupInvite $invite, string $successUrl, string $cancelUrl): string
    {
        $session = $this->stripe->createCheckoutSession([
            'mode'                       => 'subscription',
            'customer_email'             => $invite->work_email,
            'line_items'                 => [[
                'price'    => $this->priceId($invite->plan, $invite->billing_period),
                'quantity' => $invite->seats,
            ]],
            'billing_address_collection' => 'required',
            // No trial_period_days — the first period is charged at checkout.
            'subscription_data'          => [
                'metadata' => ['plan' => $invite->plan, 'workspace' => $invite->desired_slug],
            ],
            'metadata'                   => [
                'intent'           => 'workforce_signup',
                'signup_invite_id' => (string) $invite->id,
                'plan'             => $invite->plan,
            ],
            'success_url'                => $successUrl,
            'cancel_url'                 => $cancelUrl,
            'allow_promotion_codes'      => true,
        ]);

        $invite->update(['stripe_checkout_session_id' => $session['id']]);

        return $session['url'];
    }

    /**
     * Record a completed Checkout Session against its invite. Called from both
     * the success redirect and the checkout.session.completed webhook; the
     * atomic whereNull('paid_at') update guarantees the set-password email is
     * sent exactly once whichever path wins.
     *
     * Returns the paid invite, or null if the session is not a paid signup
     * that belongs to a known invite.
     */
    public function recordPayment(array $session): ?SignupInvite
    {
        $inviteId = $session['metadata']['signup_invite_id'] ?? null;
        $paid = ($session['status'] ?? null) === 'complete'
            && ($session['payment_status'] ?? null) === 'paid';

        if (!$inviteId || !$paid) {
            return null;
        }

        $invite = SignupInvite::find($inviteId);

        // The session must be the one this invite was sent to — a paid
        // session for invite A can never unlock invite B.
        if (!$invite || $invite->stripe_checkout_session_id !== ($session['id'] ?? null)) {
            Log::warning('signup.checkout.session_mismatch', [
                'invite_id'  => $inviteId,
                'session_id' => $session['id'] ?? null,
            ]);
            return null;
        }

        $newlyPaid = SignupInvite::whereKey($invite->id)->whereNull('paid_at')->update([
            'paid_at'                => now(),
            'stripe_customer_id'     => $session['customer'] ?? null,
            'stripe_subscription_id' => $session['subscription'] ?? null,
            // They've paid — give them a month to set a password.
            'expires_at'             => now()->addDays(30),
        ]) === 1;

        $invite->refresh();

        if ($newlyPaid) {
            $this->sendSetPasswordLink($invite);
        }

        return $invite;
    }

    /** Email the set-password link. Non-fatal: the success redirect also lands on the page. */
    public function sendSetPasswordLink(SignupInvite $invite): bool
    {
        try {
            Mail::to($invite->work_email)->send(new SignupConfirmationMail($invite));
            return true;
        } catch (\Throwable $e) {
            Log::error('signup.set_password_mail_failed', [
                'invite_id' => $invite->id,
                'error'     => $e->getMessage(),
            ]);
            report($e);
            return false;
        }
    }
}
