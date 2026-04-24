# Threat Model — Employee Portal (HRM SaaS)

**System**: Claritas Employee Portal
**Version**: 1.0 (April 2026)
**Classification**: Internal — Confidential
**Last Reviewed**: 2026-04-06

---

## 1. System Overview

The Employee Portal is a multi-role HR management system handling:
- **Employee lifecycle** (onboarding → active → offboarding)
- **IT asset management** (inventory, provisioning, assignment, disposal)
- **Leave management, payroll, attendance tracking, expense claims**
- **AI-powered accounting module**
- **C-suite reporting and analytics**

### 1.1 Architecture

```
Internet → Apache/Nginx (TLS) → PHP-FPM → Laravel Application → MySQL/MariaDB
                                    ├── Redis (cache/sessions)
                                    ├── File Storage (local disk)
                                    └── SMTP (outbound email)
```

### 1.2 Data Classification

| Category | Sensitivity | Examples |
|---|---|---|
| **Restricted** | Highest | NRIC copies, passport scans, salary data, bank accounts |
| **Confidential** | High | Employee personal details, medical info, contracts, payroll |
| **Internal** | Medium | Work schedules, IT assets, leave records, announcements |
| **Public** | Low | Company name, office locations |

---

## 2. Trust Boundaries

```
┌─────────────────────────────────────────────────────────────┐
│  EXTERNAL (Untrusted)                                        │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                  │
│  │ Browser  │  │ Email    │  │ VPN User │                   │
│  │ (Public) │  │ Client   │  │ (Remote) │                   │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘                  │
├───────┼──────────────┼─────────────┼────────────────────────┤
│  TB1: TLS Termination (HTTPS boundary)                       │
├───────┼──────────────┼─────────────┼────────────────────────┤
│  ┌────▼──────────────▼─────────────▼─────┐                  │
│  │  Web Server (Apache/PHP-FPM)          │                  │
│  │  ┌───────────────────────────────┐    │                  │
│  │  │ TB2: Authentication Gate       │    │                  │
│  │  │  (Login + Session + CSRF)     │    │                  │
│  │  └───────────┬───────────────────┘    │                  │
│  │              │                         │                  │
│  │  ┌───────────▼───────────────────┐    │                  │
│  │  │ TB3: Authorization Layer       │    │                  │
│  │  │  (Role-based middleware)      │    │                  │
│  │  │  HR → IT → Finance → Admin   │    │                  │
│  │  └───────────┬───────────────────┘    │                  │
│  │              │                         │                  │
│  │  ┌───────────▼───────────────────┐    │                  │
│  │  │ TB4: Application Logic         │    │                  │
│  │  │  (Controllers + Services)     │    │                  │
│  │  └───────────┬───────────────────┘    │                  │
│  └──────────────┼────────────────────────┘                  │
├─────────────────┼────────────────────────────────────────────┤
│  TB5: Data Layer Boundary                                     │
│  ┌──────────────▼──────┐  ┌──────────────┐  ┌────────────┐  │
│  │  MySQL/MariaDB      │  │ File Storage  │  │ SMTP       │  │
│  │  (encrypted at rest)│  │ (local disk)  │  │ (outbound) │  │
│  └─────────────────────┘  └──────────────┘  └────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

---

## 3. STRIDE Threat Analysis

### 3.1 Spoofing (Identity)

| # | Threat | Component | Likelihood | Impact | Mitigation | Status |
|---|---|---|---|---|---|---|
| S1 | Credential stuffing / brute force | Login endpoint | High | High | Account lockout after 5 failures; rate limiting (30/min); timing-attack-safe responses | ✅ Implemented |
| S2 | Session hijacking | Session management | Medium | Critical | HttpOnly + Secure + SameSite cookies; single-session enforcement; session regeneration on login | ✅ Implemented |
| S3 | Phishing for credentials | Email links | Medium | High | HSTS enforcement; branded email templates; no credentials in URLs | ✅ Implemented |
| S4 | Password reset abuse | Password reset flow | Medium | High | Generic responses ("if account exists..."); 60-min token expiry; single-use tokens | ✅ Implemented |
| S5 | Token-based access bypass | AARF acknowledgement links | Low | Medium | Unique random tokens; HMAC validation; rate limited (10/min) | ✅ Implemented |

### 3.2 Tampering (Data Integrity)

| # | Threat | Component | Likelihood | Impact | Mitigation | Status |
|---|---|---|---|---|---|---|
| T1 | SQL injection | Database queries | Medium | Critical | Eloquent ORM (parameterized queries); no raw SQL with user input | ✅ Implemented |
| T2 | CSRF attacks | State-changing forms | Medium | High | Laravel CSRF tokens on all POST/PUT/DELETE; SameSite cookies | ✅ Implemented |
| T3 | Log tampering | Audit trail | Low | High | HMAC-chained integrity log; sequence verification | ✅ Implemented |
| T4 | File content manipulation | File uploads | Medium | High | Magic-byte validation; image reprocessing; upload size limits | ✅ Implemented |
| T5 | Backup tampering | Backup files | Low | Critical | AES-256-CBC encryption + HMAC integrity; SHA-256 manifests | ✅ Implemented |
| T6 | Mass assignment | Eloquent models | Medium | High | `$fillable` whitelists on all models | ✅ Implemented |

### 3.3 Repudiation (Deniability)

| # | Threat | Component | Likelihood | Impact | Mitigation | Status |
|---|---|---|---|---|---|---|
| R1 | Deny performing sensitive action | Admin operations | Medium | Medium | SecurityAuditLog records all auth events; HMAC chain integrity | ✅ Implemented |
| R2 | Deny editing employee records | Employee/Onboarding edits | Medium | High | EmployeeEditLog + OnboardingEditLog with user context | ✅ Implemented |
| R3 | Deny consent/acknowledgement | AARF/consent flows | Medium | High | Token-based acknowledgement with timestamp + IP logging | ✅ Implemented |

### 3.4 Information Disclosure

| # | Threat | Component | Likelihood | Impact | Mitigation | Status |
|---|---|---|---|---|---|---|
| I1 | NRIC/passport data exposure | File storage | Medium | Critical | Private disk (non-web-accessible); role-based SecureFileController; rate limiting | ✅ Implemented |
| I2 | Error message information leak | Exception handling | Medium | Medium | Generic error pages in production; APP_DEBUG=false | ✅ Implemented |
| I3 | Server/technology fingerprinting | HTTP headers | Low | Low | X-Powered-By and Server headers removed; CSP set | ✅ Implemented |
| I4 | Image EXIF data leaking PII | Uploaded images (GPS, camera) | Medium | Medium | ImageSanitizer strips all EXIF/metadata on upload | ✅ Implemented |
| I5 | Salary/payroll data exposure | Payroll module | Low | Critical | Role-based access (HR Manager + Superadmin only); no caching | ✅ Implemented |
| I6 | Session data in URL | Authentication | Low | High | Session tokens in cookies only; no URL parameters | ✅ Implemented |
| I7 | Backup data exposure | Backup files | Low | Critical | AES-256-CBC encryption; 0600 permissions; dedicated encryption key | ✅ Implemented |

### 3.5 Denial of Service

| # | Threat | Component | Likelihood | Impact | Mitigation | Status |
|---|---|---|---|---|---|---|
| D1 | Login bruteforce flooding | Login endpoint | High | Medium | Rate limiting (30/min per IP); auto-lockout | ✅ Implemented |
| D2 | File upload storage exhaustion | Upload endpoints | Medium | Medium | Rate limiting (10/min); max file size (10MB); upload count limits | ✅ Implemented |
| D3 | Rapid-fire automated scanning | All endpoints | Medium | Medium | ThreatDetector rate anomaly check (60 req/min); automated alerts | ✅ Implemented |
| D4 | Database connection exhaustion | Application layer | Low | High | Connection pooling; query optimization | ⚠️ Partial |

### 3.6 Elevation of Privilege

| # | Threat | Component | Likelihood | Impact | Mitigation | Status |
|---|---|---|---|---|---|---|
| E1 | Horizontal privilege escalation | Employee data access | Medium | High | Resource ownership verification in controllers; SecureFileController checks | ✅ Implemented |
| E2 | Vertical privilege escalation | Role-based access | Medium | Critical | Middleware role gates; capability methods (canEdit*, canView*); server-side enforcement | ✅ Implemented |
| E3 | Role self-assignment | Role management | Low | Critical | Only Superadmin can assign roles; validation in EmployeeController | ✅ Implemented |
| E4 | Multiple auth failure privilege escal. | Repeated 403s | Medium | High | ThreatDetector detects 3+ unauthorized attempts in 10 min; alerts IT+Superadmin | ✅ Implemented |

---

## 4. Attack Surface Analysis

### 4.1 External Attack Surface

| Entry Point | Protocol | Auth Required | Rate Limited | Notes |
|---|---|---|---|---|
| Login page | HTTPS | No | 30/min/IP | Account lockout after 5 failures |
| Password reset | HTTPS | No | 5/min/IP | Generic response messages |
| AARF acknowledgement | HTTPS | Token-based | 10/min | CSRF exempted (email link access) |
| Registration (invite) | HTTPS | Token-based | Yes | OnboardingInvite token required |

### 4.2 Authenticated Attack Surface

| Entry Point | Min Role | Controls |
|---|---|---|
| Employee CRUD | hr_manager | Capability checks + CSRF + audit logging |
| File upload/download | varies | Magic-byte + MIME validation + image sanitization + role-based |
| Payroll management | hr_manager | Capability checks + encrypted transport |
| Role assignment | superadmin | Strict validation + audit trail |
| Accounting module | finance_manager | `canManageAccounting()` + `canApproveTransactions()` |
| Asset management | it_manager / it_executive | Capability checks + CSRF |

### 4.3 Internal Attack Surface

| Component | Threat | Mitigation |
|---|---|---|
| Database credentials | Credential theft | Stored in .env (not in code); file permissions 0600 |
| Backup files | Data theft | AES-256 encrypted; 0600 permissions; separate encryption key |
| Log files | Evidence tampering | HMAC-chain integrity protection |
| Scheduled commands | Unauthorized execution | Artisan commands require CLI access |

---

## 5. Security Controls Matrix

### 5.1 Preventive Controls

| Control | Implementation | Covers |
|---|---|---|
| TLS/HTTPS enforcement | ForceHttps middleware + HSTS header (1 year) | All network communication |
| CSRF protection | Laravel CSRF tokens on all state-changing requests | Form-based attacks |
| Input validation | Server-side validation rules on all endpoints | Injection attacks |
| Magic-byte file validation | `valid_file_content` custom validator | Malicious file upload |
| Image metadata stripping | ImageSanitizer GD reprocessing | EXIF PII leakage + polyglot attacks |
| Role-based access control | Middleware + capability methods on User model | Unauthorized access |
| Encrypted backups | AES-256-CBC + HMAC integrity | Backup data theft |
| Security headers | SecurityHeaders middleware (CSP, HSTS, X-Frame, etc.) | Browser-based attacks |

### 5.2 Detective Controls

| Control | Implementation | Covers |
|---|---|---|
| Security audit logging | SecurityAuditLog model + middleware | All authentication/authorization events |
| HMAC log integrity | LogIntegrity service with chained hashing | Log tampering |
| Threat detection | ThreatDetector service (brute force, privilege escalation, rate anomaly) | Active attacks |
| Backup manifest | SHA-256 hash verification | Backup integrity |
| Log integrity verification | `log:verify-integrity` artisan command (daily scheduled) | Audit trail tampering |

### 5.3 Responsive Controls

| Control | Implementation | Covers |
|---|---|---|
| Account lockout | Auto-deactivation after 5 failed logins | Brute force attacks |
| Real-time alerts | SuspiciousActivityAlert email to IT Manager + Superadmin | Active threats |
| Hourly security digest | SecurityAuditReport command + SecurityAuditMail | Security event review |
| Alert deduplication | 15-minute cache-based dedup | Alert fatigue prevention |

---

## 6. Data Flow Security

### 6.1 Authentication Flow

```
Browser → [HTTPS/TLS] → ForceHttps → SecurityHeaders → Login Controller
    ↓ fail (×5)                                              ↓
