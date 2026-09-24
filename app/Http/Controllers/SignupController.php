<?php

namespace App\Http\Controllers;

use App\Models\SignupInvite;
use App\Models\Tenant;
use App\Services\Billing\SignupCheckout;
use App\Services\Billing\StripeGateway;
use App\Services\TenantProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

/**
 * SignupController — public tenant signup at the marketing apex
 * (ep.eiaawsolutions.com/signup). Pay-first, no free trial — same shape as
 * the Social Media Team signup.
 *
 *   GET  /signup?plan=            → details form (plan, period, headcount)
 *   POST /signup                  → validate + SignupInvite + Stripe Checkout (MYR)
 *   GET  /signup/checkout/success → record payment → set-password page
 *   GET  /signup/confirm/{token}  → password form (paid invites only)
 *   POST /signup/confirm/{token}  → provision tenant + redirect to subdomain login
 *
 * The checkout.session.completed webhook records the payment too (and emails
 * the set-password link) in case the browser never comes back from Stripe.
 *
 * The marketing apex is identified by the absence of a tenant subdomain.
 * If the request hits a tenant subdomain, signup is 404 (existing tenants
 * don't need the public signup).
 */
class SignupController extends Controller
{
    public function showForm(Request $request)
    {
        $this->ensureMarketingApex();

        $plan = $this->normalizePlan($request->query('plan'));

        // No plan picked yet → bounce to /pricing so the user makes an explicit
        // choice before handing over their email. /pricing CTAs link back here
        // with ?plan=starter|growth|scale.
        if ($plan === null) {
            return redirect()->route('marketing.pricing')
                ->with('signup_intent', 'choose_plan_first');
        }

        return view('signup.form', ['plan' => $plan]);
    }

