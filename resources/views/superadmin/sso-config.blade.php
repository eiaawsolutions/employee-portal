@extends('layouts.app')

@section('title', 'SSO configuration · Enterprise')
@section('page-title', 'SSO configuration')

@section('content')
<style>
    .sso-wrap {
        max-width: 880px; margin: 32px auto;
        padding: clamp(28px, 4vw, 48px);
        background: var(--surface, #FFFFFF);
        border: 1px solid var(--line-soft, #E8DFCC);
        border-radius: 20px;
    }
    .sso-wrap h1 {
        font-family: var(--sans, 'Inter', sans-serif);
        font-weight: 500; font-size: clamp(24px, 2.6vw, 32px);
        letter-spacing: -0.02em; margin: 14px 0 8px;
    }
    .sso-wrap h1 em {
        font-family: var(--serif, 'Instrument Serif', serif);
        font-style: italic; font-weight: 400;
        color: var(--primary-dark, #11766A);
    }
    .sso-pill {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 5px 12px; border-radius: 999px;
        background: var(--primary-tint, #E5F4F1); color: var(--primary-dark, #11766A);
        font-family: var(--mono, monospace); font-size: 11px;
        text-transform: uppercase; letter-spacing: 0.12em; font-weight: 500;
    }
    .sso-pill::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: var(--primary, #1FA896); }

    .sso-lede { color: var(--ink-2); font-size: 14.5px; line-height: 1.55; margin: 0 0 28px; }

    .sso-tabs {
        display: flex; gap: 0; margin: 20px 0 28px;
        border-bottom: 1px solid var(--line-soft);
    }
    .sso-tab {
        padding: 12px 20px; border: 0; background: transparent;
        cursor: pointer; font-family: var(--sans); font-size: 14px; font-weight: 500;
        color: var(--mute); letter-spacing: -0.005em;
        border-bottom: 2px solid transparent;
        transition: color 0.2s var(--ease, cubic-bezier(.2,.7,.2,1)), border-color 0.2s var(--ease, cubic-bezier(.2,.7,.2,1));
    }
    .sso-tab[aria-selected="true"] { color: var(--ink); border-bottom-color: var(--primary); }

    .sso-panel[hidden] { display: none; }

    .sso-field { margin-bottom: 18px; }
    .sso-field label {
        display: block; font-size: 13px; font-weight: 500;
        color: var(--ink-2); margin-bottom: 6px;
    }
    .sso-field input[type=text],
    .sso-field input[type=url],
    .sso-field input[type=password],
    .sso-field textarea {
        width: 100%; box-sizing: border-box;
        padding: 10px 14px; border: 1px solid var(--line, #D9CFBC); border-radius: 10px;
        background: var(--surface); color: var(--ink);
        font-family: var(--sans); font-size: 14px;
    }
    .sso-field textarea { resize: vertical; min-height: 80px; font-family: var(--mono, monospace); font-size: 12.5px; }
    .sso-field .hint {
        font-family: var(--mono, monospace); font-size: 11px;
        color: var(--mute); margin-top: 4px; letter-spacing: 0.04em;
    }
    .sso-field .error { font-size: 12.5px; color: var(--danger, #B4412B); margin-top: 4px; }

    .sso-check {
        display: flex; align-items: center; gap: 10px;
        padding: 12px 14px; border-radius: 10px;
        background: var(--bg-warm, #F3EDE0);
        margin-bottom: 18px;
    }
    .sso-check label { font-size: 13.5px; color: var(--ink); }

    .sso-info {
        background: var(--bg-warm);
        border: 1px solid var(--line-soft);
        border-radius: 10px; padding: 14px 16px;
        font-family: var(--mono, monospace); font-size: 12px;
        color: var(--ink-2);
        margin-bottom: 20px; line-height: 1.6;
    }
    .sso-info strong { color: var(--primary-dark); font-weight: 500; }

    .sso-actions { display: flex; gap: 12px; padding-top: 20px; border-top: 1px solid var(--line-soft); }
    .sso-btn {
        padding: 11px 20px; border-radius: 999px;
        font-family: var(--sans); font-size: 13.5px; font-weight: 500;
        cursor: pointer; border: 1px solid transparent;
    }
    .sso-btn-primary { background: var(--ink); color: var(--bg); border-color: var(--ink); }
    .sso-btn-primary:hover { background: var(--primary-dark); border-color: var(--primary-dark); }
</style>

<div class="sso-wrap">
    <span class="sso-pill">Enterprise only</span>
    <h1>Single sign-on <em>configuration.</em></h1>
    <p class="sso-lede">
        Bring your own identity provider. Configure either OpenID Connect or SAML 2.0;
        users sign in through your IdP and EIAAW Workforce provisions accounts on demand.
    </p>

    @if($errors->any())
        <div style="background: rgba(180,65,43,0.08); border: 1px solid rgba(180,65,43,0.3); color: var(--danger, #B4412B); padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 13.5px;">
            @foreach($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
    @endif

    @if(session('success'))
        <div style="background: rgba(47,140,110,0.08); border: 1px solid rgba(47,140,110,0.3); color: var(--success, #2F8C6E); padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 13.5px;">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('superadmin.sso.update') }}">
        @csrf

        <div class="sso-info">
            <strong>JIT provisioning:</strong> when enabled, the first SSO sign-in from a new user creates
            their workspace account automatically. Disable to require every user be pre-created.
        </div>

        <div class="sso-check">
            <input type="checkbox" name="jit_provision" id="jit_provision" value="1" {{ ($config['jit_provision'] ?? false) ? 'checked' : '' }}>
            <label for="jit_provision">Enable JIT provisioning for SSO sign-ins</label>
        </div>

        <div class="sso-field">
            <label for="allowed_domains">Allowed email domains (comma-separated)</label>
            <input type="text" name="allowed_domains" id="allowed_domains"
                   value="{{ implode(', ', (array) ($config['allowed_domains'] ?? [])) }}"
                   placeholder="acmecorp.com, acme.asia">
            <div class="hint">If set, only emails from these domains can sign in via SSO. Leave blank to allow any domain your IdP asserts.</div>
        </div>

        <div class="sso-tabs" role="tablist">
            <button type="button" class="sso-tab" data-tab="oidc" aria-selected="true" role="tab">OpenID Connect</button>
            <button type="button" class="sso-tab" data-tab="saml" aria-selected="false" role="tab">SAML 2.0</button>
        </div>

        {{-- OIDC panel --}}
        <div class="sso-panel" data-panel="oidc" role="tabpanel">
            <div class="sso-info">
                <strong>Redirect URI (share with IdP):</strong><br>
                <code>{{ $oidcCallbackUrl }}</code>
            </div>

            <div class="sso-check">
                <input type="checkbox" name="oidc_enabled" id="oidc_enabled" value="1" {{ data_get($config, 'oidc.enabled') ? 'checked' : '' }}>
                <label for="oidc_enabled">Enable OIDC sign-in for this workspace</label>
            </div>

            <div class="sso-field">
                <label for="oidc_issuer">Issuer URL</label>
                <input type="url" name="oidc_issuer" id="oidc_issuer" value="{{ data_get($config, 'oidc.issuer') }}" placeholder="https://login.microsoftonline.com/<tenant-id>/v2.0">
                <div class="hint">Must support /.well-known/openid-configuration discovery.</div>
            </div>

            <div class="sso-field">
                <label for="oidc_client_id">Client ID</label>
                <input type="text" name="oidc_client_id" id="oidc_client_id" value="{{ data_get($config, 'oidc.client_id') }}">
            </div>

            <div class="sso-field">
                <label for="oidc_client_secret">Client secret</label>
                <input type="password" name="oidc_client_secret" id="oidc_client_secret" value="" placeholder="{{ data_get($config, 'oidc.client_secret') ? '•••••••• (leave blank to keep)' : '' }}">
                <div class="hint">Stored encrypted at rest. Leave blank to preserve the current value.</div>
            </div>

            <div class="sso-field">
                <label for="oidc_scopes">Scopes</label>
                <input type="text" name="oidc_scopes" id="oidc_scopes" value="{{ data_get($config, 'oidc.scopes', 'openid profile email') }}">
                <div class="hint">Space-separated. "openid" is required; add "groups" if your IdP supplies them.</div>
            </div>

            <div class="sso-field">
                <label for="oidc_role_mapping">Role mapping (one per line, <code>group=role</code>)</label>
                <textarea name="oidc_role_mapping" id="oidc_role_mapping" placeholder="admin=hr_manager&#10;it-team=it_manager&#10;default=employee">{{ collect((array) data_get($config, 'oidc.role_mapping', []))->map(fn($r, $g) => "{$g}={$r}")->implode("\n") }}</textarea>
                <div class="hint">Valid roles: employee, hr_intern, hr_executive, hr_manager, it_intern, it_executive, it_manager. "superadmin" is blocked via SSO.</div>
            </div>
        </div>

        {{-- SAML panel --}}
        <div class="sso-panel" data-panel="saml" role="tabpanel" hidden>
            <div class="sso-info">
                <strong>Service Provider metadata (share with IdP):</strong><br>
                Entity ID: <code>{{ $spEntityId }}</code><br>
                ACS URL: <code>{{ $samlAcsUrl }}</code><br>
                NameID format: <code>urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress</code><br>
                Metadata XML: <code>{{ url('/sso/saml/metadata') }}</code>
            </div>

            <div class="sso-check">
                <input type="checkbox" name="saml_enabled" id="saml_enabled" value="1" {{ data_get($config, 'saml.enabled') ? 'checked' : '' }}>
                <label for="saml_enabled">Enable SAML sign-in for this workspace</label>
            </div>

            <div class="sso-field">
                <label for="saml_idp_entity_id">IdP entity ID</label>
                <input type="text" name="saml_idp_entity_id" id="saml_idp_entity_id" value="{{ data_get($config, 'saml.idp_entity_id') }}" placeholder="https://sts.windows.net/<tenant-id>/">
            </div>

            <div class="sso-field">
                <label for="saml_idp_sso_url">IdP SSO URL (HTTP-Redirect or HTTP-POST binding)</label>
                <input type="url" name="saml_idp_sso_url" id="saml_idp_sso_url" value="{{ data_get($config, 'saml.idp_sso_url') }}" placeholder="https://login.microsoftonline.com/<tenant-id>/saml2">
            </div>

            <div class="sso-field">
                <label for="saml_idp_cert_pem">IdP X.509 certificate (PEM, with BEGIN/END markers)</label>
                <textarea name="saml_idp_cert_pem" id="saml_idp_cert_pem" rows="6" placeholder="-----BEGIN CERTIFICATE-----&#10;MIID...&#10;-----END CERTIFICATE-----">{{ data_get($config, 'saml.idp_cert_pem') }}</textarea>
                <div class="hint">We verify every SAMLResponse signature against this certificate.</div>
            </div>

            <div class="sso-field">
                <label for="saml_role_mapping">Role mapping (one per line, <code>group=role</code>)</label>
                <textarea name="saml_role_mapping" id="saml_role_mapping" placeholder="hr-admins=hr_manager&#10;default=employee">{{ collect((array) data_get($config, 'saml.role_mapping', []))->map(fn($r, $g) => "{$g}={$r}")->implode("\n") }}</textarea>
                <div class="hint">"superadmin" is blocked via SSO for security.</div>
            </div>
        </div>

        <div class="sso-actions">
            <button type="submit" class="sso-btn sso-btn-primary">Save configuration</button>
        </div>
    </form>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    var tabs = document.querySelectorAll('.sso-tab');
    var panels = document.querySelectorAll('.sso-panel');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var target = tab.getAttribute('data-tab');
            tabs.forEach(function (t) { t.setAttribute('aria-selected', t === tab ? 'true' : 'false'); });
            panels.forEach(function (p) {
                if (p.getAttribute('data-panel') === target) p.removeAttribute('hidden');
                else p.setAttribute('hidden', '');
            });
        });
    });
})();
</script>
@endsection