ThreatDetector ← SecurityAuditLog ← Account Lockout    Validate credentials
    ↓                                                        ↓ success
Alert IT Manager                                    Session regenerate + login
+ Superadmin                                         Single-session enforcement
```

### 6.2 File Upload Flow

```
Browser → [HTTPS/TLS] → Rate limiter (10/min) → Controller
    ↓
Validation: required|file|max:10240|mimes:jpg,png,pdf|valid_file_content|sanitize_image
    ↓ image?
ImageSanitizer → GD reprocess (strip EXIF, neutralize polyglots)
    ↓
Store to private disk (storage/app/private/) with server-generated filename
    ↓
SecureFileController serves with role-based access check
```

### 6.3 Backup Flow

```
Scheduler (02:00 daily) → backup:run --type=full --encrypt
    ↓
mysqldump → gzip → AES-256-CBC encrypt + HMAC → storage/app/backups/
    ↓
tar codebase → gzip → AES-256-CBC encrypt + HMAC → storage/app/backups/
    ↓
Write SHA-256 manifest → Prune backups older than 30 days
```

---

## 7. Residual Risks & Accepted Trade-offs

| Risk | Severity | Justification | Monitoring |
|---|---|---|---|
| No MFA | Medium | Single-tenant internal system; compensated by single-session + lockout + VPN access | Login monitoring |
| Local file storage (no S3) | Medium | Synology NAS with RAID; compensated by encrypted backups + restricted permissions | Backup verification |
| No WAF | Low | Internal network (VPN-gated); compensated by application-level rate limiting + CSP | Request rate monitoring |
| Single database (no replica) | Medium | NAS RAID storage; compensated by 6-hourly DB snapshots | Backup success monitoring |

---

## 8. Review Schedule

| Activity | Frequency | Owner |
|---|---|---|
| Threat model review | Quarterly | IT Manager |
| Dependency vulnerability scan | Monthly | System Admin |
| Security audit log review | Weekly | IT Manager |
| Backup restore test | Monthly | System Admin |
| Password policy review | Annually | HR Manager + IT Manager |
| Penetration test (external) | Annually | IT Manager |

---

*Document generated: 2026-04-06 | Next review due: 2026-07-06*

---

## Appendix A — EIAAW Workforce SaaS surfaces (Sessions 6-11)

**Added:** 2026-04-24. The original threat model above is v1 Claritas (single-tenant, MySQL). This appendix covers the multi-tenant SaaS surfaces built in Sessions 6-11 on top of the v1 foundation.

## A1. New trust boundaries

```
Internet (Cloudflare edge)
  └─ apex: ep.eiaawsolutions.com (marketing + signup)  [boundary: unauthenticated visitors]
       │
       ├─ Stripe webhook POST (signature-verified)     [boundary: Stripe → our app]
       ├─ CSP violation report POST                    [boundary: browser → our app, unauthenticated]
       └─ SSO metadata GET (public per SAML spec)      [boundary: IdP admin → our app]
  └─ tenant: {slug}.ep.eiaawsolutions.com              [boundary: tenant-scoped]
       │
       ├─ SSO ACS POST (signature-verified via cert)   [boundary: IdP → our app]
       ├─ OIDC callback GET (state + nonce verified)   [boundary: IdP → our app]
       └─ AI assistant POST (authenticated)            [boundary: user → AiGateway → LLM provider]
  └─ Postgres (RLS FORCE on 80+ tables)                [boundary: app → DB, non-superuser role]