    public function start(Request $request, SignupCheckout $checkout)
    {
        $this->ensureMarketingApex();

        $data = Validator::make($request->all(), [
            'work_email'   => ['required', 'email:rfc,dns', 'max:255'],
            'full_name'    => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'desired_slug' => [
                'required',
                'string',
                'min:3',
                'max:60',
                'regex:/^[a-z0-9](?:[a-z0-9-]{1,58}[a-z0-9])?$/',
            ],
            'plan'         => ['required', 'in:starter,growth,scale'],
            'period'       => ['required', 'in:monthly,annual'],
            'headcount'    => ['required', 'integer', 'min:1', 'max:5000'],
            'consent'      => ['accepted'],
        ], [
            'consent.accepted' => 'Please agree to the Terms of Service and Privacy Notice to continue.',
        ])->validate();

        $slug = strtolower($data['desired_slug']);

        // Already paid for this email but never set a password → resend the
        // set-password link; never overwrite a paid invite or charge twice.
        $paid = SignupInvite::where('work_email', $data['work_email'])
            ->whereNotNull('paid_at')->whereNull('confirmed_at')->first();
        if ($paid) {
            $mailSent = $checkout->sendSetPasswordLink($paid);
            return redirect()->route('signup.sent')
                ->with('signup_email', $paid->work_email)
                ->with('signup_plan', $paid->plan)
                ->with('signup_mail_sent', $mailSent);
        }

        // Single availability check — covers reserved list, format,
        // existing tenants (including soft-deleted), and pending invites.
        // Reserved-slug list lives in config/eiaaw.php so it can be
        // updated without redeploying controller code.
        if (!Tenant::isSlugAvailable($slug)) {
            return back()->withInput()->withErrors([
                'desired_slug' => 'That workspace URL is not available. Please choose another.',
            ]);
        }

        // If the same email already started a signup, refresh that invite
        // rather than creating a duplicate (idempotent on the form).
        $invite = SignupInvite::updateOrCreate(
            ['work_email' => $data['work_email']],
            [
                'full_name'         => $data['full_name'],
                'company_name'      => $data['company_name'],
                'desired_slug'      => $slug,
                'plan'              => $data['plan'],
                'billing_period'    => $data['period'],
                'seats'             => $checkout->quantity($data['plan'], (int) $data['headcount']),
                'confirmation_token' => Str::random(48),
                'expires_at'        => now()->addDay(),
                'signup_ip'         => $request->ip(),
                'signup_user_agent' => Str::limit($request->userAgent() ?? '', 500),
                'confirmed_at'      => null,
                'consent_at'        => now(),
                'consent_version'   => config('eiaaw.privacy_version'),
                'stripe_checkout_session_id' => null,
            ]
        );

        try {
            $url = $checkout->start(
                $invite,
                route('signup.checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
                route('signup.form', ['plan' => $invite->plan, 'canceled' => 1]),
            );
        } catch (\Throwable $e) {
            Log::error('signup.checkout_start_failed', [
                'invite_id' => $invite->id,
                'plan'      => $invite->plan,
                'error'     => $e->getMessage(),
            ]);
            report($e);
            return back()->withInput()->withErrors([
                'checkout' => 'We could not open checkout just now. Nothing was charged — please try again in a minute.',
            ]);
        }

        // Defensive allow-list: never redirect a customer to a non-Stripe host.
        $host = parse_url($url, PHP_URL_HOST);
        if (!in_array($host, ['checkout.stripe.com', 'billing.stripe.com'], true)) {
            Log::error('signup.checkout_non_stripe_url', ['host' => $host]);
            return back()->withInput()->withErrors([
                'checkout' => 'We could not open checkout just now. Nothing was charged — please try again in a minute.',
            ]);
        }

        return redirect()->away($url);
    }

    /**
     * Stripe success URL. Records the payment (idempotent with the webhook)
     * and sends the customer straight to set their password.
     */
    public function checkoutSuccess(Request $request, StripeGateway $stripe, SignupCheckout $checkout)
    {
        $this->ensureMarketingApex();

        $sessionId = (string) $request->query('session_id', '');
        if ($sessionId === '') {
            return redirect()->route('marketing.pricing');
        }

        try {
            $session = $stripe->retrieveCheckoutSession($sessionId);
        } catch (\Throwable $e) {
            Log::error('signup.checkout_retrieve_failed', ['session_id' => $sessionId, 'error' => $e->getMessage()]);
            return redirect()->route('marketing.pricing')->with('signup_intent', 'checkout_unverified');
        }

        $invite = $checkout->recordPayment($session);
        if (!$invite) {
            $plan = $this->normalizePlan($session['metadata']['plan'] ?? null) ?? 'growth';
            return redirect()->route('signup.form', ['plan' => $plan])
                ->withErrors(['checkout' => 'Payment was not completed, so no workspace was created. You can try again below.']);
        }

        return redirect()->route('signup.confirm', $invite->confirmation_token);
    }

    public function showSent()
    {
        $this->ensureMarketingApex();
        return view('signup.sent');
    }

    public function showConfirm(string $token)
    {
        $this->ensureMarketingApex();

        $invite = $this->findValidInvite($token);
        if (!$invite->isPaid()) {
            return $this->redirectToCheckout($invite);
        }

        return view('signup.confirm', compact('invite'));
    }

    public function confirm(Request $request, string $token, TenantProvisioner $provisioner)
    {
        $this->ensureMarketingApex();

        $invite = $this->findValidInvite($token);
        if (!$invite->isPaid()) {
            return $this->redirectToCheckout($invite);
        }

        $data = Validator::make($request->all(), [
            'password'     => ['required', 'string', 'min:12', 'confirmed'],
            // Only sent when the paid-for URL was taken before provisioning.
            'desired_slug' => ['sometimes', 'string', 'min:3', 'max:60', 'regex:/^[a-z0-9](?:[a-z0-9-]{1,58}[a-z0-9])?$/'],
        ])->validate();

        if (!empty($data['desired_slug'])) {
            $invite->update(['desired_slug' => strtolower($data['desired_slug'])]);
        }

        try {
            $tenant = $provisioner->provisionFromInvite($invite, $data['password']);
        } catch (\App\Exceptions\SlugUnavailableException $e) {
            // Race lost — the URL was taken between checkout and now. The
            // customer has already paid, so never send them back through
            // checkout: let them pick a new URL on this page.
            return redirect()->route('signup.confirm', $invite->confirmation_token)
                ->withErrors(['desired_slug' => $e->getMessage()]);
        }

        // Redirect to the new tenant's subdomain login. In local dev there
        // are no real subdomains; use the dev escape hatch (?tenant=slug)
        // so ResolveTenant binds correctly.
        $url = app()->environment('local')
            ? url('/login') . '?tenant=' . urlencode($tenant->slug)
            : $tenant->workspaceUrl('/login');

        return redirect($url)->with('success',
            'Workspace created — sign in with your work email.');
    }

    private function findValidInvite(string $token): SignupInvite
    {
        $invite = SignupInvite::where('confirmation_token', $token)->first();

        if (!$invite) {
            abort(404, 'Invitation not found or already consumed.');
        }

        if ($invite->isConfirmed()) {
            abort(410, 'This signup link has already been used.');
        }

        if ($invite->isExpired()) {
            abort(410, 'This signup link has expired. Please request a new one.');
        }

        return $invite;
    }

    /** Unpaid invites can't set a password — send them back to finish checkout. */
    private function redirectToCheckout(SignupInvite $invite)
    {
        return redirect()->route('signup.form', ['plan' => $invite->plan])
            ->withInput([
                'work_email'   => $invite->work_email,
                'full_name'    => $invite->full_name,
                'company_name' => $invite->company_name,
                'desired_slug' => $invite->desired_slug,
            ])
            ->withErrors(['checkout' => 'Payment for this workspace has not gone through yet. Complete checkout to continue.']);
    }

    /**
     * Normalize an incoming plan param. Returns the plan slug if valid,
     * null if missing/unrecognised. We never silently default to growth —
     * that hid the "user didn't actually choose a plan" bug.
     */
    private function normalizePlan(?string $plan): ?string
    {
        $plan = strtolower(trim((string) $plan));
        return in_array($plan, ['starter', 'growth', 'scale'], true) ? $plan : null;
    }

    private function ensureMarketingApex(): void
    {
        // Signup forms only exist at the marketing apex. If a request reached
        // here on a tenant subdomain, something is misrouted — 404.
        if (app()->bound('current_tenant')) {
            abort(404);
        }
    }
}
