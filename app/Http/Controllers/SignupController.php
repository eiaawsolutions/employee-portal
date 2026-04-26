<?php

namespace App\Http\Controllers;

use App\Mail\SignupConfirmationMail;
use App\Models\SignupInvite;
use App\Models\Tenant;
use App\Services\TenantProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

/**
 * SignupController — public tenant signup at the marketing apex
 * (ep.eiaawsolutions.com/signup).
 *
 * Three-step flow:
 *   GET  /signup                 → form
 *   POST /signup                 → validate + create SignupInvite + email token
 *   GET  /signup/confirm/{token} → password form
 *   POST /signup/confirm/{token} → provision tenant + redirect to subdomain dashboard
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

    public function start(Request $request, TenantProvisioner $provisioner)
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
        ])->validate();

        $slug = strtolower($data['desired_slug']);

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
                'confirmation_token' => Str::random(48),
                'expires_at'        => now()->addDay(),
                'signup_ip'         => $request->ip(),
                'signup_user_agent' => Str::limit($request->userAgent() ?? '', 500),
                'confirmed_at'      => null,
            ]
        );

        // Send confirmation email. Failures here are user-blocking (no email →
        // no way to confirm), so we log full context and tell the user instead
        // of pretending the email went out.
        $mailSent = true;
        try {
            Mail::to($invite->work_email)->send(new SignupConfirmationMail($invite));
        } catch (\Throwable $e) {
            $mailSent = false;
            Log::error('signup.confirmation_mail_failed', [
                'invite_id'  => $invite->id,
                'work_email' => $invite->work_email,
                'plan'       => $invite->plan,
                'mailer'     => config('mail.default'),
                'host'       => config('mail.mailers.smtp.host'),
                'from'       => config('mail.from.address'),
                'error'      => $e->getMessage(),
                'class'      => get_class($e),
            ]);
            report($e);
        }

        return redirect()->route('signup.sent')
            ->with('signup_email', $invite->work_email)
            ->with('signup_plan', $invite->plan)
            ->with('signup_mail_sent', $mailSent);
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
        return view('signup.confirm', compact('invite'));
    }

    public function confirm(Request $request, string $token, TenantProvisioner $provisioner)
    {
        $this->ensureMarketingApex();

        $invite = $this->findValidInvite($token);

        $data = Validator::make($request->all(), [
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ])->validate();

        try {
            $tenant = $provisioner->provisionFromInvite($invite, $data['password']);
        } catch (\App\Exceptions\SlugUnavailableException $e) {
            // Race lost — another signup grabbed the slug between the form
            // submission and provisioning. Send the user back to the
            // signup form to pick a fresh slug; the original invite is
            // unconsumed so they can retry without losing email/name.
            return redirect()->route('signup.form', ['plan' => $invite->plan])
                ->withInput([
                    'work_email'   => $invite->work_email,
                    'full_name'    => $invite->full_name,
                    'company_name' => $invite->company_name,
                    'plan'         => $invite->plan,
                ])
                ->withErrors(['desired_slug' => $e->getMessage()]);
        }

        // Redirect to the new tenant's subdomain login. In local dev there
        // are no real subdomains; use the dev escape hatch (?tenant=slug)
        // so ResolveTenant binds correctly.
        $url = app()->environment('local')
            ? url('/login') . '?tenant=' . urlencode($tenant->slug)
            : $tenant->workspaceUrl('/login');

        return redirect($url)->with('success',
            'Workspace created — sign in with your work email to start the trial.');
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
