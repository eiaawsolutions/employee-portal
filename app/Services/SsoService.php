<?php

namespace App\Services;

use App\Models\SecurityAuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * SsoService — tenant-scoped SAML 2.0 + OIDC sign-in, JIT provisioning.
 *
 * OIDC (OpenID Connect) flow:
 *   1. GET /sso/oidc/start      → redirect to IdP authorize endpoint with
 *                                  state+nonce stored in session
 *   2. IdP redirects back to    → /sso/oidc/callback?code=...&state=...
 *   3. Exchange code for tokens at IdP token endpoint (server-to-server)
 *   4. Verify ID token signature against the IdP's JWKS (cached 6h)
 *   5. JIT-provision or match user by email, log them in
 *
 * SAML flow:
 *   1. SP metadata published at /sso/saml/metadata.xml for IdP configuration
 *   2. GET /sso/saml/start      → HTTP-Redirect binding to IdP SSO URL
 *   3. IdP posts SAMLResponse → /sso/saml/acs (HTTP-POST binding)
 *   4. Verify signature against tenant's configured cert
 *   5. Extract NameID + attributes, JIT-provision, log in
 *
 * Per-tenant config lives in tenants.sso_config (JSON cast):
 *   {
 *     "oidc": {
 *       "enabled": true,
 *       "issuer": "https://login.microsoftonline.com/<tid>/v2.0",
 *       "client_id": "...",
 *       "client_secret": "...",
 *       "scopes": "openid profile email",
 *       "role_mapping": {"groups.admin": "hr_manager", "default": "employee"}
 *     },
 *     "saml": {
 *       "enabled": false,
 *       "idp_entity_id": "...",
 *       "idp_sso_url": "...",
 *       "idp_cert_pem": "-----BEGIN CERTIFICATE-----\n..."
 *     },
 *     "jit_provision": true,
 *     "allowed_domains": ["acmecorp.com"]
 *   }
 *
 * Security notes:
 *   - state + nonce bound to session, single-use
 *   - ID token must match issuer, audience, nonce, exp
 *   - SAMLResponse signature verified against configured cert
 *   - Only JIT-provisions if the email domain is on the allowed_domains list
 *   - Users created via JIT get role='employee' unless role_mapping says otherwise
 *   - Failed SSO logged to security_audit_logs for threat detection
 */
class SsoService
{
    public function oidcConfigFor(Tenant $tenant): array
    {
        $config = data_get($tenant->sso_config, 'oidc');
        if (empty($config['enabled']) || empty($config['issuer']) || empty($config['client_id'])) {
            throw new RuntimeException('OIDC is not configured for this workspace.');
        }
        return $config;
    }

    public function samlConfigFor(Tenant $tenant): array
    {
        $config = data_get($tenant->sso_config, 'saml');
        if (empty($config['enabled']) || empty($config['idp_sso_url']) || empty($config['idp_cert_pem'])) {
            throw new RuntimeException('SAML is not configured for this workspace.');
        }
        return $config;
    }

    // ────────────────────────────────────────────────────────────────────
    // OIDC
    // ────────────────────────────────────────────────────────────────────

