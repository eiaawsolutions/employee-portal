# Penetration Testing Reference

Comprehensive guide for building systems that pass penetration tests. Covers pentest methodology, common findings, pre-pentest hardening checklists, and remediation patterns. Use this reference during Phase 3 (implementation) to build pentest-ready code, and during Phase 4 (validation) to self-assess before an external pentest.

---

## Pentest Methodology Understanding

Know what pentesters look for so you can build defenses proactively.

### Pentest Phases (Attacker Perspective)

```
1. Reconnaissance (Information Gathering)
   ├── Passive: OSINT, DNS records, WHOIS, certificate transparency logs
   ├── Active: Port scanning, service fingerprinting, directory brute-forcing
   └── What they find: Tech stack, endpoints, usernames, email patterns, exposed services

2. Enumeration (Mapping the Attack Surface)
   ├── Endpoint discovery (spidering, API docs, JS analysis)
   ├── Authentication mechanism identification
   ├── Parameter discovery (hidden form fields, API params, headers)
   └── User/role enumeration (error message differences, timing attacks)

3. Vulnerability Analysis
   ├── Automated scanning (Burp Suite, OWASP ZAP, Nuclei)
   ├── Manual testing per OWASP Testing Guide
   ├── Business logic abuse testing
   └── Configuration review

4. Exploitation
   ├── Injection attacks (SQL, NoSQL, command, template)
   ├── Authentication bypass
   ├── Privilege escalation (horizontal + vertical)
   ├── Session hijacking / fixation
   └── Chain multiple low-severity findings into critical impact

5. Post-Exploitation
   ├── Data exfiltration attempts
   ├── Lateral movement (service-to-service)
   ├── Persistence mechanisms
   └── Evidence of impact (demonstrate what an attacker could access)
```

### Types of Penetration Tests

| Type | Scope | When to Use |
|---|---|---|
| **Black box** | No prior knowledge of the system | Pre-launch assessment, most realistic |
| **Gray box** | Authenticated access, partial documentation | Most common for web apps, best cost/value |
| **White box** | Full source code access, architecture docs | Deepest coverage, finds logic bugs |
| **API-only** | Focus on API endpoints | Backend services, microservices |
| **Mobile** | iOS/Android app + backend API | Mobile applications |
| **Infrastructure** | Network, servers, cloud config | Cloud environments, network security |
| **Social engineering** | Phishing, pretexting | Employee security awareness |
| **Red team** | Full adversary simulation, multi-vector | Mature organizations, comprehensive |

---

## Pre-Pentest Hardening Checklist

Run this checklist before any scheduled pentest. Every item represents a common finding.

### 1. Information Disclosure (Recon Defense)

- [ ] Server version headers removed (`Server`, `X-Powered-By`, `X-AspNet-Version`)
- [ ] Framework-specific default pages removed (Laravel Telescope in prod, Django debug, Spring Actuator)
- [ ] Error pages show generic messages — no stack traces, SQL, file paths, or framework details
- [ ] Robots.txt does not reveal sensitive paths (admin panels, API docs, internal URLs)
- [ ] Directory listing disabled on all web servers
- [ ] Source maps not deployed to production (`.map` files)
- [ ] Git repository not accessible via web (`.git/` directory blocked)
- [ ] API documentation not publicly accessible in production (Swagger/OpenAPI)
- [ ] HTML comments don't contain sensitive info (TODO comments, credentials, internal URLs)
- [ ] DNS records don't expose internal infrastructure (internal hostnames, staging URLs)
- [ ] Certificate transparency logs reviewed for unintended subdomain exposure
- [ ] Verbose error messages in API responses replaced with error codes
- [ ] HTTP headers don't leak internal IP addresses (`X-Forwarded-For` handling)

### 2. Authentication & Session Management

