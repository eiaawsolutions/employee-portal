@extends('layouts.marketing')

@section('title', 'Features — EIAAW Workforce')
@section('description', 'Core HR, Payroll & EA forms, Claims, IT Asset Inventory, Full Accounting, and the AI assistant — every module in depth.')

@push('head')
<style>
    .ft-hero {
        padding: clamp(60px, 8vw, 100px) 0 clamp(32px, 5vw, 48px);
        text-align: center;
    }
    .ft-hero .eyebrow { justify-content: center; }
    .ft-hero h1 { margin: 18px auto 20px; max-width: 900px; }
    .ft-hero p { max-width: 640px; margin: 0 auto; color: var(--ink-2); font-size: 17px; }

    .ft-toc {
        display: flex; flex-wrap: wrap; justify-content: center;
        gap: 8px; margin-top: 40px;
    }
    .ft-toc a {
        padding: 7px 14px;
        border: 1px solid var(--line);
        border-radius: 999px;
        background: var(--surface);
        font-family: var(--mono); font-size: 11.5px;
        text-transform: uppercase; letter-spacing: 0.12em;
        color: var(--ink-2);
        transition: all 0.2s var(--ease);
    }
    .ft-toc a:hover { background: var(--ink); color: var(--bg); border-color: var(--ink); }

    /* ── Module sections ── */
    .ft-mod {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: clamp(32px, 5vw, 80px);
        align-items: center;
        padding: clamp(60px, 8vw, 100px) 0;
        border-top: 1px solid var(--line-soft);
    }
    @media (max-width: 860px) { .ft-mod { grid-template-columns: 1fr; gap: 28px; } }
    .ft-mod:nth-child(even) .ft-mod-copy { order: 2; }
    @media (max-width: 860px) { .ft-mod:nth-child(even) .ft-mod-copy { order: 0; } }

    .ft-mod-copy .eyebrow { margin-bottom: 16px; }
    .ft-mod-copy h2 {
        font-family: var(--sans); font-weight: 500;
        font-size: clamp(30px, 3.4vw, 44px); line-height: 1.08;
        letter-spacing: -0.025em; margin: 0 0 20px; color: var(--ink);
    }
    .ft-mod-copy h2 em { font-family: var(--serif); font-style: italic; font-weight: 400; color: var(--primary-dark); }
    .ft-mod-copy > p { font-size: 16px; color: var(--ink-2); line-height: 1.6; margin: 0 0 24px; max-width: 520px; }
    .ft-mod-copy ul { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px; }
    .ft-mod-copy li {
        padding-left: 22px; position: relative;
        font-size: 14.5px; line-height: 1.55; color: var(--ink-2);
    }
    .ft-mod-copy li::before {
        content: ''; position: absolute; left: 0; top: 9px;
        width: 12px; height: 1px; background: var(--primary);
    }

    /* ── Mock panel (used as the visual anchor for each module) ── */
    .ft-mock {
        background: var(--surface);
        border: 1px solid var(--line-soft);
        border-radius: 20px;
        padding: 28px;
        box-shadow: 0 18px 50px -30px rgba(15,26,29,0.3);
        display: flex; flex-direction: column; gap: 14px;
    }
    .ft-mock-head {
        display: flex; justify-content: space-between; align-items: center;
        padding-bottom: 14px; border-bottom: 1px solid var(--line-soft);
        font-family: var(--mono); font-size: 11px;
        text-transform: uppercase; letter-spacing: 0.14em;
        color: var(--mute);
    }
    .ft-mock-head-dot {
        display: inline-flex; align-items: center; gap: 8px; color: var(--primary-dark);
    }
    .ft-mock-head-dot::before {
        content: ''; width: 8px; height: 8px; border-radius: 50%;
        background: var(--primary);
    }
    .ft-mock-row {
        display: grid;
        grid-template-columns: 1fr auto auto;
        gap: 12px; align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid var(--line-soft);
        font-size: 13.5px; color: var(--ink-2);
    }
    .ft-mock-row:last-child { border-bottom: 0; }
    .ft-mock-row strong { color: var(--ink); font-weight: 500; }
    .ft-mock-row .tag {
        font-family: var(--mono); font-size: 10.5px;
        text-transform: uppercase; letter-spacing: 0.12em;
        padding: 3px 8px; border-radius: 999px;
    }
    .tag--approved { background: rgba(47,140,110,0.12); color: var(--success); }
    .tag--pending  { background: rgba(198,138,46,0.14); color: var(--warn); }
    .tag--danger   { background: rgba(180,65,43,0.12); color: var(--danger); }
    .tag--primary  { background: var(--primary-tint); color: var(--primary-dark); }

    .ft-mock-stats {
        display: grid; grid-template-columns: repeat(3, 1fr);
        gap: 0; border-radius: 12px; overflow: hidden;
        border: 1px solid var(--line-soft);
    }
    .ft-mock-stats-cell {
        padding: 14px 16px; border-right: 1px solid var(--line-soft);
        background: var(--bg);
    }
    .ft-mock-stats-cell:last-child { border-right: 0; }
    .ft-mock-stats-cell .n {
        font-family: var(--serif); font-size: 24px; font-style: italic;
        font-weight: 400; color: var(--ink); line-height: 1;
    }
    .ft-mock-stats-cell .l {
        font-family: var(--mono); font-size: 10.5px;
        text-transform: uppercase; letter-spacing: 0.12em;
        color: var(--mute); margin-top: 6px;
    }

    /* ── AI module has inverted palette (dark card) ── */
    .ft-mod--ai .ft-mock {
        background: var(--ink); border-color: var(--ink);
        box-shadow: 0 20px 60px -30px rgba(15,26,29,0.5);
    }
    .ft-mod--ai .ft-mock-head {
        color: rgba(255,255,255,0.55); border-bottom-color: rgba(255,255,255,0.12);
    }
    .ft-mod--ai .ft-mock-head-dot { color: var(--primary); }
    .ft-mod--ai .ft-mock-row { color: rgba(255,255,255,0.75); border-bottom-color: rgba(255,255,255,0.08); }
    .ft-mod--ai .ft-mock-row strong { color: var(--bg); }
