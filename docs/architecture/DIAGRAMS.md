# EIAAW Workforce — Architecture Diagrams

Five Mermaid diagrams covering the shipped system at Session 11 close.
Rendered in GitHub, GitLab, and most modern markdown viewers.

---

## 1. System context

Shows EIAAW Workforce sitting between its users (tenant admins, employees,
IdP admins) and its external dependencies (Stripe, Anthropic, Cloudflare
R2, Railway). Tenant-admin and employee flow through different host
surfaces; the IdP admin flow is the one that consumes the public SAML
metadata endpoint during SSO configuration.

```mermaid
graph LR
    tenantAdmin["Tenant Admin<br/>(HR Manager / Owner)"]
    employee["Employee<br/>(via {slug}.ep)"]
    prospect["Prospect<br/>(marketing apex)"]
    idp["Enterprise IdP Admin<br/>(Entra / Okta / Google)"]

    subgraph eiaaw["EIAAW Workforce"]
        apex[/"ep.eiaawsolutions.com<br/>apex (marketing + signup)"/]
        tenant[/"{slug}.ep.eiaawsolutions.com<br/>tenant workspace"/]
        apex --- tenant
    end

    stripe["Stripe<br/>billing + webhooks"]
    anthropic["Anthropic API<br/>Haiku / Sonnet"]
    openai["OpenAI API<br/>fallback"]
    cloudflare["Cloudflare R2<br/>file storage"]
    railway["Railway<br/>compute + Postgres"]
    mailgun["Mailgun<br/>transactional mail"]

    prospect --> apex
    tenantAdmin --> tenant
    employee --> tenant
    idp -.reads SAML metadata.-> tenant

    apex <-->|Cashier SDK| stripe
    tenant <-->|Cashier SDK| stripe
    stripe -->|webhook| apex
    tenant -->|AiGateway| anthropic
    tenant -.fallback.-> openai
    tenant --> cloudflare
    eiaaw --> railway
    tenant --> mailgun
```

---

## 2. Component diagram

Internal components of the Laravel monolith as of Session 11. Shows the
middleware stack, the tenant-isolation plumbing, and the service boundary
between the AI gateway + Stripe billing + SSO + audit export. The blue
boxes are the per-request middleware chain; the green boxes are services
resolved via the container.

```mermaid
graph TB
    subgraph middleware["Middleware stack (every web request)"]
        sh[SecurityHeaders<br/>CSP, HSTS, COOP]
        fh[ForceHttps]
        rt[ResolveTenant<br/>binds current_tenant<br/>SET LOCAL app.tenant_id]
        ess[EnforceSingleSession]
        ep[EnsurePlan]
        ea[EnsureApex]
    end

    subgraph controllers["HTTP controllers"]
        mc[MarketingController]
        sc[SignupController]
        ac[AuthController]
        swc[StripeWebhookController]
        sso[SsoController]
        scfg[SsoConfigController]
        aia[AiAssistantController]
        csp[CspReportController]
        ue[UpgradeRequiredController]
        fwc[FindWorkspaceController]
        ddc[DedicatedDatabaseController]
    end

    subgraph services["Services (container-resolved)"]
        tp[TenantProvisioner]
        ssvc[SsoService]
        aig[AiGateway<br/>singleton]
        tc[TenantContext]
        sas[SecurityAuditLog]
    end

    subgraph commands["Console commands"]
        c1[billing:trial-end]
        c2[billing:past-due-suspend]
        c3[billing:delete-canceled]
        c4[billing:purge-canceled]
        c5[audit:export]
        c6[tenancy:check-rls]
        c7[tenancy:test-leakage]
        c8[stripe:sync-prices]
        c9[launch:preflight]
    end

    subgraph db["Postgres (with RLS FORCE on 80+ tables)"]
        tenants[(tenants)]
        users[(users)]
        sa[(security_audit_logs)]
        ai[(ai_conversations)]
        se[(subscription_events<br/>no RLS)]
        app[(app tables × 80)]
    end

    middleware --> controllers
    sc --> tp
    ac --> sas
    sso --> ssvc
    scfg --> ssvc
    aia --> aig
    ssvc --> sas
    aig --> ai
    tp --> tc
    tc -.SET LOCAL.-> db
    commands --> db
    swc -.no tenant context.-> se

    controllers --> db
    services --> db
```

