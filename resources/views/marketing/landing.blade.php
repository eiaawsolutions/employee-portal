@extends('layouts.marketing')

@section('title', 'EIAAW Workforce — AI · Human Partnerships for HR, Payroll & Finance')
@section('description', 'The AI-native workforce platform for Malaysian and APAC mid-market teams. HR, payroll, claims, IT assets, and full accounting on one backbone. 14-day trial, no credit card.')

@push('head')
<style>
    /* ── Hero ── */
    .ln-hero {
        padding: clamp(72px, 10vw, 140px) 0 clamp(72px, 10vw, 120px);
        position: relative; overflow: hidden;
    }
    .ln-hero::before {
        content: ''; position: absolute;
        inset: auto -10% -30% -10%;
        height: 420px;
        background:
            radial-gradient(60% 60% at 20% 40%, rgba(31,168,150,0.10), transparent 70%),
            radial-gradient(50% 50% at 80% 60%, rgba(17,118,106,0.08), transparent 70%);
        pointer-events: none; z-index: 0;
    }
    .ln-hero-grid {
        display: grid;
        grid-template-columns: 1.35fr 1fr;
        gap: clamp(32px, 5vw, 80px);
        align-items: end;
        position: relative; z-index: 1;
    }
    @media (max-width: 960px) {
        .ln-hero-grid { grid-template-columns: 1fr; gap: 40px; }
    }
    .ln-hero-meta { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; margin-bottom: 28px; }
    .ln-hero-meta .mk-pill {
        background: var(--surface); color: var(--primary-dark);
        border: 1px solid var(--line);
    }
    .ln-hero h1 { margin-bottom: 28px; }
    .ln-hero-lede {
        font-size: clamp(17px, 1.7vw, 20px);
        line-height: 1.55;
        color: var(--ink-2);
        max-width: 540px;
        margin-bottom: 36px;
    }
    .ln-hero-ctas { display: flex; gap: 14px; flex-wrap: wrap; align-items: center; }
    .ln-hero-meta-side {
        display: flex; flex-direction: column; gap: 18px;
        padding-bottom: 8px;
    }
    .ln-hero-stat {
        font-family: var(--serif); font-size: clamp(40px, 4.5vw, 58px);
        line-height: 0.95; color: var(--ink);
        font-style: italic; font-weight: 400;
        letter-spacing: -0.02em;
    }
    .ln-hero-stat-label {
        font-family: var(--mono); font-size: 11px;
        text-transform: uppercase; letter-spacing: 0.14em;
        color: var(--mute); margin-top: 10px;
    }

    /* ── Social proof strip ── */
    .ln-proof {
        border-top: 1px solid var(--line-soft);
        border-bottom: 1px solid var(--line-soft);
        padding: 40px 0;
        background: var(--bg-warm);
    }
    .ln-proof-inner {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: clamp(24px, 4vw, 56px);
        align-items: center;
    }
    @media (max-width: 760px) {
        .ln-proof-inner { grid-template-columns: 1fr; }
    }
    .ln-proof-label {
        font-family: var(--mono); font-size: 11px;
        text-transform: uppercase; letter-spacing: 0.14em;
        color: var(--mute); max-width: 200px;
    }
    .ln-proof-modules {
        display: flex; flex-wrap: wrap; gap: 28px;
        font-family: var(--sans); font-size: 14px; font-weight: 500;
        color: var(--ink-2);
    }
    .ln-proof-modules span { display: inline-flex; align-items: center; gap: 10px; }
    .ln-proof-modules span::before {
        content: ''; width: 8px; height: 8px; border-radius: 50%;
        background: var(--primary); opacity: 0.7;
    }

    /* ── Editorial three-up ── */
    .ln-three {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1px;
        background: var(--line-soft);
        border: 1px solid var(--line-soft);
        border-radius: 20px; overflow: hidden;
    }
    @media (max-width: 860px) {
        .ln-three { grid-template-columns: 1fr; }
    }
    .ln-three-cell {
        background: var(--surface);
        padding: clamp(32px, 3.5vw, 48px);
        display: flex; flex-direction: column; gap: 18px;
    }
    .ln-three-cell .mk-pill { align-self: flex-start; }
    .ln-three-cell h3 {
        font-family: var(--sans); font-weight: 500;
        font-size: clamp(22px, 2vw, 28px); line-height: 1.15;
        letter-spacing: -0.02em; margin: 0; color: var(--ink);
    }
    .ln-three-cell h3 em { font-family: var(--serif); font-style: italic; font-weight: 400; color: var(--primary-dark); }
    .ln-three-cell p { color: var(--ink-2); font-size: 14.5px; margin: 0; line-height: 1.6; }

    /* ── Section header ── */
    .ln-sec-head {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: clamp(24px, 4vw, 80px);
        align-items: end;
        margin-bottom: clamp(36px, 5vw, 64px);
    }
    @media (max-width: 860px) { .ln-sec-head { grid-template-columns: 1fr; gap: 18px; } }
    .ln-sec-head h2 {
        font-family: var(--sans); font-weight: 500;
        font-size: clamp(32px, 4vw, 52px); line-height: 1.05;
        letter-spacing: -0.025em; margin: 14px 0 0; color: var(--ink);
    }
    .ln-sec-head h2 em { font-family: var(--serif); font-style: italic; font-weight: 400; color: var(--primary-dark); }
    .ln-sec-head p { color: var(--ink-2); font-size: 17px; line-height: 1.55; margin: 0; }

    /* ── AI demo block ── */
    .ln-ai {
        background: var(--ink);
        color: var(--bg);
        border-radius: 24px;
        padding: clamp(40px, 5vw, 72px);
        display: grid;
        grid-template-columns: 1fr 1.15fr;
        gap: clamp(28px, 5vw, 72px);
        align-items: center;
        position: relative; overflow: hidden;
    }
    @media (max-width: 960px) { .ln-ai { grid-template-columns: 1fr; } }
    .ln-ai::before {
        content: ''; position: absolute; inset: 0;
        background: radial-gradient(40% 60% at 80% 0%, rgba(34,184,165,0.22), transparent 70%);
        pointer-events: none;
    }
    .ln-ai-copy { position: relative; z-index: 1; }
    .ln-ai-copy .eyebrow { color: var(--primary); }
    .ln-ai-copy .eyebrow::before { background: currentColor; opacity: 0.6; }
    .ln-ai-copy h2 {
        font-family: var(--sans); font-weight: 500;
        font-size: clamp(28px, 3.2vw, 44px); line-height: 1.1;
        letter-spacing: -0.025em; margin: 16px 0 22px; color: var(--bg);
    }
    .ln-ai-copy h2 em { font-family: var(--serif); font-style: italic; font-weight: 400; color: var(--primary); }
    .ln-ai-copy p { color: #CBD4D6; font-size: 15.5px; line-height: 1.6; margin: 0 0 24px; max-width: 460px; }
    .ln-ai-copy ul { list-style: none; padding: 0; margin: 0 0 32px; display: flex; flex-direction: column; gap: 10px; }
    .ln-ai-copy li { color: #CBD4D6; font-size: 14.5px; padding-left: 24px; position: relative; line-height: 1.5; }
    .ln-ai-copy li::before {
        content: ''; position: absolute; left: 0; top: 8px;
        width: 14px; height: 1px; background: var(--primary); opacity: 0.7;
    }
    .ln-ai-mock {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.09);
        border-radius: 16px;
        padding: 24px;
        font-family: var(--sans); font-size: 14px;
        display: flex; flex-direction: column; gap: 14px;
        position: relative; z-index: 1;
        backdrop-filter: blur(8px);
    }
    .ln-ai-mock-head {
        display: flex; align-items: center; gap: 10px;
        padding-bottom: 14px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        font-family: var(--mono); font-size: 11px;
        text-transform: uppercase; letter-spacing: 0.14em;
        color: rgba(255,255,255,0.55);
    }
    .ln-ai-mock-head-dot {
        width: 8px; height: 8px; border-radius: 50%;
        background: var(--primary); box-shadow: 0 0 10px var(--primary);
    }
    .ln-ai-bubble-user {
        align-self: flex-end; max-width: 82%;
        padding: 10px 14px; border-radius: 14px;
        background: rgba(255,255,255,0.08);
        color: var(--bg);
    }
    .ln-ai-bubble-ai {
        align-self: flex-start; max-width: 88%;
        padding: 12px 16px; border-radius: 14px;
        background: var(--gradient);
        color: var(--bg);
        box-shadow: 0 6px 24px rgba(17,118,106,0.3);
    }
    .ln-ai-bubble-ai strong { color: var(--bg); font-weight: 600; }

    /* ── Pricing teaser ── */
    .ln-prc {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1px;
        background: var(--line-soft);
        border: 1px solid var(--line-soft);
        border-radius: 20px; overflow: hidden;
    }
    @media (max-width: 960px) { .ln-prc { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 540px) { .ln-prc { grid-template-columns: 1fr; } }
    .ln-prc-cell {
        background: var(--surface);
        padding: 32px 28px;
        display: flex; flex-direction: column; gap: 10px;
    }
    .ln-prc-cell.featured { background: var(--bg-warm); }
    .ln-prc-cell-name {
        font-family: var(--mono); font-size: 11px;
        text-transform: uppercase; letter-spacing: 0.14em;
        color: var(--mute);
    }
    .ln-prc-cell-price {
        font-family: var(--sans); font-weight: 500;
        font-size: clamp(30px, 3vw, 38px); line-height: 1;
        color: var(--ink); letter-spacing: -0.02em;
    }
    .ln-prc-cell-price small {
        font-family: var(--mono); font-size: 11px; font-weight: 400;
        color: var(--mute); text-transform: uppercase; letter-spacing: 0.1em;
        margin-left: 4px;
    }
    .ln-prc-cell-sub {
        font-size: 13.5px; color: var(--ink-2); line-height: 1.5;
    }

    /* ── Final CTA ── */
    .ln-cta {
        text-align: center;
        padding: clamp(80px, 10vw, 140px) 0;
    }
    .ln-cta h2 {
        font-family: var(--sans); font-weight: 500;
        font-size: clamp(40px, 5.5vw, 72px); line-height: 1.02;
        letter-spacing: -0.03em; margin: 0 auto 24px;
        max-width: 820px; color: var(--ink);
    }
    .ln-cta h2 em { font-family: var(--serif); font-style: italic; font-weight: 400; color: var(--primary-dark); }
    .ln-cta p { font-size: 17px; color: var(--ink-2); max-width: 540px; margin: 0 auto 36px; }
    .ln-cta-row { display: inline-flex; gap: 14px; flex-wrap: wrap; justify-content: center; }
    .ln-cta-note {
        font-family: var(--mono); font-size: 11px;
        text-transform: uppercase; letter-spacing: 0.14em;
        color: var(--mute); margin-top: 28px;
    }
</style>
@endpush

@section('content')

<section class="ln-hero">
    <div class="mk-container">
        <div class="ln-hero-grid">
            <div>
                <div class="ln-hero-meta">
                    <span class="mk-pill"><span class="mk-pill-dot"></span>AI · Human Partnerships</span>
                    <span style="font-family: var(--mono); font-size: 11px; color: var(--mute); text-transform: uppercase; letter-spacing: 0.14em;">Built for Malaysia &amp; APAC</span>
                </div>
                <h1 class="mk-display">
                    Run HR, payroll, and finance <em>on one backbone</em> — with an AI partner that actually reads the data.
                </h1>
                <p class="ln-hero-lede">
                    EIAAW Workforce is the AI-native platform for mid-market teams who outgrew spreadsheets but refuse the folksy SME tools. Four modules, one tenant, zero duplicated data — and an assistant that summarises approvals, drafts offboarding plans, and explains payslips in plain English.
                </p>
                <div class="ln-hero-ctas">
                    <a href="{{ route('signup.form') }}" class="eiaaw-btn eiaaw-btn--primary">Start 14-day trial · no credit card</a>
                    <a href="{{ route('marketing.features') }}" class="eiaaw-btn eiaaw-btn--outline">See every feature →</a>
                </div>
            </div>

            <aside class="ln-hero-meta-side" aria-label="Product at a glance">
                <div>
                    <div class="ln-hero-stat">4 modules</div>
                    <div class="ln-hero-stat-label">HR · Payroll · IT Assets · Accounting</div>
                </div>
                <div>
                    <div class="ln-hero-stat">Postgres RLS</div>
                    <div class="ln-hero-stat-label">Database-enforced tenant isolation</div>
                </div>
                <div>
                    <div class="ln-hero-stat">14 days</div>
                    <div class="ln-hero-stat-label">Growth-tier trial · no card required</div>
                </div>
            </aside>
        </div>
    </div>
</section>

<section class="ln-proof">
    <div class="mk-container ln-proof-inner">
        <div class="ln-proof-label">Four modules, one tenant, zero duplicated data:</div>
        <div class="ln-proof-modules">
            <span>M1 Employee Journey</span>
            <span>M2 Asset Management</span>
            <span>M3 HRM</span>
            <span>M4 Finance</span>
            <span>AI · Human Partnerships</span>
        </div>
    </div>
</section>

<section class="mk-section">
    <div class="mk-container">
        <div class="ln-sec-head">
            <div class="eyebrow">Why EIAAW Workforce</div>
            <div>
                <h2>The spreadsheet exit, <em>without the SaaS sprawl.</em></h2>
                <p style="max-width: 620px; margin-top: 24px;">Three architectural choices the "friendly SME" tools don't make.</p>
            </div>
        </div>

        <div class="ln-three">
            <article class="ln-three-cell">
                <span class="mk-pill"><span class="mk-pill-dot"></span>01 · Isolation</span>
                <h3>Tenant data is walled off at the <em>database</em>, not the app.</h3>
                <p>Postgres Row-Level Security enforces `tenant_id` on every query at the driver level. No accidental cross-tenant leak is possible, even through a bugged controller.</p>
            </article>
            <article class="ln-three-cell">
                <span class="mk-pill"><span class="mk-pill-dot"></span>02 · One backbone</span>
                <h3>Payroll reads from HR, claims post to the ledger, <em>no exports.</em></h3>
                <p>Every module shares the same employee, the same approver chain, the same audit log. When someone offboards, the whole org-chart, assets, and claims react at once.</p>
            </article>
            <article class="ln-three-cell">
                <span class="mk-pill"><span class="mk-pill-dot"></span>03 · AI with receipts</span>
                <h3>The assistant cites <em>which records</em> it read before answering.</h3>
                <p>Every AI answer is retrieval-grounded on your tenant's data with row-level citations. Hallucinations are caught at the gate — no inventing policies, no inventing payslips.</p>
            </article>
        </div>
    </div>
</section>

<section class="mk-section mk-section--tight">
    <div class="mk-container">
        <div class="ln-ai">
            <div class="ln-ai-copy">
                <div class="eyebrow">AI · Human Partnerships</div>
                <h2>An assistant that knows <em>who is on leave next week</em> — because it reads your actual data.</h2>
                <p>Included in every tier. Retrieval-grounded on your tenant. Cites the records it used. Respects role-based access. Costs are capped per tenant with a circuit breaker so a runaway prompt never shocks your bill.</p>
                <ul>
                    <li>Summarises pending approvals across leave, claims, and AARFs</li>
                    <li>Drafts offboarding checklists from the employee's actual role + assets</li>
                    <li>Explains the delta in this month's payslip in plain language</li>
                    <li>Surfaces anomalies — duplicate claims, late attendance, budget drift</li>
                </ul>
                <a href="{{ route('marketing.features') }}" class="eiaaw-btn" style="background: var(--primary); color: var(--bg); border-color: var(--primary);">
                    See the assistant in detail →
                </a>
            </div>

            <div class="ln-ai-mock" aria-label="Example AI conversation">
                <div class="ln-ai-mock-head">
                    <span class="ln-ai-mock-head-dot"></span>
                    <span>Workforce Assistant · Haiku 4.5</span>
                </div>
                <div class="ln-ai-bubble-user">Who's OOO next week and what's blocked on them?</div>
                <div class="ln-ai-bubble-ai">
                    <strong>3 people on leave next week (Apr 27 – May 3):</strong><br>
                    · Aisha Rahman (Finance) — Mon–Wed · approval queue: 2 invoices<br>
                    · Daniel Lim (IT) — Thu–Fri · AARF pending: 1 laptop return<br>
                    · Priya K (HR) — full week · no pending items<br>
                    <span style="opacity:0.7; font-size: 12px;">Sources: 3 leave records, 2 invoice approvals, 1 AARF</span>
                </div>
                <div class="ln-ai-bubble-user">Reassign Aisha's approvals to Hanna.</div>
                <div class="ln-ai-bubble-ai">
                    Delegated 2 invoice approvals to Hanna Tan (Finance Manager) for Apr 27 – Apr 29. An audit log entry was created. Want me to email Hanna a summary?
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mk-section">
    <div class="mk-container">
        <div class="ln-sec-head">
            <div class="eyebrow">Pricing at a glance</div>
            <div>
                <h2>Per active employee, <em>per month.</em></h2>
                <p style="margin-top: 24px;">Annual plans get two months free. Switch tier any time — upgrades take effect immediately, downgrades at the end of the cycle.</p>
            </div>
        </div>

        <div class="ln-prc">
            @foreach($pricing['tiers'] as $key => $tier)
                <div class="ln-prc-cell {{ $tier['featured'] ? 'featured' : '' }}">
                    <span class="ln-prc-cell-name">{{ $tier['name'] }}@if($tier['featured']) · Most popular @endif</span>
                    @if($tier['monthly_usd'] !== null)
                        <div class="ln-prc-cell-price">${{ $tier['monthly_usd'] }}<small>/emp/mo</small></div>
                    @else
                        <div class="ln-prc-cell-price">Custom</div>
                    @endif
                    <div class="ln-prc-cell-sub">{{ $tier['headcount_label'] }}</div>
                </div>
            @endforeach
        </div>

        <div style="display: flex; justify-content: center; margin-top: 40px;">
            <a href="{{ route('marketing.pricing') }}" class="eiaaw-btn eiaaw-btn--outline">Compare every tier →</a>
        </div>
    </div>
</section>

<section class="ln-cta">
    <div class="mk-container mk-container--narrow">
        <h2>Spend your first week <em>importing, not configuring.</em></h2>
        <p>Sign up with your work email, pick a workspace URL, set a password. Your tenant is provisioned with a 14-day Growth trial — no credit card.</p>
        <div class="ln-cta-row">
            <a href="{{ route('signup.form') }}" class="eiaaw-btn eiaaw-btn--primary">Start 14-day trial</a>
            <a href="{{ route('marketing.pricing') }}" class="eiaaw-btn eiaaw-btn--outline">See pricing</a>
        </div>
        <div class="ln-cta-note">No credit card · 14-day Growth trial · Postgres RLS isolation</div>
    </div>
</section>

@endsection
