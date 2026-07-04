@extends('layouts.marketing')

@section('title', 'Security — EIAAW Workforce')
@section('description', 'Postgres Row-Level Security, HMAC-chained audit log, encrypted-at-rest storage, PDPA-aligned. The security architecture behind EIAAW Workforce.')

@push('head')
<style>
    .sec-hero {
        padding: clamp(60px, 8vw, 100px) 0 clamp(36px, 5vw, 56px);
    }
    .sec-hero-grid {
        display: grid; grid-template-columns: 1.5fr 1fr;
        gap: clamp(28px, 4vw, 64px); align-items: end;
    }
    @media (max-width: 860px) { .sec-hero-grid { grid-template-columns: 1fr; } }
    .sec-hero h1 { margin: 14px 0 22px; }
    .sec-hero p { color: var(--ink-2); font-size: 17px; max-width: 560px; }
    .sec-hero-badges {
        display: flex; flex-wrap: wrap; gap: 10px;
        padding-bottom: 10px;
    }
    .sec-badge {
        display: inline-flex; align-items: center; gap: 10px;
        padding: 10px 16px;
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 14px;
        font-size: 13px; color: var(--ink);
    }
    .sec-badge::before {
        content: ''; width: 8px; height: 8px; border-radius: 50%;
        background: var(--success);
    }

    /* ── Architecture diagram ── */
    .sec-diagram {
        background: var(--surface);
        border: 1px solid var(--line-soft);
        border-radius: 20px;
        padding: clamp(28px, 4vw, 48px);
        margin-top: clamp(40px, 5vw, 64px);
    }
    .sec-diagram-head {
        font-family: var(--mono); font-size: 11px;
        text-transform: uppercase; letter-spacing: 0.14em;
        color: var(--mute); margin-bottom: 32px;
    }

    .sec-arch {
        display: flex; flex-direction: column; gap: 0;
    }
    .sec-arch-layer {
        padding: 20px 24px;
        border: 1px solid var(--line-soft);
        border-bottom: 0;
        display: grid;
        grid-template-columns: 140px 1fr auto;
        gap: 24px; align-items: center;
        position: relative;
    }
    .sec-arch-layer:first-child { border-radius: 14px 14px 0 0; }
    .sec-arch-layer:last-child { border-bottom: 1px solid var(--line-soft); border-radius: 0 0 14px 14px; }
    .sec-arch-layer .layer-tag {
        font-family: var(--mono); font-size: 10.5px;
        text-transform: uppercase; letter-spacing: 0.14em;
        color: var(--primary-dark);
    }
    .sec-arch-layer .layer-name {
        font-family: var(--sans); font-weight: 500; font-size: 16px;
        color: var(--ink); letter-spacing: -0.01em;
    }
    .sec-arch-layer .layer-name small {
        display: block; font-size: 13px; font-weight: 400;
        color: var(--ink-2); margin-top: 4px; letter-spacing: 0;
    }
    .sec-arch-layer .layer-check {
        font-family: var(--mono); font-size: 11px;
        color: var(--success); letter-spacing: 0.08em;
        white-space: nowrap;
    }
    .sec-arch-layer .layer-check::before { content: '✓ '; }

    .sec-arch-layer--strong {
        background: var(--bg-warm);
        border-color: var(--primary);
        border-width: 2px;
    }
    .sec-arch-layer--strong + .sec-arch-layer { border-top: 0; }
    .sec-arch-layer--strong .layer-tag { color: var(--primary-dark); }
    .sec-arch-layer--strong .layer-name { color: var(--primary-dark); }

    @media (max-width: 640px) {
        .sec-arch-layer { grid-template-columns: 1fr; gap: 6px; }
        .sec-arch-layer .layer-check { margin-top: 4px; }
    }

    /* ── Audit log sample ── */
    .sec-audit {
        background: var(--ink);
        color: var(--bg);
        border-radius: 20px;
        padding: clamp(28px, 4vw, 48px);
        margin-top: clamp(40px, 5vw, 64px);
        position: relative; overflow: hidden;
    }
    .sec-audit::before {
        content: ''; position: absolute;
        inset: 0;
        background: radial-gradient(50% 80% at 100% 0%, rgba(34,184,165,0.12), transparent 70%);
        pointer-events: none;
    }
    .sec-audit-head {
        position: relative; z-index: 1;
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;
        gap: 12px;
        padding-bottom: 20px;
        border-bottom: 1px solid rgba(255,255,255,0.12);
    }
    .sec-audit-head-left { display: flex; gap: 14px; align-items: center; }
    .sec-audit-head-pill {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 5px 12px; border-radius: 999px;
        background: rgba(255,255,255,0.06);
        font-family: var(--mono); font-size: 11px;
        text-transform: uppercase; letter-spacing: 0.14em;
        color: rgba(255,255,255,0.7);
    }
    .sec-audit-head-pill::before {
        content: ''; width: 8px; height: 8px; border-radius: 50%;
        background: var(--primary); box-shadow: 0 0 10px var(--primary);
    }
    .sec-audit pre {
        position: relative; z-index: 1;
        margin: 20px 0 0;
        font-family: var(--mono); font-size: 12.5px; line-height: 1.7;
        color: rgba(255,255,255,0.85);
        white-space: pre;
        overflow-x: auto;
    }
    .sec-audit pre .k { color: #6BC9B8; }
    .sec-audit pre .s { color: #E8DFCC; }
    .sec-audit pre .n { color: #C68A2E; }
    .sec-audit pre .c { color: rgba(255,255,255,0.45); }

    /* ── Compliance roadmap ── */
    .sec-roadmap {
        margin-top: clamp(60px, 8vw, 96px);
    }
    .sec-roadmap-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 0;
        border: 1px solid var(--line-soft);
        border-radius: 20px; overflow: hidden;
        background: var(--line-soft);
    }
    @media (max-width: 760px) { .sec-roadmap-grid { grid-template-columns: 1fr; } }
    .sec-roadmap-cell {
        background: var(--surface);
        padding: 28px 32px;
        border: 0;
        display: flex; flex-direction: column; gap: 14px;
    }
    .sec-roadmap-cell h3 {
        font-family: var(--sans); font-weight: 500; font-size: 18px;
        letter-spacing: -0.01em; margin: 0; color: var(--ink);
        display: flex; align-items: center; gap: 12px;
    }
    .sec-roadmap-cell h3 .status {
        font-family: var(--mono); font-size: 10.5px;
        text-transform: uppercase; letter-spacing: 0.14em;
        padding: 3px 9px; border-radius: 999px;
    }
    .sec-roadmap-cell h3 .status--live  { background: rgba(47,140,110,0.12); color: var(--success); }
    .sec-roadmap-cell h3 .status--q3    { background: var(--primary-tint); color: var(--primary-dark); }
    .sec-roadmap-cell h3 .status--q4    { background: rgba(198,138,46,0.14); color: var(--warn); }
    .sec-roadmap-cell p { color: var(--ink-2); font-size: 14px; line-height: 1.55; margin: 0; }

    .sec-principles {
        display: grid; grid-template-columns: repeat(3, 1fr);
        gap: 20px; margin-top: clamp(40px, 5vw, 64px);
    }
    @media (max-width: 860px) { .sec-principles { grid-template-columns: 1fr; } }
    .sec-principle {
        background: var(--surface);
        border: 1px solid var(--line-soft);
        border-radius: 16px;
        padding: 24px;
    }
    .sec-principle h4 {
        font-family: var(--sans); font-weight: 500; font-size: 15px;
        margin: 14px 0 8px; letter-spacing: -0.005em; color: var(--ink);
    }
    .sec-principle p { font-size: 13.5px; color: var(--ink-2); line-height: 1.55; margin: 0; }
    .sec-principle .eyebrow { margin-bottom: 0; }
</style>
@endpush

@section('content')

<section class="sec-hero">
    <div class="mk-container">
        <div class="sec-hero-grid">
            <div>
                <span class="eyebrow">Security</span>
                <h1 class="mk-display">Tenant data walled off at the <em>database.</em></h1>
                <p>Most SaaS multi-tenancy is enforced in the application — one forgotten `where tenant_id = ?` and a bug becomes a breach. EIAAW Workforce enforces isolation in Postgres Row-Level Security, at the driver level. Controllers can't forget what the database enforces.</p>
            </div>
            <div class="sec-hero-badges">
                <span class="sec-badge">Postgres RLS enforced</span>
                <span class="sec-badge">PDPA-aligned</span>
                <span class="sec-badge">HMAC audit chain</span>
                <span class="sec-badge">MFA &amp; session rotation</span>
            </div>
        </div>

        <div class="sec-diagram">
            <div class="sec-diagram-head">Architecture · Layered enforcement</div>
            <div class="sec-arch">
                <div class="sec-arch-layer">
                    <span class="layer-tag">L1 · Edge</span>
                    <span class="layer-name">Cloudflare + Railway TLS
                        <small>HTTPS enforced, HSTS preloaded, WAF rules on signup &amp; webhooks.</small>
                    </span>
                    <span class="layer-check">Active</span>
                </div>
                <div class="sec-arch-layer">
                    <span class="layer-tag">L2 · App</span>
                    <span class="layer-name">Laravel middleware stack
                        <small>SecurityHeaders + CSP + EnforceSingleSession + SecurityAuditMiddleware.</small>
                    </span>
                    <span class="layer-check">Active</span>
                </div>
                <div class="sec-arch-layer">
                    <span class="layer-tag">L3 · Tenant</span>
                    <span class="layer-name">ResolveTenant middleware
                        <small>Binds current tenant from subdomain → SET LOCAL app.tenant_id on the Postgres connection.</small>
                    </span>
                    <span class="layer-check">Active</span>
                </div>
                <div class="sec-arch-layer sec-arch-layer--strong">
                    <span class="layer-tag">L4 · Database</span>
                    <span class="layer-name">Postgres ROW-LEVEL SECURITY (FORCE)
                        <small>Every tenant-tagged table rejects queries that don't match the session's tenant_id. Fails CLOSED on unset.</small>
                    </span>
                    <span class="layer-check">Enforced</span>
                </div>
                <div class="sec-arch-layer">
                    <span class="layer-tag">L5 · CI</span>
                    <span class="layer-name">tenancy:check-rls + tenancy:test-leakage
                        <small>Boot check: the DB role must NOT have BYPASSRLS. Integration test: 22 cross-tenant leakage assertions on every deploy.</small>
                    </span>
                    <span class="layer-check">Passing</span>
                </div>
            </div>
        </div>

        <div class="sec-audit">
            <div class="sec-audit-head">
                <div class="sec-audit-head-left">
                    <span class="sec-audit-head-pill">Audit log · HMAC-chained</span>
                </div>
                <div style="font-family: var(--mono); font-size: 11px; color: rgba(255,255,255,0.55); letter-spacing: 0.08em;">
                    Sample · tenant #1 · last 4 events
                </div>
            </div>
<pre><span class="c"># tamper-evident: each row's HMAC chains the previous row's HMAC.</span>
<span class="c"># flipping any historical row invalidates every row after it.</span>

{
  <span class="k">"id"</span>: <span class="n">0x4F9A</span>,
  <span class="k">"at"</span>: <span class="s">"2026-04-23T14:22:08+08:00"</span>,
  <span class="k">"actor"</span>: <span class="s">"user:142 (Hanna Tan, Finance Manager)"</span>,
  <span class="k">"action"</span>: <span class="s">"claim.approve"</span>,
  <span class="k">"target"</span>: <span class="s">"claim:CLM-0412 (RM 342)"</span>,
  <span class="k">"ip"</span>: <span class="s">"203.82.xx.xx"</span>,
  <span class="k">"ua_hash"</span>: <span class="s">"a1e8...c402"</span>,
  <span class="k">"prev_hmac"</span>: <span class="s">"e7f2...91ab"</span>,
  <span class="k">"hmac"</span>: <span class="s">"b4c1...7d28"</span>
}
{
  <span class="k">"id"</span>: <span class="n">0x4F9B</span>,
  <span class="k">"at"</span>: <span class="s">"2026-04-23T14:22:11+08:00"</span>,
  <span class="k">"actor"</span>: <span class="s">"system"</span>,
  <span class="k">"action"</span>: <span class="s">"ledger.post (auto)"</span>,
  <span class="k">"target"</span>: <span class="s">"journal_entry:JE-8810"</span>,
  <span class="k">"source"</span>: <span class="s">"claim:CLM-0412"</span>,
  <span class="k">"prev_hmac"</span>: <span class="s">"b4c1...7d28"</span>,
  <span class="k">"hmac"</span>: <span class="s">"c302...4fde"</span>
}
{
  <span class="k">"id"</span>: <span class="n">0x4F9C</span>,
  <span class="k">"at"</span>: <span class="s">"2026-04-23T14:25:52+08:00"</span>,
  <span class="k">"actor"</span>: <span class="s">"user:88 (Aisha Rahman)"</span>,
  <span class="k">"action"</span>: <span class="s">"ai.query"</span>,
  <span class="k">"prompt_summary"</span>: <span class="s">"Who is OOO next week?"</span>,
  <span class="k">"tokens"</span>: <span class="n">482</span>,
  <span class="k">"cost_myr"</span>: <span class="n">0.0031</span>,
  <span class="k">"prev_hmac"</span>: <span class="s">"c302...4fde"</span>,
  <span class="k">"hmac"</span>: <span class="s">"d5ea...0b11"</span>
}</pre>
        </div>

        <div class="sec-principles">
            <div class="sec-principle">
                <span class="eyebrow">Principle 01</span>
                <h4>Fail closed</h4>
                <p>If the tenant isn't set, the database rejects the query. There is no fallback that "helpfully" returns all rows.</p>
            </div>
            <div class="sec-principle">
                <span class="eyebrow">Principle 02</span>
                <h4>Least privilege</h4>
                <p>The DB role used by the app is NOT a superuser and does NOT have BYPASSRLS. Boot check refuses to start if this changes.</p>
            </div>
            <div class="sec-principle">
                <span class="eyebrow">Principle 03</span>
                <h4>Tamper-evident</h4>
                <p>Every security-relevant action (auth, approval, AI query, export) is HMAC-chained so historical forgery is detectable.</p>
            </div>
        </div>

        <div class="sec-roadmap">
            <h2 style="font-family: var(--sans); font-weight: 500; font-size: clamp(28px, 3.4vw, 40px); line-height: 1.1; letter-spacing: -0.025em; margin: 0 0 20px;">
                Compliance <em style="font-family: var(--serif); font-style: italic; color: var(--primary-dark);">roadmap.</em>
            </h2>
            <p style="color: var(--ink-2); font-size: 15.5px; max-width: 580px; margin: 0 0 32px;">
                We list what's live, what's in flight, and what's planned — no "bank-grade security" marketing language.
            </p>

            <div class="sec-roadmap-grid">
                <div class="sec-roadmap-cell">
                    <h3>PDPA alignment <span class="status status--live">Live</span></h3>
                    <p>Data-subject access, correction, and deletion request flows. Data residency opt-in for MY. DPO contact published.</p>
                </div>
                <div class="sec-roadmap-cell">
                    <h3>Encryption at rest &amp; in transit <span class="status status--live">Live</span></h3>
                    <p>TLS 1.3 enforced. Postgres AES-256 disk encryption. File uploads encrypted on private disk. Secrets in Railway's encrypted env vars.</p>
                </div>
                <div class="sec-roadmap-cell">
                    <h3>HMAC audit log integrity <span class="status status--live">Live</span></h3>
                    <p>Daily `log:verify-integrity` command walks the HMAC chain; any tamper invalidates the run and notifies the ops channel.</p>
                </div>
                <div class="sec-roadmap-cell">
                    <h3>SOC 2 Type I readiness <span class="status status--q3">Planned</span></h3>
                    <p>Controls documented against AICPA Trust Service Criteria (Security, Availability, Confidentiality). Not yet certified — talk to us for where the formal program stands.</p>
                </div>
                <div class="sec-roadmap-cell">
                    <h3>SAML 2.0 + OIDC SSO <span class="status status--q3">Enterprise</span></h3>
                    <p>Enterprise tier. IdP-initiated and SP-initiated flows. Contact us to scope your identity provider.</p>
                </div>
                <div class="sec-roadmap-cell">
                    <h3>Penetration test (external) <span class="status status--q4">Planned</span></h3>
                    <p>External pentest by an accredited firm. Report summary published; findings treated per severity with SLAs.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mk-section" style="text-align: center;">
    <div class="mk-container mk-container--narrow">
        <h2 style="font-family: var(--sans); font-weight: 500; font-size: clamp(30px, 4vw, 48px); line-height: 1.05; letter-spacing: -0.025em; margin: 0 0 22px;">
            Security questions? <em style="font-family: var(--serif); font-style: italic; color: var(--primary-dark);">Talk to a human.</em>
        </h2>
        <p style="color: var(--ink-2); font-size: 16px; max-width: 520px; margin: 0 auto 28px;">
            DPAs, data-residency addenda, pentest summaries, and security-questionnaire responses — we answer before the contract.
        </p>
        <a href="mailto:{{ config('eiaaw.support_email') }}?subject=EIAAW Workforce · Security review" class="eiaaw-btn eiaaw-btn--outline">Email security@</a>
    </div>
</section>

@endsection