---

## 3. Sequence — signup flow

The three-step tenant signup flow. Illustrates why the flow lives on apex
(not tenant), how `TenantProvisioner` creates the tenant atomically, and
when the redirect crosses from apex to the new tenant subdomain. Stripe
is NOT called during signup (no-credit-card trial) — it's called on day
15 when the trial converts.

```mermaid
sequenceDiagram
    actor User as Prospect
    participant Apex as Marketing Apex
    participant SC as SignupController
    participant SI as signup_invites
    participant Mail as Mailgun
    participant TP as TenantProvisioner
    participant T as tenants + users
    participant TDom as tenant subdomain

    User->>Apex: GET /signup
    Apex->>SC: showForm()
    SC-->>User: form

    User->>Apex: POST /signup (email, name, company, slug)
    Apex->>SC: start()
    SC->>SI: create SignupInvite (24h token)
    SC->>Mail: SignupConfirmationMail
    Mail-->>User: email with /signup/confirm/{token}
    SC-->>User: 302 /signup/sent

    User->>Apex: GET /signup/confirm/{token}
    Apex->>SC: showConfirm()
    SC->>SI: findValidInvite(token)
    SC-->>User: password form

    User->>Apex: POST /signup/confirm/{token} (password)
    Apex->>SC: confirm()
    SC->>TP: provisionFromInvite(invite, password)
    Note over TP,T: Single transaction:<br/>1. Create Tenant (plan=growth, trial=14d)<br/>2. Create User (role=superadmin)<br/>3. Attach via tenant_users pivot
    TP-->>SC: Tenant
    SC->>SI: delete invite
    SC-->>User: 302 {slug}.ep.eiaawsolutions.com/login
    User->>TDom: login
```

---

## 4. Infrastructure diagram

Railway production deployment. Shows the two-host TLS cert (apex +
wildcard), the Postgres-inside-Railway topology, and the out-of-band
services. The `eiaaw_app` DB role is explicitly non-superuser, non-BYPASSRLS
— that's enforced by `tenancy:check-rls` at boot.

```mermaid
graph TB
    dns["DNS (Cloudflare)<br/>ep.eiaawsolutions.com<br/>*.ep.eiaawsolutions.com"]
    cf["Cloudflare Edge<br/>TLS, WAF, DDoS"]

    subgraph railway["Railway (region: us-east by default)"]
        lb["Railway Edge<br/>Apex cert + wildcard cert"]
        app1["Laravel app · replica 1"]
        app2["Laravel app · replica 2"]
        sched["Scheduler<br/>(php artisan schedule:run)"]

        subgraph pg["Postgres 16"]
            pgrole["role: eiaaw_app<br/>NOT superuser<br/>NOT BYPASSRLS"]
            pgdb[("eiaaw_workforce DB<br/>80+ tables w/ RLS FORCE")]
            pgbackup["daily encrypted backup<br/>30d retention"]
            pgsnap["6h snapshot<br/>7d retention"]
        end

        redis[(Redis<br/>cache + queue + sessions)]
    end

    r2["Cloudflare R2<br/>private file storage<br/>NRIC, contracts"]
    stripe["Stripe API<br/>+ webhook endpoint"]
    anthropic["Anthropic API"]
    mailgun["Mailgun API"]
    logs["Railway log streams"]

    dns --> cf
    cf --> lb
    lb --> app1
    lb --> app2
    app1 --> pg
    app2 --> pg
    app1 --> redis
    app2 --> redis
    sched --> pg
    app1 -.signed uploads.-> r2
    app1 -.->  stripe
    stripe -.webhook POST.-> lb
    app1 -.AiGateway.-> anthropic
    app1 -.mail send.-> mailgun
    app1 --> logs
```