```

## A2. STRIDE per new surface

### A2.1 Apex marketing + signup

| Threat (STRIDE) | Vector | Mitigation | Status |
|---|---|---|---|
| Spoofing | Forged signup email → takeover of a target slug | Email-confirm loop; token expiry 24h; reserved-slug list includes `admin`, `api`, `www`, etc. | ✓ live |
| Tampering | Replay of used signup token | Token deleted on provision | ✓ live |
| Repudiation | Signup fraud (dispute-later) | `security_audit_logs` captures IP + UA on signup + confirm | ✓ live |
| Information disclosure | Slug enumeration via collision error messages | Generic "already taken" regardless of live/pending status | ✓ live |
| DoS | Flood signup form | `throttle:5,1` on start, `throttle:10,1` on confirm | ✓ live |
| Elevation of privilege | JIT-provisioned superadmin on signup | Controller hardcodes `role=superadmin` at *first-owner* position only; no self-upgrade path exposed | ✓ live |

### A2.2 Stripe webhook

| Threat | Vector | Mitigation | Status |
|---|---|---|---|
| Spoofing | Forged webhook POST | Cashier's `VerifyWebhookSignature` middleware (HMAC-SHA256 against `STRIPE_WEBHOOK_SECRET`) | ✓ live |
| Tampering | Replay of old Stripe event | `subscription_events` idempotency key = `stripe_event_id`; second POST is 200 OK no-op | ✓ live |
| DoS | Stripe delivery backoff if we 500 | Controller wraps side-effects in try/catch; persists event row first then processes | ✓ live |

### A2.3 SSO (SAML + OIDC)

| Threat | Vector | Mitigation | Status |
|---|---|---|---|
| Spoofing | Forged SAML assertion | SAMLResponse signature verified against tenant's configured X.509 cert; XXE disabled; DOCTYPE rejected | ✓ live |
| Spoofing | Forged OIDC ID token | RS256 signature verified against issuer's JWKS (cached 6h); `iss`/`aud`/`exp`/`nonce` checked | ✓ live |
| Tampering | Replay of captured SAML Response | `NotBefore`/`NotOnOrAfter` window checked (±30s); session `request_id` cleared on ACS | ✓ live |
| Tampering | Replay of OIDC code | `state` + `nonce` bound to session, single-use, cleared after exchange | ✓ live |
| Repudiation | User denies IdP-initiated login | Every SSO login writes `sso_login` event to `security_audit_logs` with user + tenant + IP | ✓ live |
| Information disclosure | IdP admin sees another tenant's metadata | Metadata endpoint is tenant-scoped via `ResolveTenant`; different tenant = different subdomain = different metadata XML | ✓ live |
| Elevation of privilege | IdP group → superadmin mapping | `SsoService::mapRole()` refuses to map to `superadmin`; `SsoConfigController::parseRoleMapping()` strips `superadmin` from submitted mappings; defense in depth | ✓ live |
| DoS | JWKS fetch flood | 6h cache on discovery + JWKS; 10s timeout | ✓ live |

**Known residual risk:** handwritten SAML signature verification handles common IdP cases (Entra / Okta / Google) but not every XML-DSig edge case. Documented as a Session 10+ upgrade path to a battle-tested library when SAML volume justifies the dependency.

### A2.4 AI Gateway

| Threat | Vector | Mitigation | Status |
|---|---|---|---|
| Prompt injection (LLM01) | User prompt overrides system instructions | `assertPromptSafe()` rejects 5 known-injection patterns; system prompt instructs refusal + sets `refused:true` flag | ✓ live |
| Insecure output handling (LLM02) | Model returns HTML / JS → rendered in drawer | Structured JSON output enforced; `sanitizePlainText` strips tags server-side; drawer renders `textContent` only | ✓ live (A-grade) |
| Sensitive info disclosure (LLM06) | Model answers questions it shouldn't (salary, NRIC) | Role passed to system prompt; prompt rules restrict by role; future: prompt-based redaction + retrieval-layer filtering | yellow — prompt-based only; retrieval layer is Session 12+ |
| Excessive agency (LLM08) | Model executes actions it shouldn't | Read-only v1 — no tool-use wired; system prompt instructs "tell user to do it themselves" | ✓ live |
| Cost DoS | Runaway prompts drain budget | Per-tenant monthly USD budget; cost circuit breaker (`isBudgetExhausted`); per-route rate limit `throttle:15,1` | ✓ live |
| Supply chain (LLM05) | Provider compromise | Two providers configured (Anthropic + OpenAI); switch via `AI_PROVIDER` env | yellow — fallback chain not wired automatically on provider error |
| Data leakage to training | Customer data sent to model used for training | Anthropic + OpenAI APIs do not train on customer data by default — documented in Privacy Policy §5 | ✓ confirmed in DPAs |

### A2.5 Audit export

| Threat | Vector | Mitigation | Status |
|---|---|---|---|
| Information disclosure | Operator exports another tenant's log | `audit:export --tenant=X` runs inside `TenantContext::run($tenant)` which sets Postgres RLS; cross-tenant read is rejected by DB | ✓ live |
| Tampering | Operator modifies exported JSONL before delivery | File integrity is out-of-scope; Amos to sign exports before delivery to customer if required by DPA | yellow — flagged; not shipped |
| Repudiation | Customer denies receiving export | Operator logs `audit.export` action; customer signs receipt out-of-band | manual |

### A2.6 CSP violation reports

| Threat | Vector | Mitigation | Status |
|---|---|---|---|
| DoS | Attacker floods `/csp-report` with fake violations | `throttle:60,1` rate limit; logs are summary-only (directive + blocked URI + line), not full raw reports | ✓ live |
| Information disclosure | Legit CSP report contains sensitive URL path | `CspReportController::trim()` shortens `document-uri` and `source-file` to last 120-160 chars | ✓ live |

### A2.7 Plan gating + upgrade-required

| Threat | Vector | Mitigation | Status |
|---|---|---|---|
| Elevation of privilege | Starter tenant hits `/accounting` directly | `EnsurePlan` middleware `plan:finance.accounting` checks `Tenant::hasFeature()` → redirect to `upgrade-required`; JSON 403 for XHR | ✓ live |
| Information disclosure | Upgrade-required page leaks what features other plans include | Page reads from `config/plans.php` — intentional; this is marketing content | n/a |

### A2.8 Tenant deletion pipeline

| Threat | Vector | Mitigation | Status |
|---|---|---|---|
| Repudiation | Customer disputes "you didn't delete my data" | Every deletion phase logged to `storage/logs/billing.log` + `tenant.pii_scrubbed` / `tenant.hard_purged` events | ✓ live |
| Denial of delete | Race condition — tenant reactivates during grace | Webhook `invoice.payment_succeeded` clears `past_due_at` but NOT `canceled_at` / `deleted_at`; reactivation from `canceled` state requires support workflow | ✓ by design |
| Tampering | Operator runs `billing:purge-canceled --force` prematurely | `--force` must be explicit; dry-run default; 90-day window check inside command | ✓ live |

## A3. Data classification changes since v1

| New category | Sensitivity | Examples | Storage |
|---|---|---|---|
| AI prompt / answer | Medium | Workforce-related questions + answers, cited record IDs | `ai_conversations`, redacted to `[redacted]` at deletion Phase 1 |
| SSO IdP config (incl. client secret) | High | OIDC client_secret, SAML cert (public half) | `tenants.sso_config` JSONB; client_secret masked in admin UI |
| Stripe webhook payload | Medium | Full Stripe event body | `subscription_events.payload` JSONB |
| CSP violation sample | Low | Stripped directive + blocked URI | log stream only |

## A4. Residual risk register (open items)

| Ref | Risk | Severity | Mitigation plan | Target |
| --- | --- | --- | --- | --- |
| R1 | Handwritten SAML sig verification misses an edge case | Medium | Adopt `onelogin/php-saml` once SAML tenant count > 10 | Session 12+ |
| R2 | AI retrieval layer not implemented — role-based disclosure is prompt-based only | Medium | Wire actual DB-level retrieval with role filters at query time | Session 12 |
| R3 | AI provider fallback not automatic on 5xx | Low | Wire Anthropic → OpenAI fallback with circuit breaker | Session 12+ |
| R4 | Audit export files not signed | Low | HMAC sign exports for Enterprise DPA deliveries | On first enterprise customer |
| R5 | Inline-handler CSP migration incomplete (149 handlers across 52 views) | Medium | Drive by CSP-Report-Only telemetry post-launch | Session 12 |
| R6 | No external penetration test yet | Medium | Scheduled Q4 2026 per security roadmap | Q4 2026 |

## A5. Review cadence

This appendix reviewed every session that adds a new surface; rolled into the main threat model at the next quarterly review (next due 2026-07-06).
