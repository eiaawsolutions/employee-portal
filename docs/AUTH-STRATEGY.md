# EIAAW Workforce — Auth strategy

**Decided:** 2026-04-22 (Session 2)

## The decision

**Login lives at the tenant subdomain.** A user signs in at `acme.ep.eiaawsolutions.com/login` — never at the marketing apex `ep.eiaawsolutions.com`.

The marketing apex hosts:
- `/` — public landing
- `/pricing`
- `/signup` — creates a new tenant + first owner user
- `/find-workspace` — small form ("enter your work email") → looks up which tenant(s) own that email → redirects to `acme.ep.eiaawsolutions.com/login` with email pre-filled

## Why subdomain-bound login

Three reasons:

1. **Email collisions are inevitable.** Two different companies will both have an `admin@` user. If login lived at the apex, we'd need a "which workspace?" disambiguation step in the auth flow itself — bad UX, easy to leak.
2. **Postgres RLS works correctly.** The `ResolveTenant` middleware sets the `app.tenant_id` Postgres session variable BEFORE the auth provider runs (web middleware order). Every query — including the `select users where work_email=...` query — is then constrained by RLS to the resolved tenant.
3. **Standard SaaS pattern.** Slack, Linear, Notion, Vercel all do this. Operators recognize it.

## The flow at runtime

```
Browser → https://acme.ep.eiaawsolutions.com/login
         ↓
       Cloudflare DNS  *.ep.eiaawsolutions.com → Railway
         ↓
       Laravel boots; web middleware stack runs in order:
         1. ForceHttps           → upgrade if http
         2. SecurityHeaders      → CSP / HSTS / etc.
         3. ResolveTenant        → reads "acme" from Host header
                                   → Tenant::where('slug','acme')->first()
                                   → app()->instance('current_tenant', $tenant)
                                   → DB::statement("SET LOCAL app.tenant_id = '...'")
                                   → if suspended → abort(402)
                                   → if missing  → no-op (apex/marketing route)
         4. StartSession         → loads tenant-scoped session cookie
         5. EnforceSingleSession (existing)
         6. SecurityAuditMiddleware (existing)
         7. EnforceTwoFactor (existing)
         ↓
       AuthController@showLogin renders auth/login.blade.php
         ↓
       User submits POST /login
         ↓
       WorkEmailUserProvider::retrieveByCredentials([
           'work_email' => 'admin@acme.example',
           'tenant_id'  => 7,        // ← injected by the provider
       ])
         ↓
       Eloquent query (with RLS active):
           SELECT * FROM users
           WHERE work_email = ? AND tenant_id = 7
         ↓
       Hash::check → success → SESSION created → redirect to dashboard
```

## Cookie scoping

`SESSION_DOMAIN` is intentionally LEFT BLANK in `.env.example`. Each tenant subdomain gets its own session cookie scoped to that exact host. This means:

- A logged-in user at `acme.ep.eiaawsolutions.com` is NOT logged into `bigcorp.ep.eiaawsolutions.com`.
- A potential future feature ("switch workspace" without re-auth) would require a small SSO-style token-issuing flow — explicitly out of scope for v1.

## Marketing apex routes

The marketing apex (`ep.eiaawsolutions.com`) and tenant subdomains share the same Laravel application but different route groups. Wk2 will introduce a route guard that:

- 404s tenant-scoped routes when no tenant resolved (e.g., visiting `/dashboard` on the apex)
- 404s marketing routes when a tenant IS resolved (e.g., visiting `/pricing` on `acme.`)

For Session 2, no apex-only routes exist yet, so the existing route file works on both contexts.

## Schema implication (deferred to Session 3)

The `users` table needs `tenant_id bigint REFERENCES tenants(id) ON DELETE CASCADE`. Today the unique constraint is `(work_email)`; it must become `(tenant_id, work_email)`. Implementation:

1. Add migration: add `tenant_id` column (nullable initially), backfill existing Claritas users to a single dedicated tenant, then alter to NOT NULL.
2. Drop the global `work_email` unique index, add `(tenant_id, work_email)` unique index.
3. Apply the `BelongsToTenant` trait to `User`.
4. Apply Postgres RLS to the `users` table.

The `WorkEmailUserProvider` is already future-compatible — it uses `Schema::hasColumn('users', 'tenant_id')` to safely no-op the scoping until that migration ships.

## What about superadmin / cross-tenant users?

EIAAW Solutions internal staff (you, Amos, eventually a support engineer) need to operate across tenants for billing audits, support investigation, etc. Two options:

- **(a) Stack-an-admin tenant** — provision a dedicated `eiaaw-admin.ep.eiaawsolutions.com` workspace whose users have a special `is_platform_admin` boolean flag. Routes guarded by that flag use `Model::withoutGlobalScope(TenantScope::class)` to query across tenants. Postgres RLS for these users uses a SECURITY DEFINER function.
- **(b) Separate admin app** — a totally separate Laravel codebase at `admin.eiaawsolutions.com` connecting to the same Postgres with a different DB user that bypasses RLS.

**Recommendation: (a)** for v1 (less moving parts). Move to (b) only when EIAAW Solutions hires a non-Amos support engineer who shouldn't have the same level of access as the founder.

## Failure modes worth listing

| Scenario | Behavior |
|---|---|
| User visits `bigcorp.ep.eiaawsolutions.com` but no `bigcorp` tenant exists | `app('current_tenant')` not bound → no auth scoping → login query returns nothing → "Invalid credentials" (no tenant enumeration leak) |
| User logs into tenant A then somehow gets a session cookie for tenant B | `retrieveById()` checks `user.tenant_id !== current.tenant_id` → returns null → forced re-login |
| Tenant B is suspended | `ResolveTenant` aborts 402 with friendly message before any controller sees the request |
| ResolveTenant fails to set `SET LOCAL app.tenant_id` (e.g., not Postgres connection) | Middleware silently skips — falls back to app-layer scoping via TenantScope. Defense-in-depth degrades gracefully. |
| Forgotten password email sent from tenant A | The reset URL includes `?email=` which the password broker matches against `(tenant_id, work_email)` — the reset link is implicitly tenant-scoped because the click lands on the same subdomain that issued it. |
