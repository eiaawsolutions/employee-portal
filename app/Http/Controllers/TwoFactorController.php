<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    // ── Setup: show QR code ───────────────────────────────────────────────
    public function setup(Request $request)
    {
        $user = $request->user();
        $google2fa = new Google2FA();

        $secret = $google2fa->generateSecretKey();

        // Store encrypted secret temporarily in session until confirmed
        $request->session()->put('2fa_setup_secret', Crypt::encryptString($secret));

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->work_email,
            $secret
        );

        return view('auth.two-factor-setup', [
            'secret'    => $secret,
            'qrCodeUrl' => $qrCodeUrl,
        ]);
    }

    // ── Confirm: verify code and enable 2FA ───────────────────────────────
    public function confirm(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);

        $encryptedSecret = $request->session()->get('2fa_setup_secret');
        if (!$encryptedSecret) {
            return redirect()->route('profile')->with('error', 'Two-factor setup session expired. Please try again.');
        }

        $secret = Crypt::decryptString($encryptedSecret);
        $google2fa = new Google2FA();

        if (!$google2fa->verifyKey($secret, $request->code, 2)) {
            return back()->withErrors(['code' => 'Invalid verification code. Please try again.']);
        }

        // Generate recovery codes
        $recoveryCodes = collect(range(1, 8))->map(fn () => Str::random(10))->toArray();

        $request->user()->update([
            'two_factor_secret'         => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($recoveryCodes)),
            'two_factor_confirmed_at'   => now(),
        ]);

        $request->session()->forget('2fa_setup_secret');

        return view('auth.two-factor-recovery-codes', [
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    // ── Disable 2FA ───────────────────────────────────────────────────────
    public function disable(Request $request)
    {
        if ($request->user()->requiresTwoFactor()) {
            return back()->withErrors(['password' => 'Two-factor authentication is required for your role and cannot be disabled.']);
        }

        $request->validate(['password' => 'required']);

        if (!Auth::validate(['work_email' => $request->user()->work_email, 'password' => $request->password])) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $request->user()->update([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ]);

        return redirect()->route('profile')->with('success', 'Two-factor authentication has been disabled.');
    }

    // ── Challenge page (shown after password login) ───────────────────────
    public function challenge()
    {
        if (!session('2fa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    // ── Verify challenge ──────────────────────────────────────────────────
    public function verify(Request $request)
    {
        $request->validate([
            'code'          => 'nullable|digits:6',
            'recovery_code' => 'nullable|string',
        ]);

        $userId = session('2fa_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = \App\Models\User::findOrFail($userId);
        $secret = Crypt::decryptString($user->two_factor_secret);

        // TOTP code verification (window=2 allows ±60 s clock drift)
        if ($request->filled('code')) {
            $google2fa = new Google2FA();
            if (!$google2fa->verifyKey($secret, $request->code, 2)) {
                return back()->withErrors(['code' => 'Invalid authentication code.']);
            }
        }
        // Recovery code verification
        elseif ($request->filled('recovery_code')) {
            $codes = json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true);
            $index = array_search($request->recovery_code, $codes);

            if ($index === false) {
                return back()->withErrors(['recovery_code' => 'Invalid recovery code.']);
            }

            // Consume the recovery code
            unset($codes[$index]);
            $user->update([
                'two_factor_recovery_codes' => Crypt::encryptString(json_encode(array_values($codes))),
            ]);
        } else {
            return back()->withErrors(['code' => 'Please enter a verification code or recovery code.']);
        }

        // Complete login
        Auth::login($user, session('2fa_remember', false));
        $request->session()->regenerate();

        $token = Str::random(60);
        $user->update(['session_token' => $token, 'login_attempts' => 0]);
        session(['_single_session_token' => $token]);

        // Clean up 2FA session data
        $request->session()->forget(['2fa_user_id', '2fa_remember', '2fa_redirect']);

        $redirect = session('2fa_redirect');
        if ($redirect) {
            return $this->bounceToTenantSubdomain($request, $user, $redirect);
        }

        if ($user->isHr() || $user->isSuperadmin() || $user->isSystemAdmin()) {
            return $this->bounceToTenantSubdomain($request, $user, route('hr.dashboard'));
        }
        if ($user->isIt()) {
            return $this->bounceToTenantSubdomain($request, $user, route('it.dashboard'));
        }

        return $this->bounceToTenantSubdomain($request, $user, route('user.dashboard'));
    }

    /**
     * Mirror of AuthController::redirectAfterLogin — if the request arrived
     * on the marketing apex (ep.eiaawsolutions.com) or bare root, rewrite
     * the redirect target to the user's tenant subdomain so plan-gated
     * routes have a current_tenant bound by ResolveTenant.
     *
     * Session cookie is scoped to .eiaawsolutions.com (leading dot) so the
     * user stays logged in across the redirect.
     */
    private function bounceToTenantSubdomain(Request $request, \App\Models\User $user, string $destinationUrl): \Illuminate\Http\RedirectResponse
    {
        $marketingHost = strtolower(config('eiaaw.marketing_host', env('APP_MARKETING_HOST', 'ep.eiaawsolutions.com')));
        $tenantDomain  = strtolower(config('eiaaw.tenant_domain',  env('APP_TENANT_DOMAIN',  'eiaawsolutions.com')));
        $currentHost   = strtolower($request->getHost());

        if ($currentHost !== $marketingHost && $currentHost !== $tenantDomain) {
            return redirect($destinationUrl);
        }

        $slug = \App\Models\Tenant::where('id', $user->tenant_id)
            ->withTrashed()
            ->value('slug');

        if (!$slug) {
            return redirect()->route('marketing.find-workspace');
        }

        $parts  = parse_url($destinationUrl);
        $path   = $parts['path']  ?? '/';
        $query  = isset($parts['query']) ? '?' . $parts['query'] : '';
        $scheme = $parts['scheme'] ?? 'https';

        return redirect("{$scheme}://{$slug}.{$tenantDomain}{$path}{$query}");
    }
}