- [ ] Login does NOT reveal whether username or password is wrong ("Invalid credentials" for both)
- [ ] Registration does NOT reveal whether email is already registered (use "check your email" pattern)
- [ ] Password reset does NOT reveal whether email exists (always say "if account exists, email sent")
- [ ] No user enumeration via timing attacks (constant-time comparison for auth checks)
- [ ] Account lockout after failed attempts (5-10 attempts) with lockout duration
- [ ] Password policy enforced: 12+ chars, check against breached password lists (HaveIBeenPwned API)
- [ ] MFA available and enforced for admin/sensitive roles
- [ ] Session tokens are high-entropy (128+ bits from CSPRNG)
- [ ] Session ID regenerated after login (prevent session fixation)
- [ ] Sessions expire: idle timeout (15-30 min for sensitive, 8-24 hr for standard)
- [ ] Absolute session timeout (24-72 hr max regardless of activity)
- [ ] Session invalidated server-side on logout (not just client cookie deletion)
- [ ] All other sessions invalidated on password change
- [ ] Cookie attributes set: `HttpOnly`, `Secure`, `SameSite=Lax` (or `Strict`)
- [ ] No session tokens in URLs
- [ ] JWT: Signature verified, `alg` header validated (reject `none`), expiry enforced, issuer checked
- [ ] JWT: Refresh token rotation implemented (one-time use refresh tokens)
- [ ] OAuth: `state` parameter validated (CSRF protection for OAuth flow)
- [ ] OAuth: Authorization code used once, short-lived (< 10 min)
- [ ] Remember-me tokens are separate from session tokens, stored hashed server-side

### 3. Authorization & Access Control

- [ ] Every endpoint enforces authorization (middleware/decorator, not manual checks)
- [ ] Horizontal privilege escalation tested: User A cannot access User B's resources by changing ID
- [ ] Vertical privilege escalation tested: Regular user cannot access admin functions
- [ ] IDOR (Insecure Direct Object Reference) prevented: All resource access checks ownership
- [ ] UUIDs used for external-facing resource IDs (not sequential integers)
- [ ] API endpoints don't expose data beyond the user's scope (over-fetching)
- [ ] Admin panels on separate subdomain or behind VPN
- [ ] Admin creation/invitation restricted (no self-registration for admin roles)
- [ ] Role changes require re-authentication
- [ ] Deleted/disabled users cannot authenticate or access resources
- [ ] API keys scoped to minimum required permissions
- [ ] Webhook endpoints validate signatures (HMAC verification)
- [ ] File access restricted: Users can only access their own uploads
- [ ] GraphQL: Field-level authorization (not just query-level)

### 4. Injection Prevention

- [ ] SQL injection: ALL queries use parameterized statements — no exceptions
- [ ] SQL injection: ORM raw query methods audited (`DB::raw()`, `.raw()`, `@Query` with concatenation)
- [ ] NoSQL injection: MongoDB queries use typed filters (not string-constructed queries)
- [ ] Command injection: No `exec()`/`system()`/`shell_exec()` with user input. If needed, strict allow-list
- [ ] LDAP injection: Parameterized LDAP queries
- [ ] Template injection (SSTI): No user input in template expressions (`eval`, `render`, `compile`)
- [ ] XPath injection: Parameterized XPath queries
- [ ] Header injection: Newlines (`\r\n`) stripped from values used in HTTP headers
- [ ] Log injection: User input sanitized before writing to logs (strip newlines, limit length)
- [ ] Email header injection: Newlines stripped from email headers (To, Subject, CC)
- [ ] Path traversal: `../` sequences blocked, filenames validated against allow-list
- [ ] XXE (XML External Entity): XML parsing configured to disable DTD and external entities
- [ ] Deserialization: No deserialization of untrusted data (use JSON instead of native serialization)

### 5. Cross-Site Scripting (XSS) Prevention

- [ ] All output HTML-encoded by default (framework auto-escaping enabled)
- [ ] Unsafe rendering explicitly opted into and audited (Blade `{!! !!}`, React `dangerouslySetInnerHTML`, `|safe`)
- [ ] Content Security Policy (CSP) header set with specific sources (no `unsafe-inline` in production)
- [ ] CSP `script-src` does not include `unsafe-eval`
- [ ] User-generated HTML sanitized with allow-list library (DOMPurify, HtmlSanitizer, Bleach)
- [ ] JSON data in HTML uses `JSON.stringify()` with proper encoding, not raw interpolation
- [ ] SVG uploads sanitized (SVGs can contain JavaScript)
- [ ] `X-Content-Type-Options: nosniff` header set
- [ ] Rich text editors configured to strip dangerous tags/attributes
- [ ] URL parameters not reflected into HTML without encoding

