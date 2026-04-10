# Cybersecurity Expert Reference

Comprehensive security procedures for threat modeling, vulnerability assessment, secure implementation, and hardening. Integrated into every phase of the full-stack engineering workflow.

---

## Threat Modeling (Phase 2 — Architecture)

Run threat modeling during architecture design, before implementation begins.

### STRIDE Analysis

For each component/boundary in the architecture, assess:

| Threat | Question | Mitigations |
|---|---|---|
| **S**poofing | Can an attacker impersonate a user or service? | Strong authentication (MFA, mTLS, API keys), session management |
| **T**ampering | Can data be modified in transit or at rest? | TLS, HMAC signatures, checksums, database constraints |
| **R**epudiation | Can a user deny performing an action? | Audit logging, digital signatures, immutable event logs |
| **I**nformation Disclosure | Can sensitive data leak? | Encryption at rest/transit, access controls, output filtering |
| **D**enial of Service | Can the system be overwhelmed? | Rate limiting, auto-scaling, circuit breakers, WAF |
| **E**levation of Privilege | Can a user gain unauthorized access? | Least privilege, RBAC/ABAC, input validation, sandboxing |

### Trust Boundaries

Identify and document every trust boundary:
- Client ↔ API (untrusted → trusted)
- API ↔ Database (application → data layer)
- Service ↔ Service (inter-service authentication)
- Internal ↔ External APIs (third-party trust)
- User roles (privilege boundaries within the application)

**Rule**: Validate and sanitize ALL data crossing a trust boundary.

---

## OWASP Top 10 Implementation Checklist

### A01: Broken Access Control
- [ ] Default deny — all routes require authentication unless explicitly public
- [ ] Server-side access control enforcement (never trust client-side checks)
- [ ] RBAC or ABAC consistently applied via middleware/policies
- [ ] Direct object reference protection (verify ownership before access)
- [ ] CORS restricted to specific allowed origins
- [ ] Directory listing disabled on web servers
- [ ] JWT tokens validated (signature, expiry, issuer, audience)
- [ ] Rate limiting on authentication endpoints
- [ ] Session invalidation on logout/password change

### A02: Cryptographic Failures
- [ ] TLS 1.2+ enforced on all connections (HTTP, DB, cache, MQ)
- [ ] Passwords hashed with bcrypt/scrypt/Argon2 (never MD5/SHA)
- [ ] Sensitive data encrypted at rest (PII, financial data, health records)
- [ ] Encryption keys managed via KMS/Vault (not in source code)
- [ ] HTTP Strict-Transport-Security (HSTS) header set
- [ ] No sensitive data in URLs or query parameters
- [ ] Proper random number generation for tokens (CSPRNG)

### A03: Injection
- [ ] SQL: Parameterized queries / prepared statements everywhere
- [ ] NoSQL: Use driver-provided query builders (not string construction)
- [ ] OS command: Avoid `exec`/`system` calls. If required, use allow-lists for commands
- [ ] LDAP: Use parameterized LDAP queries
- [ ] Template: No user input in server-side template expressions
- [ ] XPath: Use parameterized XPath queries
- [ ] ORM: Even with ORMs, audit any raw query usage

### A04: Insecure Design
- [ ] Threat model documented for the system
- [ ] Business logic abuse cases identified (e.g., coupon reuse, race conditions)
- [ ] Rate limiting on expensive operations
- [ ] Resource quotas per user/tenant
- [ ] Secure defaults (deny by default, minimum permissions)
- [ ] Input length limits enforced at all boundaries

### A05: Security Misconfiguration
- [ ] Unnecessary features/ports/services disabled
- [ ] Default credentials changed everywhere
- [ ] Error messages don't expose stack traces, SQL, or internal paths to users
- [ ] Security headers set (CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy)
- [ ] Server version headers removed (Server, X-Powered-By)
- [ ] Directory listing disabled
- [ ] Development tools/debug endpoints removed from production
- [ ] Cloud storage buckets/blobs are not publicly accessible by default

### A06: Vulnerable and Outdated Components
- [ ] Dependency audit in CI pipeline (`npm audit`, `composer audit`, `pip audit`, `cargo audit`)
- [ ] Automated vulnerability scanning (Snyk, Dependabot, Socket.dev, Trivy)
- [ ] Container base images pinned and regularly updated
- [ ] No components with known critical CVEs in production
- [ ] License compliance checked

### A07: Identification and Authentication Failures
- [ ] Password complexity requirements enforced (12+ chars, or passphrase support)
- [ ] Brute force protection (account lockout or exponential backoff)
- [ ] Multi-factor authentication available for sensitive operations
- [ ] Session tokens regenerated after authentication
- [ ] Session timeout configured (idle + absolute)
- [ ] Password reset tokens are single-use, time-limited, and high entropy
- [ ] Credential stuffing mitigation (breached password check, CAPTCHA)

### A08: Software and Data Integrity Failures
- [ ] CI/CD pipeline integrity (signed commits, protected branches, code review required)
- [ ] Dependency integrity (lock files committed, hash verification)
- [ ] Deserialization: Never deserialize untrusted data without validation
- [ ] Auto-update mechanisms use signed packages
- [ ] Database migrations reviewed before production execution

### A09: Security Logging and Monitoring Failures
- [ ] Authentication events logged (login, logout, failed attempts, lockout)
- [ ] Authorization failures logged (access denied events)
- [ ] Input validation failures logged (potential attack probing)
- [ ] High-value transactions logged with user context
- [ ] Logs do NOT contain passwords, tokens, PII, or credit card numbers
- [ ] Log format is structured (JSON) for automated analysis
- [ ] Log aggregation and retention policy defined
- [ ] Alerting configured for anomalous patterns (brute force, privilege escalation)

