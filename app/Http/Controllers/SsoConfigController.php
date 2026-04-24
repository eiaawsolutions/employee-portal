<?php

namespace App\Http\Controllers;

use App\Services\SsoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * SsoConfigController — workspace-admin UI to configure per-tenant SSO.
 *
 * Enterprise-only, workspace-owner-only. Writes to tenants.sso_config (JSON).
 *
 * Route gating handles plan check; controller re-checks owner authorisation
 * because the plan alone doesn't imply the asking user should edit identity
 * infrastructure for the whole workspace.
 */
class SsoConfigController extends Controller
{
    public function show(Request $request)
    {
        $tenant = $this->tenant();
        $this->assertOwner($request->user(), $tenant);

        return view('superadmin.sso-config', [
            'tenant' => $tenant,
            'config' => $tenant->sso_config ?? [],
            'spEntityId' => SsoService::spEntityId(),
            'samlAcsUrl' => route('sso.saml.acs'),
            'oidcCallbackUrl' => route('sso.oidc.callback'),
        ]);
    }

    public function update(Request $request)
    {
        $tenant = $this->tenant();
        $this->assertOwner($request->user(), $tenant);

        $data = Validator::make($request->all(), [
            'jit_provision'   => ['nullable', 'boolean'],
            'allowed_domains' => ['nullable', 'string', 'max:2000'],

            // OIDC
            'oidc_enabled'        => ['nullable', 'boolean'],
            'oidc_issuer'         => ['nullable', 'url', 'max:500'],
            'oidc_client_id'      => ['nullable', 'string', 'max:500'],
            'oidc_client_secret'  => ['nullable', 'string', 'max:500'],
            'oidc_scopes'         => ['nullable', 'string', 'max:200'],
            'oidc_role_mapping'   => ['nullable', 'string', 'max:2000'],

            // SAML
            'saml_enabled'       => ['nullable', 'boolean'],
            'saml_idp_entity_id' => ['nullable', 'string', 'max:500'],
            'saml_idp_sso_url'   => ['nullable', 'url', 'max:500'],
            'saml_idp_cert_pem'  => ['nullable', 'string', 'max:8000'],
            'saml_role_mapping'  => ['nullable', 'string', 'max:2000'],
        ])->validate();

        // Coerce role mappings (newline-separated "group=role" pairs) into arrays.
        $oidcRoleMapping = $this->parseRoleMapping($data['oidc_role_mapping'] ?? '');
        $samlRoleMapping = $this->parseRoleMapping($data['saml_role_mapping'] ?? '');

        $allowedDomains = array_values(array_filter(array_map('trim', explode(',', $data['allowed_domains'] ?? ''))));

        // Merge with existing so partial updates don't destroy the other half.
        $existing = $tenant->sso_config ?? [];

        // Preserve the existing secret when the form field is blank (don't overwrite
        // a valid secret with an empty string when the user is only editing OIDC issuer).
        $existingSecret = data_get($existing, 'oidc.client_secret');
        $newSecret = !empty($data['oidc_client_secret']) ? $data['oidc_client_secret'] : $existingSecret;

        $config = [
            'jit_provision' => (bool) ($data['jit_provision'] ?? false),
            'allowed_domains' => $allowedDomains,
            'oidc' => [
                'enabled'       => (bool) ($data['oidc_enabled'] ?? false),
                'issuer'        => $data['oidc_issuer']        ?? null,
                'client_id'     => $data['oidc_client_id']     ?? null,
                'client_secret' => $newSecret,
                'scopes'        => $data['oidc_scopes']        ?? 'openid profile email',
                'role_mapping'  => $oidcRoleMapping,
            ],
            'saml' => [
                'enabled'        => (bool) ($data['saml_enabled'] ?? false),
                'idp_entity_id'  => $data['saml_idp_entity_id'] ?? null,
                'idp_sso_url'    => $data['saml_idp_sso_url']   ?? null,
                'idp_cert_pem'   => $data['saml_idp_cert_pem']  ?? null,
                'role_mapping'   => $samlRoleMapping,
            ],
        ];

        // Basic sanity — if either provider is enabled, at least one flow must be complete.
        if ($config['oidc']['enabled'] && (!$config['oidc']['issuer'] || !$config['oidc']['client_id'])) {
            return back()->withInput()->withErrors(['oidc_enabled' => 'OIDC requires issuer and client_id.']);
        }
        if ($config['saml']['enabled'] && (!$config['saml']['idp_sso_url'] || !$config['saml']['idp_cert_pem'])) {
            return back()->withInput()->withErrors(['saml_enabled' => 'SAML requires IdP SSO URL and X.509 certificate.']);
        }
        if ($config['saml']['enabled']) {
            // Reject certs that don't look like certs — quick prefix check prevents
            // users pasting private keys or PKCS#1 public keys by mistake.
            if (!str_contains($config['saml']['idp_cert_pem'], 'BEGIN CERTIFICATE')) {
                return back()->withInput()->withErrors(['saml_idp_cert_pem' => 'Paste the PEM-encoded X.509 certificate including BEGIN/END markers.']);
            }
        }

        $tenant->update([
            'sso_enabled' => $config['oidc']['enabled'] || $config['saml']['enabled'],
            'sso_config' => $config,
        ]);

        Log::info('sso_config.updated', [
            'tenant_id' => $tenant->id,
            'slug' => $tenant->slug,
            'updated_by' => $request->user()->id,
            'oidc_enabled' => $config['oidc']['enabled'],
            'saml_enabled' => $config['saml']['enabled'],
        ]);

        return back()->with('success', 'SSO configuration saved.');
    }

    private function parseRoleMapping(string $raw): array
    {
        $map = [];
        foreach (preg_split('/\r?\n/', $raw) as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (!str_contains($line, '=')) continue;
            [$group, $role] = array_map('trim', explode('=', $line, 2));
            if ($group === '' || $role === '') continue;
            // Block SSO→superadmin mapping at input time too (defence-in-depth with SsoService).
            if ($role === 'superadmin') continue;
            $map[$group] = $role;
        }
        return $map;
    }

    private function tenant()
    {
        if (!app()->bound('current_tenant')) {
            abort(403, 'SSO config requires an active tenant context.');
        }
        return app('current_tenant');
    }

    private function assertOwner($user, $tenant): void
    {
        if (!$user) {
            abort(401);
        }
        if (in_array($user->role, ['superadmin', 'system_admin'], true)) {
            return;
        }
        $isOwner = $tenant->users()
            ->where('users.id', $user->id)
            ->wherePivot('tenant_role', 'owner')
            ->exists();
        if (!$isOwner) {
            abort(403, 'Only workspace owners can configure SSO.');
        }
    }
}