---

## 5. ER — core tenant-isolation graph

The tables that matter for tenant isolation + billing + AI. Omits the 80
tenant-scoped domain tables (employees, payroll, etc.) — they all follow
the same pattern: `tenant_id bigint NOT NULL` + Postgres RLS policy using
`eiaaw_current_tenant_id()`.

```mermaid
erDiagram
    tenants ||--o{ tenant_users : "pivot"
    tenants ||--o{ users : "scoped"
    tenants ||--o{ subscriptions : "Cashier"
    tenants ||--o{ subscription_events : "Stripe audit"
    tenants ||--o{ ai_conversations : "per-tenant AI log"
    tenants ||--o{ ai_usage_daily : "daily aggregate"
    tenants ||--o{ security_audit_logs : "per-tenant audit"
    users ||--o{ tenant_users : "pivot"
    subscriptions ||--o{ subscription_items : ""
    signup_invites ||..|| tenants : "ephemeral, 24h"

    tenants {
        bigint id PK
        string slug UK
        string name
        string plan "starter|growth|scale|enterprise"
        string billing_currency "USD (Session 11)"
        int plan_seats
        timestamp trial_ends_at
        string stripe_id "Cashier customer ref"
        string stripe_customer_id "legacy alias"
        string subscription_status "active|past_due|canceled"
        timestamp past_due_at "Session 7 grace clock"
        timestamp canceled_at "Session 11 deletion clock"
        string status "active|suspended|canceled"
        timestamp suspended_at
        boolean sso_enabled
        jsonb sso_config "OIDC+SAML per-tenant"
        boolean uses_dedicated_db
        timestamp deleted_at "SoftDeletes"
    }

    users {
        bigint id PK
        bigint tenant_id FK "RLS-scoped"
        string name
        string work_email
        string password
        string role
        boolean is_active
        string session_token
        string two_factor_secret
    }

    tenant_users {
        bigint tenant_id FK
        bigint user_id FK
        string tenant_role "owner|member"
        timestamp joined_at
    }

    subscriptions {
        bigint id PK
        bigint tenant_id FK "RLS-scoped"
        string stripe_id
        string stripe_status
        string stripe_price
        int quantity
        timestamp trial_ends_at
        timestamp ends_at
    }

    subscription_events {
        bigint id PK
        bigint tenant_id
        string stripe_event_id UK "idempotency"
        string event_type
        jsonb payload
        timestamp processed_at
        string processing_error
    }

    ai_conversations {
        bigint id PK
        bigint tenant_id FK "RLS-scoped"
        bigint user_id FK
        string session_id
        string role "user|assistant"
        string model
        text content
        int input_tokens
        int output_tokens
        numeric cost_usd
        int latency_ms
    }

    ai_usage_daily {
        bigint id PK
        bigint tenant_id FK "RLS-scoped"
        date usage_date
        int input_tokens
        int output_tokens
        numeric cost_usd
        int request_count
    }

    security_audit_logs {
        bigint id PK
        bigint tenant_id FK "RLS-scoped"
        bigint user_id
        string work_email
        string role
        string event_type
        string url
        string ip_address
        text details
        boolean emailed
    }

    signup_invites {
        bigint id PK
        string work_email
        string full_name
        string company_name
        string desired_slug UK
        string plan
        string token UK
        timestamp expires_at
    }
```

---

## Maintenance

When you add a new controller / service / command, update the component
diagram. When you add a tenant-scoped table, confirm it's covered by RLS
tests but don't add it to the ER diagram unless it's foundational — 80+
domain tables would make the diagram unreadable.

Re-verify in any session that touches tenant isolation, billing, or auth.