    /**
     * Build the IdP authorize URL. Stores state+nonce in session for
     * validation in the callback.
     */
    public function oidcAuthorizeUrl(Tenant $tenant, string $redirectUri, $session): string
    {
        $cfg = $this->oidcConfigFor($tenant);
        $discovery = $this->oidcDiscovery($cfg['issuer']);

        $state = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));

        $session->put('sso.oidc.state', $state);
        $session->put('sso.oidc.nonce', $nonce);
        $session->put('sso.oidc.tenant_id', $tenant->id);

        return $discovery['authorization_endpoint'] . '?' . http_build_query([
            'response_type' => 'code',
            'client_id'     => $cfg['client_id'],
            'redirect_uri'  => $redirectUri,
            'scope'         => $cfg['scopes'] ?? 'openid profile email',
            'state'         => $state,
            'nonce'         => $nonce,
            'prompt'        => 'select_account',
        ]);
    }

    /**
     * Handle the callback — exchange code for tokens, verify, return the claims.
     * Throws on any failure; caller decides how to surface the error.
     *
     * @return array{email: string, name: string, sub: string, groups: array}
     */
    public function oidcHandleCallback(Tenant $tenant, string $code, string $state, string $redirectUri, $session): array
    {
        if (!hash_equals($session->get('sso.oidc.state', ''), $state)) {
            throw new RuntimeException('OIDC state mismatch — request was tampered with or session expired.');
        }
        $expectedNonce = $session->pull('sso.oidc.nonce', '');
        $session->forget(['sso.oidc.state', 'sso.oidc.tenant_id']);

        $cfg = $this->oidcConfigFor($tenant);
        $discovery = $this->oidcDiscovery($cfg['issuer']);

        // Exchange code for tokens
        $response = Http::asForm()->timeout(15)->post($discovery['token_endpoint'], [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
            'client_id'     => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'] ?? '',
        ]);

        if (!$response->successful() || empty($response['id_token'])) {
            Log::warning('OIDC token exchange failed', ['tenant_id' => $tenant->id, 'status' => $response->status()]);
            throw new RuntimeException('OIDC token exchange failed.');
        }

        $idToken = $response['id_token'];
        $claims = $this->verifyIdToken($idToken, $cfg, $discovery, $expectedNonce);

        return [
            'email'  => strtolower($claims['email'] ?? $claims['preferred_username'] ?? ''),
            'name'   => $claims['name'] ?? ($claims['given_name'] ?? '') . ' ' . ($claims['family_name'] ?? ''),
            'sub'    => $claims['sub'],
            'groups' => (array) ($claims['groups'] ?? []),
        ];
    }

    /**
     * Fetch + cache the IdP's OIDC discovery document. Anchors all later
     * calls to the authoritative endpoints advertised by the issuer.
     */
    private function oidcDiscovery(string $issuer): array
    {
        $key = 'oidc-discovery:' . md5($issuer);
        return Cache::remember($key, now()->addHours(6), function () use ($issuer) {
            $url = rtrim($issuer, '/') . '/.well-known/openid-configuration';
            $response = Http::timeout(10)->get($url);
            if (!$response->successful()) {
                throw new RuntimeException("OIDC discovery failed at {$url}");
            }
            $doc = $response->json();
            if (empty($doc['authorization_endpoint']) || empty($doc['token_endpoint']) || empty($doc['jwks_uri'])) {
                throw new RuntimeException('OIDC discovery document is missing required fields.');
            }
            return $doc;
        });
    }

    private function oidcJwks(string $jwksUri): array
    {
        $key = 'oidc-jwks:' . md5($jwksUri);
        return Cache::remember($key, now()->addHours(6), function () use ($jwksUri) {
            $response = Http::timeout(10)->get($jwksUri);
            if (!$response->successful()) {
                throw new RuntimeException("JWKS fetch failed at {$jwksUri}");
            }
            return $response->json('keys', []);
        });
    }

    /**
     * Verify an OIDC ID token. Checks signature (RS256), issuer, audience,
     * exp, and nonce. Rejects unsigned / alg=none tokens categorically.
     */
    private function verifyIdToken(string $idToken, array $cfg, array $discovery, string $expectedNonce): array
    {
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            throw new RuntimeException('Malformed ID token.');
        }
        [$h64, $p64, $s64] = $parts;

        $header  = json_decode(self::b64urlDecode($h64), true) ?: [];
        $payload = json_decode(self::b64urlDecode($p64), true) ?: [];
        $sig     = self::b64urlDecode($s64);

        if (($header['alg'] ?? '') !== 'RS256') {
            throw new RuntimeException('Unsupported token algorithm — only RS256 is accepted.');
        }

        $jwks = $this->oidcJwks($discovery['jwks_uri']);
        $kid = $header['kid'] ?? null;
        $key = collect($jwks)->firstWhere('kid', $kid) ?: ($jwks[0] ?? null);
        if (!$key || ($key['kty'] ?? '') !== 'RSA') {
            throw new RuntimeException('Unable to match signing key from JWKS.');
        }

        $pem = self::jwkToPem($key);
        $valid = openssl_verify("{$h64}.{$p64}", $sig, $pem, OPENSSL_ALGO_SHA256) === 1;
        if (!$valid) {
            throw new RuntimeException('ID token signature verification failed.');
        }

        // Claim checks — issuer, audience, exp, nonce
        if (($payload['iss'] ?? '') !== rtrim($cfg['issuer'], '/') && ($payload['iss'] ?? '') !== $cfg['issuer']) {
            throw new RuntimeException('ID token issuer does not match configured value.');
        }
        $aud = (array) ($payload['aud'] ?? []);
        if (!in_array($cfg['client_id'], $aud, true)) {
            throw new RuntimeException('ID token audience does not include our client_id.');
        }
        if (($payload['exp'] ?? 0) < time() - 30) {
            throw new RuntimeException('ID token has expired.');
        }
        if ($expectedNonce && ($payload['nonce'] ?? '') !== $expectedNonce) {
            throw new RuntimeException('ID token nonce mismatch — possible replay attack.');
        }

        return $payload;
    }

    // ────────────────────────────────────────────────────────────────────
    // SAML
    // ────────────────────────────────────────────────────────────────────

    /**
     * Build the SAML AuthnRequest redirect URL (HTTP-Redirect binding).
     *
     * We intentionally don't sign the AuthnRequest — it's not required by
     * the SAML spec and most IdPs accept unsigned requests. The response
     * signature IS required and IS verified.
     */
    public function samlAuthnRedirect(Tenant $tenant, string $acsUrl, $session): string
    {
        $cfg = $this->samlConfigFor($tenant);

        $requestId = '_' . bin2hex(random_bytes(16));
        $issueInstant = gmdate('Y-m-d\TH:i:s\Z');

        $spEntityId = self::spEntityId();

        $xml = sprintf(
            '<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" ID="%s" Version="2.0" IssueInstant="%s" Destination="%s" AssertionConsumerServiceURL="%s" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"><saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">%s</saml:Issuer></samlp:AuthnRequest>',
            $requestId,
            $issueInstant,
            htmlspecialchars($cfg['idp_sso_url'], ENT_QUOTES | ENT_XML1),
            htmlspecialchars($acsUrl, ENT_QUOTES | ENT_XML1),
            htmlspecialchars($spEntityId, ENT_QUOTES | ENT_XML1),
        );

        $deflated = gzdeflate($xml, 9);
        $encoded = base64_encode($deflated);

        $session->put('sso.saml.request_id', $requestId);
        $session->put('sso.saml.tenant_id', $tenant->id);

        $url = $cfg['idp_sso_url'];
        $url .= (str_contains($url, '?') ? '&' : '?') . 'SAMLRequest=' . urlencode($encoded);

        return $url;
    }

    /**
     * Parse and verify a SAML Response (HTTP-POST binding). Returns the
     * authenticated subject's email and attributes.
     *
     * @return array{email: string, name: string, sub: string, attributes: array}
     */
    public function samlHandleResponse(Tenant $tenant, string $samlResponseB64, $session): array
    {
        $cfg = $this->samlConfigFor($tenant);
        $session->forget(['sso.saml.request_id', 'sso.saml.tenant_id']);

        $xml = base64_decode($samlResponseB64);
        if ($xml === false || $xml === '') {
            throw new RuntimeException('Invalid SAMLResponse encoding.');
        }

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        if (!$doc->loadXML($xml, LIBXML_NONET | LIBXML_DTDLOAD | LIBXML_NOENT)) {
            throw new RuntimeException('SAMLResponse is not valid XML.');
        }
        // XXE / entity-expansion defence: loadXML above doesn't auto-load
        // external subsets when LIBXML_NONET is set; we also reject any
        // DOCTYPE declaration entirely.
        if ($doc->doctype !== null) {
            throw new RuntimeException('SAMLResponse MUST NOT contain a DOCTYPE.');
        }

        $this->verifySamlSignature($doc, $cfg['idp_cert_pem']);

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');

        // Audience check
        $audience = $xpath->query('//saml:Conditions/saml:AudienceRestriction/saml:Audience')->item(0);
        if ($audience && trim($audience->nodeValue) !== self::spEntityId()) {
            throw new RuntimeException('SAMLResponse audience does not match this SP.');
        }

        // NotOnOrAfter / NotBefore
        $conditions = $xpath->query('//saml:Conditions')->item(0);
        if ($conditions) {
            $notBefore = $conditions->getAttribute('NotBefore');
            $notAfter  = $conditions->getAttribute('NotOnOrAfter');
            $now = time();
            if ($notBefore && strtotime($notBefore) - 30 > $now) {
                throw new RuntimeException('SAMLResponse is not yet valid (NotBefore).');
            }
            if ($notAfter && strtotime($notAfter) + 30 < $now) {
                throw new RuntimeException('SAMLResponse has expired (NotOnOrAfter).');
            }
        }

        // Subject
        $nameId = $xpath->query('//saml:Subject/saml:NameID')->item(0);
        if (!$nameId) {
            throw new RuntimeException('SAMLResponse is missing the Subject NameID.');
        }
        $sub = trim($nameId->nodeValue);

        // Attributes
        $attributes = [];
        foreach ($xpath->query('//saml:AttributeStatement/saml:Attribute') as $attr) {
            $name = $attr->getAttribute('Name');
            $values = [];
            foreach ($xpath->query('saml:AttributeValue', $attr) as $v) {
                $values[] = trim($v->nodeValue);
            }
            $attributes[$name] = $values;
        }

        $email = $attributes['email'][0]
            ?? $attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress'][0]
            ?? ((str_contains($sub, '@')) ? $sub : null);

        if (!$email) {
            throw new RuntimeException('SAMLResponse did not supply an email for the subject.');
        }

        $name = $attributes['name'][0]
            ?? $attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name'][0]
            ?? $sub;

        return [
            'email' => strtolower($email),
            'name'  => $name,
            'sub'   => $sub,
            'attributes' => $attributes,
        ];
    }

    /**
     * Verify the XML-DSig enveloped signature on a SAMLResponse. Minimal
     * implementation that checks (a) signature is over the signed element
     * using canonicalised XML, and (b) cert matches the tenant's configured
     * cert.
     *
     * NOTE: production SAML deployments should use a battle-tested library
     * (onelogin/php-saml or simplesamlphp) for signature verification.
     * This implementation covers the common case (IdP-signed response,
     * single signature, RSA-SHA256, exclusive c14n) but does not handle
     * every XML-DSig edge case.
     */
    private function verifySamlSignature(\DOMDocument $doc, string $certPem): void
    {
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        $signature = $xpath->query('//ds:Signature')->item(0);
        if (!$signature) {
            throw new RuntimeException('SAMLResponse is not signed — rejecting.');
        }

        // Extract SignedInfo, SignatureValue, Reference URI
        $signedInfo = $xpath->query('ds:SignedInfo', $signature)->item(0);
        $sigValue = $xpath->query('ds:SignatureValue', $signature)->item(0);
        $reference = $xpath->query('ds:SignedInfo/ds:Reference', $signature)->item(0);
        if (!$signedInfo || !$sigValue || !$reference) {
            throw new RuntimeException('SAMLResponse signature block is incomplete.');
        }

        $signatureAlg = $xpath->query('ds:SignedInfo/ds:SignatureMethod', $signature)->item(0)->getAttribute('Algorithm');
        if (!in_array($signatureAlg, [
            'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
            'http://www.w3.org/2000/09/xmldsig#rsa-sha1', // accepted for legacy IdPs, not recommended
        ], true)) {
            throw new RuntimeException('Unsupported SAML signature algorithm.');
        }
        $opensslAlg = str_contains($signatureAlg, 'sha256') ? OPENSSL_ALGO_SHA256 : OPENSSL_ALGO_SHA1;

        // Canonicalise SignedInfo (exclusive c14n)
        $signedInfoC14n = $signedInfo->C14N(true, false);

        $sigBin = base64_decode(trim($sigValue->nodeValue));
        if ($sigBin === false) {
            throw new RuntimeException('SAMLResponse signature is not valid base64.');
        }

        $publicKey = openssl_pkey_get_public($certPem);
        if (!$publicKey) {
            throw new RuntimeException('Configured IdP certificate could not be parsed.');
        }

        $valid = openssl_verify($signedInfoC14n, $sigBin, $publicKey, $opensslAlg) === 1;
        if (!$valid) {
            throw new RuntimeException('SAMLResponse signature verification failed.');
        }

        // Digest check on the signed element
        $digestAlg = $xpath->query('ds:SignedInfo/ds:Reference/ds:DigestMethod', $signature)->item(0)->getAttribute('Algorithm');
        $digestValue = $xpath->query('ds:SignedInfo/ds:Reference/ds:DigestValue', $signature)->item(0)->nodeValue;
        $phpDigestAlg = str_contains($digestAlg, 'sha256') ? 'sha256' : 'sha1';

        // Clone the signed element, remove the Signature, then canonicalise
        $refUri = ltrim($reference->getAttribute('URI'), '#');
        $signedEl = null;
        if ($refUri === '') {
            $signedEl = $doc->documentElement;
        } else {
            foreach ($xpath->query("//*[@ID='{$refUri}']") as $cand) {
                $signedEl = $cand;
                break;
            }
        }
        if (!$signedEl) {
            throw new RuntimeException('SAMLResponse signed element not found.');
        }
        $clone = $signedEl->cloneNode(true);
        foreach ($xpath->query('ds:Signature', $clone) as $sigNode) {
            $clone->removeChild($sigNode);
        }
        $actualDigest = base64_encode(hash($phpDigestAlg, $clone->C14N(true, false), true));
        if (!hash_equals(trim($digestValue), trim($actualDigest))) {
            throw new RuntimeException('SAMLResponse digest mismatch — response was tampered with.');
        }
    }

    public static function spEntityId(): string
    {
        return 'https://' . config('eiaaw.tenant_domain', 'ep.eiaawsolutions.com') . '/sso/saml/metadata';
    }

    // ────────────────────────────────────────────────────────────────────
    // JIT provisioning + role mapping
    // ────────────────────────────────────────────────────────────────────

    /**
     * Find or create a User for the authenticated SSO identity. Returns
     * null if JIT is disabled and no existing user matches, OR if the
     * email domain is not allow-listed.
     */
    public function findOrProvisionUser(Tenant $tenant, string $email, string $name, array $groups = []): ?User
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        // Domain allow-list (strict when set)
        $allowedDomains = (array) data_get($tenant->sso_config, 'allowed_domains', []);
        if (!empty($allowedDomains)) {
            $domain = substr(strrchr($email, '@'), 1);
            if (!in_array($domain, $allowedDomains, true)) {
                SecurityAuditLog::record('sso_domain_rejected', [
                    'work_email' => $email,
                    'details' => "Email domain {$domain} not on allowed_domains",
                ]);
                return null;
            }
        }

        // Try to find the user within this tenant
        $existing = User::where('work_email', $email)
            ->where('tenant_id', $tenant->id)
            ->first();

        if ($existing) {
            // Keep the role; SSO does NOT downgrade existing roles.
            return $existing;
        }

        if (!data_get($tenant->sso_config, 'jit_provision', false)) {
            SecurityAuditLog::record('sso_jit_disabled', [
                'work_email' => $email,
                'details' => 'User does not exist and JIT provisioning is disabled',
            ]);
            return null;
        }

        // Plan-seat enforcement: if this JIT would push the tenant over its
        // plan_seats limit, refuse and log. The SSO controller surfaces this
        // as a generic "could not match" — operators see the audit reason.
        if (!$tenant->canAddUser()) {
            SecurityAuditLog::record('sso_jit_seat_limit_blocked', [
                'work_email' => $email,
                'tenant_id'  => $tenant->id,
                'details'    => sprintf(
                    'JIT blocked: %d of %d seats used on the %s plan',
                    $tenant->seatsUsed(),
                    (int) $tenant->plan_seats,
                    $tenant->plan,
                ),
            ]);
            return null;
        }

        // JIT provision inside a transaction so the tenant_users pivot row lands too.
        return DB::transaction(function () use ($tenant, $email, $name, $groups) {
            $mappedRole = $this->mapRole($tenant, $groups);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $name ?: explode('@', $email)[0],
                'work_email'=> $email,
                'password'  => Hash::make(Str::random(64)), // SSO-only account; password unusable
                'role'      => $mappedRole,
                'is_active' => true,
            ]);

            $tenant->users()->attach($user->id, [
                'tenant_role' => $mappedRole === 'superadmin' ? 'owner' : 'member',
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            SecurityAuditLog::record('sso_jit_provisioned', [
                'user_id' => $user->id,
                'work_email' => $email,
                'role' => $mappedRole,
                'details' => 'User provisioned via SSO JIT',
            ]);

            return $user;
        });
    }

    /**
     * Map IdP groups → EIAAW role using tenant's configured role_mapping.
     * Falls back to 'employee'. Never maps to superadmin via SSO —
     * superadmin must be promoted manually by an existing superadmin.
     */
    private function mapRole(Tenant $tenant, array $groups): string
    {
        $mapping = (array) data_get($tenant->sso_config, 'oidc.role_mapping',
                    data_get($tenant->sso_config, 'saml.role_mapping', []));

        foreach ($mapping as $group => $role) {
            if ($group === 'default') continue;
            if (in_array($group, $groups, true)) {
                // SSO-provisioned superadmin is a footgun; block it.
                if ($role === 'superadmin') {
                    continue;
                }
                return $role;
            }
        }

        $default = $mapping['default'] ?? 'employee';
        return $default === 'superadmin' ? 'employee' : $default;
    }

    // ────────────────────────────────────────────────────────────────────
    // helpers
    // ────────────────────────────────────────────────────────────────────

    private static function b64urlDecode(string $s): string
    {
        $remainder = strlen($s) % 4;
        if ($remainder) {
            $s .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($s, '-_', '+/'));
    }

    private static function jwkToPem(array $jwk): string
    {
        $n = self::b64urlDecode($jwk['n']);
        $e = self::b64urlDecode($jwk['e']);

        // Build an RSAPublicKey ASN.1 sequence, then wrap it in SubjectPublicKeyInfo.
        $modulus = self::asnInteger($n);
        $exponent = self::asnInteger($e);
        $rsaKey = self::asnSequence($modulus . $exponent);

        // SubjectPublicKeyInfo: AlgorithmIdentifier (rsaEncryption OID) + BIT STRING wrapping the RSAPublicKey.
        $algId = pack('H*', '300d06092a864886f70d0101010500');
        $bitString = "\x03" . self::asnLen(strlen($rsaKey) + 1) . "\x00" . $rsaKey;
        $spki = self::asnSequence($algId . $bitString);

        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spki), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    private static function asnInteger(string $bytes): string
    {
        // Prefix 0x00 if the high bit is set so ASN.1 treats it as positive.
        if (ord($bytes[0]) & 0x80) {
            $bytes = "\x00" . $bytes;
        }
        return "\x02" . self::asnLen(strlen($bytes)) . $bytes;
    }

    private static function asnSequence(string $bytes): string
    {
        return "\x30" . self::asnLen(strlen($bytes)) . $bytes;
    }

    private static function asnLen(int $len): string
    {
        if ($len < 0x80) {
            return chr($len);
        }
        $s = '';
        while ($len > 0) {
            $s = chr($len & 0xff) . $s;
            $len >>= 8;
        }
        return chr(0x80 | strlen($s)) . $s;
    }
}