</style>
@endpush

@section('content')

<section class="ft-hero">
    <div class="mk-container mk-container--narrow">
        <span class="eyebrow">Features</span>
        <h1 class="mk-display">Four modules. <em>One tenant.</em> No duplicated data.</h1>
        <p>Every module reads from the same employee record, the same approver chain, the same audit log. Payroll knows the leave balance. Claims post to the ledger. IT assets travel with the offboarding.</p>
        <nav class="ft-toc" aria-label="Jump to module">
            <a href="#employee-journey">M1 · Employee Journey</a>
            <a href="#assets">M2 · Asset Management</a>
            <a href="#hrm">M3 · HRM</a>
            <a href="#finance">M4 · Finance</a>
            <a href="#ai">AI Assistant</a>
        </nav>
    </div>
</section>

<div class="mk-container">

    <!-- ─── M1 EMPLOYEE JOURNEY (Starter+) ─── -->
    <section class="ft-mod" id="employee-journey">
        <div class="ft-mod-copy">
            <span class="eyebrow">M1 · Employee Journey · Starter+</span>
            <h2>The employee record is <em>the spine</em> of everything.</h2>
            <p>Onboarding to offboarding in one timeline. Every piece of data — NRIC, contract, spouse, emergency contact, education, children — lives in one place with role-gated visibility.</p>
            <ul>
                <li>Structured onboarding invite with self-service sections F–I staging</li>
                <li>Document vault with magic-byte validation and EXIF sanitisation</li>
                <li>Contract &amp; handbook acknowledgement workflow</li>
                <li>Org chart &amp; reporting lines</li>
                <li>Offboarding workflow with clearance checklist</li>
                <li>Private vs public fields with granular role policies</li>
            </ul>
        </div>
        <div class="ft-mock">
            <div class="ft-mock-head">
                <span class="ft-mock-head-dot">Employee · Aisha Rahman</span>
                <span>Finance · KL</span>
            </div>
            <div class="ft-mock-row"><strong>Onboarding</strong> <span>Sections A–I complete</span> <span class="tag tag--approved">Done</span></div>
            <div class="ft-mock-row"><strong>Contract</strong> <span>Acknowledged 2026-01-14</span> <span class="tag tag--approved">Active</span></div>
            <div class="ft-mock-row"><strong>Probation</strong> <span>Ends 2026-04-14</span> <span class="tag tag--primary">Confirmed</span></div>
            <div class="ft-mock-row"><strong>Reporting line</strong> <span>Finance Manager (Hanna T.)</span> <span class="tag tag--primary">Set</span></div>
            <div class="ft-mock-row"><strong>Documents</strong> <span>NRIC · Offer letter · Handbook</span> <span class="tag tag--approved">Signed</span></div>
        </div>
    </section>

    <!-- ─── M2 ASSET MANAGEMENT (Growth+) ─── -->
    <section class="ft-mod" id="assets">
        <div class="ft-mod-copy">
            <span class="eyebrow">M2 · Asset Management · Growth+</span>
            <h2>Assets travel with the employee — <em>even through offboarding.</em></h2>
            <p>Every laptop, phone, licence, and access badge is tied to the employee record. When they offboard, the return checklist writes itself.</p>
            <ul>
                <li>Asset master with serial, purchase date, warranty, and depreciation</li>
                <li>AARF (Asset Acknowledgement) flow — tokenised email links to confirm receipt</li>
                <li>Automatic return checklist generated from assigned assets on offboarding</li>
                <li>Software licence seat tracking with expiry alerts</li>
                <li>Disposal records with chain-of-custody trail</li>
            </ul>
        </div>
        <div class="ft-mock">
            <div class="ft-mock-head">
                <span class="ft-mock-head-dot">Asset Inventory</span>
                <span>487 items</span>
            </div>
            <div class="ft-mock-stats">
                <div class="ft-mock-stats-cell"><div class="n">142</div><div class="l">laptops</div></div>
                <div class="ft-mock-stats-cell"><div class="n">98</div><div class="l">phones</div></div>
                <div class="ft-mock-stats-cell"><div class="n">247</div><div class="l">licences</div></div>
            </div>
            <div class="ft-mock-row"><strong>Aisha R.</strong> <span>MacBook Pro 14" · SN-8824</span> <span class="tag tag--approved">AARF signed</span></div>
            <div class="ft-mock-row"><strong>Daniel L.</strong> <span>ThinkPad X1 · SN-5519</span> <span class="tag tag--pending">Return due</span></div>
            <div class="ft-mock-row"><strong>Priya K.</strong> <span>Figma seat</span> <span class="tag tag--danger">Expires 7d</span></div>
        </div>
    </section>

    <!-- ─── M3 HRM (Growth+) ─── -->
    <section class="ft-mod" id="hrm">
        <div class="ft-mod-copy">
            <span class="eyebrow">M3 · HRM · Growth+</span>
            <h2>Leave, attendance, claims, payroll — <em>one source of truth.</em></h2>
            <p>Payroll pulls from attendance, leave, and approved claims automatically — no export/import cycle. EA forms export in the format LHDN expects.</p>
            <ul>
                <li>Leave workflow — apply, approve, balances, entitlements, public holidays</li>
                <li>Attendance &amp; timesheet with anomaly flags</li>
                <li>Expense claims (eClaim) — receipt upload, multi-step approval</li>
                <li>Advanced payroll — EPF, SOCSO, EIS, PCB calculated per Malaysian statutory tables</li>
                <li>Delta payslips — "why did this month change?" explained per line</li>
                <li>EA form generator (LHDN-compliant) with per-employee distribution</li>
            </ul>
        </div>
        <div class="ft-mock">
            <div class="ft-mock-head">
                <span class="ft-mock-head-dot">Payroll · April 2026</span>
                <span>Run #47</span>
            </div>
            <div class="ft-mock-stats">
                <div class="ft-mock-stats-cell"><div class="n">142</div><div class="l">employees</div></div>
                <div class="ft-mock-stats-cell"><div class="n">$221k</div><div class="l">gross</div></div>
                <div class="ft-mock-stats-cell"><div class="n">$36.7k</div><div class="l">statutory</div></div>
            </div>
            <div class="ft-mock-row"><strong>EPF</strong> <span>$24,320 · 142 employees</span> <span class="tag tag--approved">Ready</span></div>
            <div class="ft-mock-row"><strong>SOCSO + EIS</strong> <span>$4,680 · 142 employees</span> <span class="tag tag--approved">Ready</span></div>
            <div class="ft-mock-row"><strong>PCB (tax)</strong> <span>$7,624 · 118 taxable</span> <span class="tag tag--approved">Ready</span></div>
            <div class="ft-mock-row"><strong>Anomaly</strong> <span>1 employee · OT &gt; 60% of base</span> <span class="tag tag--pending">Review</span></div>
        </div>
    </section>

    <!-- ─── M4 FINANCE (Scale+) ─── -->
    <section class="ft-mod" id="finance">
        <div class="ft-mod-copy">
            <span class="eyebrow">M4 · Finance · Scale only</span>
            <h2>Ledger, AR, AP, budgets, tax returns — <em>on the same backbone.</em></h2>
            <p>Approved claims, fixed-asset depreciation, and payroll runs all flow into the same chart of accounts. No monthly export to a separate bookkeeping tool.</p>
            <ul>
                <li>Chart of Accounts with opening-balance migration from your old system</li>
                <li>Accounts Receivable / Payable with ageing and dunning</li>
                <li>Bank reconciliation with AI auto-match</li>
                <li>Budgets with per-line variance alerts</li>
                <li>Tax returns (SST / GST-ready) and fiscal period close</li>
                <li>AI invoice scanning — receipts and vendor bills auto-coded to GL</li>
                <li>Claim → ledger auto-posting from the HRM eClaim workflow</li>
            </ul>
        </div>
        <div class="ft-mock">
            <div class="ft-mock-head">
                <span class="ft-mock-head-dot">General Ledger · April</span>
                <span>Period open</span>
            </div>
            <div class="ft-mock-row"><strong>Revenue</strong> <span>$530k</span> <span class="tag tag--approved">Posted</span></div>
            <div class="ft-mock-row"><strong>Operating expenses</strong> <span>$402k</span> <span class="tag tag--primary">Open</span></div>
            <div class="ft-mock-row"><strong>Payroll posting</strong> <span>$221k</span> <span class="tag tag--approved">Posted</span></div>
            <div class="ft-mock-row"><strong>Claims (18)</strong> <span>$3,540</span> <span class="tag tag--primary">Auto-posted</span></div>
            <div class="ft-mock-row"><strong>Depreciation</strong> <span>$2,010</span> <span class="tag tag--pending">Scheduled</span></div>
        </div>
    </section>

    <!-- ─── AI ASSISTANT ─── -->
    <section class="ft-mod ft-mod--ai" id="ai">
        <div class="ft-mod-copy">
            <span class="eyebrow">AI · Human Partnerships · Every tier</span>
            <h2>An assistant that <em>reads your data</em> — and cites the rows.</h2>
            <p>Retrieval-grounded on your tenant. Never hallucinates a policy. Respects role-based access — the assistant can only show what the asking user is allowed to see.</p>
            <ul>
                <li>Anthropic Claude Haiku 4.5 routine + Sonnet 4.6 for complex queries</li>
                <li>Per-tenant monthly message budget with a cost circuit breaker</li>
                <li>Every answer cites the specific records the model retrieved</li>
                <li>Sensitive fields (NRIC, salary) redacted unless the asker has the role</li>
                <li>Switchable to OpenAI or a self-hosted Llama model (Enterprise)</li>
            </ul>
        </div>
        <div class="ft-mock">
            <div class="ft-mock-head">
                <span class="ft-mock-head-dot">Assistant · Haiku 4.5</span>
                <span>Budget · 412 / 1,000</span>
            </div>
            <div class="ft-mock-row"><strong>Ask</strong> <span>"Summarise April claims by department"</span> <span class="tag tag--primary">Answered</span></div>
            <div class="ft-mock-row"><strong>Ask</strong> <span>"Who's OOO next week?"</span> <span class="tag tag--primary">Answered</span></div>
            <div class="ft-mock-row"><strong>Ask</strong> <span>"Draft offboarding plan for Daniel Lim"</span> <span class="tag tag--approved">Drafted</span></div>
            <div class="ft-mock-row"><strong>Ask</strong> <span>"Show me Aisha's salary"</span> <span class="tag tag--danger">Role blocked</span></div>
        </div>
    </section>

</div>

<section class="mk-section" style="text-align: center;">
    <div class="mk-container mk-container--narrow">
        <span class="eyebrow" style="justify-content: center;">Ready to explore?</span>
        <h2 style="font-family: var(--sans); font-weight: 500; font-size: clamp(34px, 4vw, 52px); line-height: 1.05; letter-spacing: -0.025em; margin: 18px 0 24px;">
            Start with the 14-day trial. <em style="font-family: var(--serif); font-style: italic; color: var(--primary-dark);">See it on your own data.</em>
        </h2>
        <a href="{{ route('marketing.pricing') }}" class="eiaaw-btn eiaaw-btn--primary">Choose plan & start trial</a>
    </div>
</section>

@endsection