### A10: Server-Side Request Forgery (SSRF)
- [ ] All outbound URLs validated against an allow-list
- [ ] No user-controlled URLs fetched without validation
- [ ] Internal network access blocked for user-supplied URLs (127.0.0.1, 169.254.169.254, 10.x, 172.16-31.x, 192.168.x)
- [ ] Cloud metadata endpoints blocked (AWS IMDS, GCP metadata, Azure IMDS)
- [ ] DNS rebinding protection (resolve and validate before fetching)

---

## Security by Phase

### Phase 1: Requirements
- Identify compliance requirements (GDPR, HIPAA, PCI-DSS, SOC2)
- Classify data sensitivity levels (public, internal, confidential, restricted)
- Define authentication requirements (SSO, MFA, API keys, OAuth)
- Determine encryption requirements (at rest, in transit, field-level)

### Phase 2: Architecture
- Run STRIDE threat model (see above)
- Define trust boundaries
- Choose auth architecture (JWT, session, OAuth2, SAML)
- Plan secrets management (Vault, cloud KMS, env vars)
- Design audit logging strategy
- Map cloud provider security services (see [cloud-providers.md](./cloud-providers.md))

### Phase 3: Implementation
- Apply OWASP Top 10 checklist (see above)
- Use language-specific security patterns (see [language-patterns.md](./language-patterns.md))
- Implement security headers middleware
- Configure CORS correctly (no wildcard in production)
- Add rate limiting on all public endpoints
- Never log sensitive data

### Phase 4: Validation
- Run SAST (static analysis) — SonarQube, Semgrep, CodeQL
- Run DAST (dynamic analysis) — OWASP ZAP, Burp Suite
- Run dependency vulnerability scan
- Run container image scan (Trivy, Grype)
- Penetration test critical flows (auth, payment, data export)
- Verify security headers with securityheaders.com
- Test for common misconfigurations (exposed debug, default creds, open CORS)

---

## Secure Coding Patterns

### Authentication

```
Password Storage:
  ✅ bcrypt (cost 12+), scrypt, Argon2id
  ❌ MD5, SHA-1, SHA-256 (without salt + stretching), plaintext

Token Generation:
  ✅ CSPRNG (crypto.randomBytes, secrets.token_urlsafe, SecureRandom)
  ❌ Math.random(), rand(), mt_rand(), UUID v4 for security tokens

Session Management:
  ✅ Regenerate session ID after login
  ✅ HttpOnly + Secure + SameSite cookies
  ✅ Idle timeout (15-30 min for sensitive apps)
  ❌ Session ID in URL parameters
  ❌ Predictable session tokens
```

### Authorization

```
Patterns (in order of preference):
1. RBAC (Role-Based Access Control) — simplest, fits most apps
2. ABAC (Attribute-Based Access Control) — fine-grained, policy-based
3. ReBAC (Relationship-Based Access Control) — for social/collaborative apps

Rules:
  ✅ Check authorization on every request (middleware/policy)
  ✅ Verify resource ownership ("Can THIS user access THIS resource?")
  ✅ Default deny — whitelist allowed actions
  ❌ Client-side authorization checks only
  ❌ Checking role in controller logic (use policies/gates)
  ❌ Trusting user-supplied role/permission claims
```

### Input Validation

```
Validate at system boundaries:
  ✅ Type checking (string, int, email, URL)
  ✅ Length limits (min/max)
  ✅ Allow-list for enumerable values
  ✅ Regex for structured formats (dates, phone numbers)
  ✅ Reject null bytes and control characters
  ❌ Block-list approach (trying to catch "bad" input)
  ❌ Validation only on the client side
  ❌ Trusting Content-Type headers without verifying body
```

### File Upload Security

```
  ✅ Validate file type by magic bytes (not just extension)
  ✅ Enforce maximum file size
  ✅ Generate random filenames (never use user-supplied names)
  ✅ Store outside web root or in object storage
  ✅ Scan for malware (ClamAV or cloud-native scanning)
  ✅ Set Content-Disposition: attachment for downloads
  ❌ Executing uploaded files
  ❌ Serving uploads from the same domain without CSP
```

---

## API Security Checklist

- [ ] Authentication required on all non-public endpoints
- [ ] API keys are not exposed in client-side code
- [ ] Rate limiting per API key / IP / user
- [ ] Request body size limits enforced
- [ ] Pagination enforced (no unbounded queries)
- [ ] Sensitive fields excluded from response by default
- [ ] API versioning strategy (URL path or header)
- [ ] CORS configured for allowed origins only
- [ ] GraphQL: Query depth limiting and complexity analysis (if applicable)
- [ ] WebSocket: Authentication on connection, message validation

---

## Infrastructure Security Checklist

- [ ] Firewall rules follow least-privilege (only required ports open)
- [ ] SSH access via key-only (no password auth)
- [ ] Database ports not publicly accessible
- [ ] Cloud IAM roles follow least-privilege
- [ ] Secrets rotated on a schedule
- [ ] Container images scanned before deployment
- [ ] Kubernetes: PodSecurityPolicies/Standards, no privileged containers
- [ ] Network policies restrict pod-to-pod communication
- [ ] Cloud storage encryption enabled (SSE or CSE)
- [ ] Backup encryption enabled
- [ ] VPN or private networking for internal service communication
