<?php

namespace App\Http\Controllers;

use App\Mail\SignupConfirmationMail;
use App\Models\SignupInvite;
use App\Models\Tenant;
use App\Services\TenantProvisioner;
use Illuminate\Http\Request;
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
    private const RESERVED_SLUGS = [
        'app', 'admin', 'api', 'www', 'mail', 'static', 'assets', 'help',
        'support', 'status', 'docs', 'blog', 'about', 'pricing', 'features',
        'security', 'legal', 'terms', 'privacy', 'contact',
        'eiaaw', 'eiaaw-admin', 'workforce', 'system',
    ];

    public function showForm()
    {
        $this->ensureMarketingApex();
        return view('signup.form');
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
            'plan'         => ['nullable', 'in:starter,growth,scale'],
        ])->validate();

        $slug = strtolower($data['desired_slug']);

        if (in_array($slug, self::RESERVED_SLUGS, true)) {
            return back()->withInput()->withErrors([
                'desired_slug' => 'That workspace URL is reserved. Please choose another.',
            ]);
        }

        // Hard collision against existing tenants (live workspaces and
        // pending invites) — no enumeration leak: we tell the user it's
        // taken without revealing whether it's pending or live.
        if (Tenant::withTrashed()->where('slug', $slug)->exists()
            || SignupInvite::where('desired_slug', $slug)->exists()) {
            return back()->withInput()->withErrors([
                'desired_slug' => 'That workspace URL is already taken. Please choose another.',
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
                'plan'              => $data['plan'] ?? 'growth',
                'confirmation_token' => Str::random(48),
                'expires_at'        => now()->addDay(),
                'signup_ip'         => $request->ip(),
                'signup_user_agent' => Str::limit($request->userAgent() ?? '', 500),
                'confirmed_at'      => null,
            ]
        );

        // Send confirmation email — if Mailable doesn't exist yet (Wk2 design
        // pass), the queue will gracefully record the failure rather than 500.
        try {
            Mail::to($invite->work_email)->queue(new SignupConfirmationMail($invite));
        } catch (\Throwable $e) {
            // Don't expose mail errors to the user; the invite row is created.
            report($e);
        }

        return redirect()->route('signup.sent')
            ->with('signup_email', $invite->work_email);
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

        $tenant = $provisioner->provisionFromInvite($invite, $data['password']);

        // Build the redirect URL to the new tenant's subdomain.
        // In production: https://{slug}.ep.eiaawsolutions.com/login
        // In local dev: keep apex + ?tenant={slug} query param.
        $apex = config('eiaaw.tenant_domain');
        $url  = app()->environment('local')
            ? url('/login') . '?tenant=' . urlencode($tenant->slug)
            : 'https://' . $tenant->slug . '.' . $apex . '/login';

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

    private function ensureMarketingApex(): void
    {
        // Signup forms only exist at the marketing apex. If a request reached
        // here on a tenant subdomain, something is misrouted — 404.
        if (app()->bound('current_tenant')) {
            abort(404);
        }
    }
}