### 6. Cross-Site Request Forgery (CSRF)

- [ ] CSRF tokens on all state-changing requests (POST, PUT, DELETE, PATCH)
- [ ] CSRF token tied to user session (not a static value)
- [ ] CSRF token validated server-side on every state-changing request
- [ ] `SameSite` cookie attribute set to `Lax` or `Strict`
- [ ] For APIs with token auth (Bearer): CSRF not needed (verify no cookie-based auth fallback)
- [ ] Custom header required for AJAX requests (`X-Requested-With`)
- [ ] Login form protected against login CSRF (attacker logging victim into attacker's account)

### 7. Security Headers

- [ ] `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload`
- [ ] `Content-Security-Policy` with restrictive policy (no `unsafe-inline/eval` if possible)
- [ ] `X-Content-Type-Options: nosniff`
- [ ] `X-Frame-Options: DENY` (or `SAMEORIGIN` if framing needed)
- [ ] `Referrer-Policy: strict-origin-when-cross-origin` (or stricter)
- [ ] `Permissions-Policy` restricting unnecessary browser features (camera, microphone, geolocation)
- [ ] `X-XSS-Protection: 0` (deprecated but some scanners flag its absence)
- [ ] `Cache-Control: no-store` on pages with sensitive data
- [ ] `Cross-Origin-Opener-Policy: same-origin`
- [ ] `Cross-Origin-Resource-Policy: same-origin`
- [ ] No `Access-Control-Allow-Origin: *` in production (except for truly public APIs)
- [ ] CORS preflight responses don't reflect arbitrary origins

### 8. File Upload & Download

- [ ] File type validated by magic bytes (not extension or Content-Type header alone)
- [ ] File extension validated against allow-list (not block-list)
- [ ] Maximum file size enforced at web server and application level
- [ ] Filename generated server-side (UUID + original extension from allow-list)
- [ ] Files stored outside web root (object storage or non-publicly-accessible directory)
- [ ] Uploaded files served from a separate domain/CDN (isolate cookie scope)
- [ ] `Content-Disposition: attachment` for downloads (prevent browser rendering)
- [ ] `Content-Type` set correctly for downloads (not `application/octet-stream` for all)
- [ ] Image files re-processed/re-encoded (strip EXIF data, neutralize polyglots)
- [ ] Archive files (ZIP) checked for path traversal in filenames (zip slip vulnerability)
- [ ] Antivirus/malware scanning on uploaded files (ClamAV or cloud-native)
- [ ] Upload rate limiting (prevent storage exhaustion)

### 9. Cryptography

- [ ] TLS 1.2+ only (TLS 1.0 and 1.1 disabled)
- [ ] Strong cipher suites only (no RC4, DES, 3DES, NULL ciphers)
- [ ] Certificate chain valid and trusted
- [ ] HSTS header prevents downgrade attacks
- [ ] Passwords: bcrypt (cost 12+), scrypt, or Argon2id — no MD5, SHA-1, SHA-256 without salt
- [ ] API tokens / secrets: 256+ bits from CSPRNG
- [ ] Encryption at rest: AES-256-GCM or ChaCha20-Poly1305
- [ ] No custom/homegrown cryptography implementations
- [ ] Key rotation possible without downtime
- [ ] Old/weak algorithms not accepted (e.g., JWT `alg: none`, RSA with small key)

### 10. Business Logic

- [ ] Race conditions tested: Concurrent requests to same endpoint (double-spend, double-redeem)
- [ ] Negative values handled: Can't purchase -1 items, can't transfer negative amounts
- [ ] Workflow bypass tested: Can't skip steps (e.g., go to checkout without payment)
- [ ] Quantity/amount limits enforced server-side (not just client-side)
- [ ] Coupon/voucher can't be reused beyond allowed limit
- [ ] Rate limiting on expensive operations (report generation, email sending, exports)
- [ ] Invitation/referral systems can't be self-referred
- [ ] Time-of-check-to-time-of-use (TOCTOU) vulnerabilities considered
- [ ] Mass assignment: Only expected fields accepted (allowlist, not blocklist)
- [ ] Feature flags don't expose disabled features to API probing

### 11. API-Specific

- [ ] API versioning implemented (no breaking changes to existing clients)
- [ ] Request body size limit enforced (prevent large payload DoS)
- [ ] Response pagination enforced (no unbounded queries)
- [ ] Batch endpoints have per-request limits
- [ ] GraphQL: Query depth limit (e.g., max 10 levels)
- [ ] GraphQL: Query complexity/cost analysis (prevent resource-intensive queries)
- [ ] GraphQL: Introspection disabled in production
- [ ] Rate limiting with appropriate windows (per-user, per-IP, per-API-key)
- [ ] HTTP method enforcement (GET endpoints don't accept POST and vice versa)
- [ ] Content-Type validation (reject unexpected content types)
- [ ] No mass data exposure via list endpoints (filter to user's scope)
- [ ] Webhook URLs validated (no SSRF — block internal IPs, metadata endpoints)
- [ ] API keys hashed in database (like passwords, not stored plaintext)

### 12. Infrastructure & Configuration

- [ ] Debug mode OFF in production (`APP_DEBUG=false`, `DEBUG=False`)
- [ ] Management ports not publicly accessible (DB, Redis, RabbitMQ, Elasticsearch)
- [ ] SSH key-only authentication (password auth disabled)
- [ ] Default credentials changed everywhere (database, admin panels, monitoring tools)
- [ ] Unused services/ports disabled
- [ ] Cloud storage buckets not publicly writable or listable
- [ ] Kubernetes: No `privileged: true` containers, no `hostNetwork`, no `hostPID`
- [ ] Secrets not in environment variable listings (use mounted secrets or Vault)
- [ ] CORS not misconfigured (no wildcard with credentials)
- [ ] HTTP to HTTPS redirect enforced (301 permanent redirect)
- [ ] Subresource Integrity (SRI) for external scripts and stylesheets
- [ ] No open redirects (redirect URLs validated against allow-list)

---

## Common Pentest Findings → Fix Patterns

### Critical Severity

| Finding | Fix Pattern |
|---|---|
| **SQL Injection** | Replace ALL string concatenation in queries with parameterized statements. Audit every `raw()`, `exec()`, and dynamic query builder usage. |
| **Remote Code Execution** | Remove all `eval()`, `exec()`, `system()` with user input. If subprocess needed, use strict allow-list of commands with parameterized arguments. |
| **Authentication Bypass** | Implement auth middleware that runs before every route handler. Verify session/token server-side on every request. Fix any logic flaws in auth checks. |
| **Insecure Deserialization** | Never deserialize untrusted data with native serialization (PHP `unserialize`, Java `ObjectInputStream`, Python `pickle`). Use JSON + schema validation. |
| **SSRF** | Validate all user-supplied URLs against allow-list. Block private IP ranges (10.x, 172.16-31.x, 192.168.x, 127.x, 169.254.x). Resolve DNS and re-check IP before fetching. |

### High Severity

| Finding | Fix Pattern |
|---|---|
| **Broken Access Control / IDOR** | Add ownership check to every data access query: `WHERE id = :id AND user_id = :current_user`. Use UUIDs for external-facing IDs. |
| **Stored XSS** | Enable framework auto-escaping. Audit all unescaped output points. Add CSP header. Use DOMPurify for user-generated HTML. |
| **Privilege Escalation** | Enforce role checks in middleware, not handlers. Verify role on every request, not just at login. Check both vertical (user→admin) and horizontal (user→other user). |
| **JWT Vulnerabilities** | Validate `alg` header against allow-list (reject `none`). Verify signature, expiry, issuer. Use asymmetric keys (RS256/ES256) for distributed verification. |
| **Password in Logs/Responses** | Scrub sensitive fields from log serialization. Add `@JsonIgnore`/`hidden`/ field exclusions on password fields. Review all error response bodies. |

### Medium Severity

| Finding | Fix Pattern |
|---|---|
| **Missing Security Headers** | Add middleware/filter that sets all security headers on every response (see Section 7 above). |
| **User Enumeration** | Return identical messages for login/register/reset regardless of whether account exists. Use constant-time comparisons. |
| **Session Fixation** | Regenerate session ID after authentication. Invalidate old session ID. |
| **Clickjacking** | Add `X-Frame-Options: DENY` and `frame-ancestors 'none'` in CSP. |
| **CORS Misconfiguration** | Replace `*` with specific allowed origins. Don't reflect `Origin` header without validation. Never allow credentials with wildcard origin. |
| **Outdated Dependencies** | Run `npm audit fix`, `composer update`, `pip install --upgrade`. Set up Dependabot/Renovate for automated updates. |

### Low Severity

| Finding | Fix Pattern |
|---|---|
| **Version Disclosure** | Remove `Server`, `X-Powered-By` headers. Configure web server to suppress version info. |
| **Missing Cookie Flags** | Set `HttpOnly`, `Secure`, `SameSite=Lax` on all cookies. |
| **Verbose Errors** | Custom error handler that returns generic message. Log details server-side only. |
| **Autocomplete on Sensitive Fields** | Add `autocomplete="off"` on password/credit card form fields (some scanners flag this). |
| **Mixed Content** | Ensure all resources loaded over HTTPS. Use `upgrade-insecure-requests` in CSP. |

---

## Pentest Preparation Workflow

### Before the Pentest

```
T-4 weeks: Schedule pentest, define scope and rules of engagement
T-3 weeks: Run this pre-hardening checklist (all 12 sections above)
T-2 weeks: Run automated scans internally
  ├── SAST: Semgrep / SonarQube / CodeQL
  ├── DAST: OWASP ZAP automated scan
  ├── Dependency: npm audit / composer audit / pip audit / cargo audit
  ├── Container: Trivy scan on all images
  ├── Infrastructure: tfsec / checkov on IaC
  └── Headers: securityheaders.com / Mozilla Observatory
T-1 week:  Fix all critical/high findings from internal scans
T-0:       Pentest begins — provide test accounts, documentation, and environment access
```

### During the Pentest

- Provide pentesters with: Test accounts (one per role), API documentation, architecture diagram
- Monitor logs for pentester activity (useful for validating detection capabilities)
- Don't fix findings during the test (wait for full report)
- Be available for questions (clarify intended behavior vs bugs)

### After the Pentest

```
T+0:       Receive report — categorize findings by severity
T+1 week:  Review findings with team — challenge false positives, confirm valid findings
T+2 weeks: Remediation plan with deadlines:
  ├── Critical: Fix within 24-48 hours
  ├── High: Fix within 1-2 weeks
  ├── Medium: Fix within 1 month
  └── Low: Fix within 1 quarter
T+N:       Retest — pentester verifies fixes (usually included in engagement)
T+N+1:     Update security baselines, add regression tests for each finding
```

---

## Automated Pre-Pentest Scan Commands

Run these before every release and before any scheduled pentest:

### SAST (Static Application Security Testing)
```bash
# Semgrep — language-agnostic, OWASP rules
semgrep --config=p/owasp-top-ten --config=p/security-audit .

# For PHP/Laravel
semgrep --config=p/php .

# For JavaScript/TypeScript
semgrep --config=p/javascript .
semgrep --config=p/typescript .

# For Python
semgrep --config=p/python .
bandit -r . -f json  # Python-specific security linter
```

### DAST (Dynamic Application Security Testing)
```bash
# OWASP ZAP — automated scan against running application
docker run -t ghcr.io/zaproxy/zaproxy:stable zap-baseline.py \
  -t https://your-staging-url.com \
  -r report.html

# Full scan (more thorough, takes longer)
docker run -t ghcr.io/zaproxy/zaproxy:stable zap-full-scan.py \
  -t https://your-staging-url.com \
  -r full-report.html

# Nuclei — template-based vulnerability scanner
nuclei -u https://your-staging-url.com -t cves/ -t vulnerabilities/ -severity critical,high
```

### Dependency Scanning
```bash
# JavaScript/Node.js
npm audit --audit-level=high
npx better-npm-audit audit

# PHP
composer audit

# Python
pip audit
safety check

# Go
govulncheck ./...

# Rust
cargo audit

# .NET
dotnet list package --vulnerable

# Ruby
bundle audit check --update
```

### Container & Infrastructure Scanning
```bash
# Container image scanning
trivy image your-registry/your-app:latest
grype your-registry/your-app:latest

# IaC scanning
tfsec .          # Terraform
checkov -d .     # Multi-framework (Terraform, CloudFormation, K8s, Docker)
kube-score score deployment.yaml  # Kubernetes manifests

# Secret scanning
trufflehog git file://. --since-commit HEAD~50
gitleaks detect --source=. --verbose
```

### Security Headers Check
```bash
# Check headers of a running application
curl -sI https://your-url.com | grep -iE "strict-transport|content-security|x-frame|x-content-type|referrer-policy|permissions-policy|x-xss"

# Mozilla Observatory (online)
# https://observatory.mozilla.org

# securityheaders.com (online)
# https://securityheaders.com
```

---

## Pentest-Ready Security Test Suite

Add these tests to your test suite. They serve as regression tests after pentest findings are fixed.

```
# Test categories to implement in your test framework:

describe "Authentication Security" do
  test "login returns same error for wrong username and wrong password"
  test "account locks after N failed attempts"
  test "session regenerated after login"
  test "session invalidated after logout"
  test "session invalidated after password change"
  test "expired JWT rejected"
  test "JWT with alg:none rejected"
  test "password reset token is single-use"
  test "password reset token expires after N minutes"
end

describe "Authorization Security" do
  test "unauthenticated user cannot access protected resource"
  test "user A cannot access user B resources (IDOR)"
  test "regular user cannot access admin endpoints"
  test "deleted user token is rejected"
  test "role change requires re-authentication"
end

describe "Injection Prevention" do
  test "SQL injection payloads in parameters are safely handled"
  test "XSS payloads in input are escaped in output"
  test "path traversal sequences rejected in file parameters"
  test "command injection payloads don't execute"
  test "null bytes in input are rejected"
end

describe "Security Headers" do
  test "response includes Strict-Transport-Security"
  test "response includes Content-Security-Policy"
  test "response includes X-Content-Type-Options: nosniff"
  test "response includes X-Frame-Options"
  test "response does not include Server version"
  test "response does not include X-Powered-By"
end

describe "Business Logic Security" do
  test "concurrent duplicate requests are handled safely (idempotency)"
  test "negative quantities rejected"
  test "workflow steps cannot be skipped"
  test "rate limiting triggers on excessive requests"
end

describe "CSRF Protection" do
  test "state-changing request without CSRF token is rejected"
  test "state-changing request with invalid CSRF token is rejected"
end

describe "Information Disclosure" do
  test "error response does not contain stack trace"
  test "error response does not contain SQL"
  test "404 page does not reveal framework"
  test "password field not present in API responses"
end
```

---

## Pentest Report Severity Mapping

Understand how pentesters score findings (typically CVSS-based):

| CVSS Score | Severity | Typical SLA | Examples |
|---|---|---|---|
| 9.0 - 10.0 | **Critical** | Fix in 24-48 hours | RCE, SQL injection (data extraction), auth bypass, SSRF to cloud metadata |
| 7.0 - 8.9 | **High** | Fix in 1-2 weeks | Stored XSS, privilege escalation, IDOR with sensitive data, broken encryption |
| 4.0 - 6.9 | **Medium** | Fix in 1 month | Reflected XSS, CSRF, user enumeration, missing security headers, session fixation |
| 0.1 - 3.9 | **Low** | Fix in 1 quarter | Version disclosure, missing cookie flags, verbose errors, autocomplete on fields |
| 0 | **Info** | No fix required | Best practice recommendations, defense-in-depth suggestions |

### Challenging Pentest Findings

Not every finding requires a fix. Valid reasons to accept risk:

- **False positive**: The finding doesn't apply (e.g., CSRF on a stateless API with token auth)
- **Compensating control**: Another control mitigates the risk (e.g., WAF blocking the attack vector)
- **Accepted risk**: Business decision to accept (must be documented with management sign-off)
- **Not exploitable**: Finding exists but the preconditions for exploitation don't (prove this with evidence)

Always document the rationale. Pentesters and auditors expect a response for every finding.
