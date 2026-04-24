<?php

namespace App\Http\Controllers;

use App\Models\SecurityAuditLog;
use App\Services\SsoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * SsoController — tenant-scoped OIDC + SAML sign-in.
 *
 * Runs on tenant subdomains only (not apex). The ResolveTenant middleware
 * binds app('current_tenant') from the subdomain; all endpoints here read
 * that and use the tenant's sso_config (JSON) for per-tenant IdP settings.
 *
 * Plan-gated to `auth.sso_saml` / `auth.sso_oidc` (Enterprise tier) via
 * route middleware; the config admin UI is separately plan-gated in
 * SsoConfigController.
 */
class SsoController extends Controller
{
    public function __construct(private readonly SsoService $sso) {}

    // ────────────────────────────────────────────────────────────────────
    // OIDC
    // ────────────────────────────────────────────────────────────────────

    public function oidcStart(Request $request)
    {
        $tenant = $this->tenant();
        try {
            $redirect = $this->sso->oidcAuthorizeUrl($tenant, route('sso.oidc.callback'), $request->session());
            return redirect()->away($redirect);
        } catch (RuntimeException $e) {
            return redirect()->route('login')->withErrors(['work_email' => 'SSO is not configured for this workspace.']);
        }
    }

    public function oidcCallback(Request $request)
    {
        $tenant = $this->tenant();
        $error = $request->query('error');
        if ($error) {
            $this->logFail('oidc_error', $request, "IdP returned error: {$error}");
            return $this->fail('Sign-in was cancelled at your identity provider.');
        }

        $code  = (string) $request->query('code', '');
        $state = (string) $request->query('state', '');
        if (!$code || !$state) {
            return $this->fail('Missing code or state in SSO callback.');
        }

        try {
            $claims = $this->sso->oidcHandleCallback(
                $tenant,
                $code,
                $state,
                route('sso.oidc.callback'),
                $request->session()
            );
        } catch (RuntimeException $e) {
            $this->logFail('oidc_verify_failed', $request, $e->getMessage());
            return $this->fail('We could not verify your sign-in. Please try again.');
        }

        return $this->completeLogin($tenant, $request, $claims['email'], $claims['name'], $claims['groups']);
    }

    // ────────────────────────────────────────────────────────────────────
    // SAML
    // ────────────────────────────────────────────────────────────────────

    /**
     * SP metadata XML — IdP admins paste this URL into their IdP to
     * register us as a Service Provider. No auth on the metadata endpoint
     * (metadata is non-sensitive by design — it's the public half).
     */
    public function samlMetadata()
    {
        $tenant = $this->tenant();
        $entityId = SsoService::spEntityId();
        $acs = route('sso.saml.acs');

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="{$entityId}">
    <md:SPSSODescriptor AuthnRequestsSigned="false" WantAssertionsSigned="true" protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        <md:NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress</md:NameIDFormat>
        <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="{$acs}" index="0"/>
    </md:SPSSODescriptor>
</md:EntityDescriptor>
XML;

        return response($xml, 200, ['Content-Type' => 'application/samlmetadata+xml']);
    }

    public function samlStart(Request $request)
    {
        $tenant = $this->tenant();
        try {
            $redirect = $this->sso->samlAuthnRedirect($tenant, route('sso.saml.acs'), $request->session());
            return redirect()->away($redirect);
        } catch (RuntimeException $e) {
            return redirect()->route('login')->withErrors(['work_email' => 'SAML is not configured for this workspace.']);
        }
    }

    public function samlAcs(Request $request)
    {
        $tenant = $this->tenant();
        $samlResponse = (string) $request->input('SAMLResponse', '');
        if (!$samlResponse) {
            return $this->fail('Missing SAMLResponse.');
        }

        try {
            $subject = $this->sso->samlHandleResponse($tenant, $samlResponse, $request->session());
        } catch (RuntimeException $e) {
            $this->logFail('saml_verify_failed', $request, $e->getMessage());
            return $this->fail('We could not verify your SAML sign-in.');
        }

        $groups = $subject['attributes']['groups'] ?? $subject['attributes']['http://schemas.xmlsoap.org/claims/Group'] ?? [];

        return $this->completeLogin($tenant, $request, $subject['email'], $subject['name'], (array) $groups);
    }

    // ────────────────────────────────────────────────────────────────────
    // shared
    // ────────────────────────────────────────────────────────────────────

    private function completeLogin($tenant, Request $request, string $email, string $name, array $groups)
    {
        $user = $this->sso->findOrProvisionUser($tenant, $email, $name, $groups);
        if (!$user) {
            $this->logFail('sso_user_not_matched', $request, "Email {$email} did not match existing user and JIT is disabled or domain not allowed");
            return $this->fail("We could not match that email to an account in this workspace.");
        }

        if (!$user->is_active) {
            $this->logFail('sso_deactivated_user', $request, "SSO sign-in attempt on deactivated account {$email}");
            return $this->fail('Your account is deactivated. Please contact your workspace admin.');
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        // Align with EnforceSingleSession — rotate session token just like password login.
        if (method_exists($user, 'update')) {
            $user->update(['session_token' => bin2hex(random_bytes(16))]);
            $request->session()->put('user_session_token', $user->session_token);
        }

        SecurityAuditLog::record('sso_login', [
            'user_id' => $user->id,
            'work_email' => $user->work_email,
            'role' => $user->role,
            'ip_address' => $request->ip(),
            'details' => 'Signed in via SSO',
        ]);

        // Land on the role-appropriate dashboard.
        $redirect = match ($user->role) {
            'hr_manager', 'hr_executive', 'hr_intern' => route('hr.dashboard'),
            'it_manager', 'it_executive', 'it_intern' => route('it.dashboard'),
            'superadmin', 'system_admin' => route('superadmin.system-overview'),
            default => route('user.dashboard'),
        };

        return redirect()->intended($redirect);
    }

    private function tenant()
    {
        if (!app()->bound('current_tenant')) {
            abort(404);
        }
        return app('current_tenant');
    }

    private function fail(string $msg)
    {
        return redirect()->route('login')->withErrors(['work_email' => $msg]);
    }

    private function logFail(string $eventType, Request $request, string $detail): void
    {
        SecurityAuditLog::record($eventType, [
            'ip_address' => $request->ip(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'details' => $detail,
        ]);
    }
}
