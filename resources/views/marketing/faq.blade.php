@extends('layouts.marketing')

@section('title', 'FAQ — EIAAW Workforce')
@section('description', 'Frequently asked questions about trials, billing, data, security, and onboarding on EIAAW Workforce.')

@push('head')
<style>
    .faq-hero {
        padding: clamp(60px, 8vw, 100px) 0 clamp(24px, 4vw, 40px);
        text-align: center;
    }
    .faq-hero .eyebrow { justify-content: center; }
    .faq-hero h1 { margin: 18px auto 16px; max-width: 860px; }
    .faq-hero p { color: var(--ink-2); font-size: 17px; max-width: 540px; margin: 0 auto; }

    .faq-group { margin-top: clamp(48px, 6vw, 80px); }
    .faq-group-head {
        display: grid; grid-template-columns: auto 1fr;
        gap: 20px; align-items: baseline;
        padding-bottom: 16px;
        border-bottom: 1px solid var(--line-soft);
        margin-bottom: 8px;
    }
    .faq-group-number {
        font-family: var(--serif); font-style: italic; font-weight: 400;
        font-size: clamp(32px, 3vw, 40px);
        color: var(--primary-dark); line-height: 1;
    }
    .faq-group-title {
        font-family: var(--sans); font-weight: 500;
        font-size: clamp(22px, 2.4vw, 28px); line-height: 1.1;
        letter-spacing: -0.02em; color: var(--ink); margin: 0;
    }

    details.faq-item {
        border-bottom: 1px solid var(--line-soft);
        padding: 0;
    }
    details.faq-item summary {
        list-style: none;
        cursor: pointer;
        padding: 22px 40px 22px 0;
        position: relative;
        font-family: var(--sans); font-weight: 500;
        font-size: 16px; line-height: 1.4;
        color: var(--ink); letter-spacing: -0.005em;
        transition: color 0.2s var(--ease);
    }
    details.faq-item summary::-webkit-details-marker { display: none; }
    details.faq-item summary:hover { color: var(--primary-dark); }
    details.faq-item summary::after {
        content: '+';
        position: absolute; right: 8px; top: 50%;
        transform: translateY(-50%);
        font-family: var(--mono); font-size: 22px;
        color: var(--mute);
        transition: transform 0.25s var(--ease), color 0.25s var(--ease);
    }
    details.faq-item[open] summary::after {
        content: '–'; color: var(--primary-dark);
    }
    details.faq-item .faq-body {
        padding: 0 48px 22px 0;
        color: var(--ink-2);
        font-size: 14.5px;
        line-height: 1.65;
    }
    details.faq-item .faq-body p { margin: 0 0 10px; }
    details.faq-item .faq-body p:last-child { margin-bottom: 0; }
    details.faq-item .faq-body a { color: var(--primary-dark); text-decoration: underline; }

    .faq-contact {
        margin-top: clamp(64px, 8vw, 96px);
        background: var(--bg-warm);
        border: 1px solid var(--line-soft);
        border-radius: 20px;
        padding: clamp(32px, 4vw, 56px);
        text-align: center;
    }
    .faq-contact h3 {
        font-family: var(--sans); font-weight: 500;
        font-size: clamp(22px, 2.6vw, 30px); line-height: 1.15;
        letter-spacing: -0.02em; margin: 0 0 12px;
    }
    .faq-contact h3 em { font-family: var(--serif); font-style: italic; font-weight: 400; color: var(--primary-dark); }
    .faq-contact p { color: var(--ink-2); font-size: 15px; margin: 0 0 24px; max-width: 440px; margin-left: auto; margin-right: auto; }
    .faq-contact-ctas { display: inline-flex; gap: 12px; flex-wrap: wrap; justify-content: center; }
</style>
@endpush

@section('content')

@php
    $faqGroups = [
        [
            'number' => '01',
            'title'  => 'Trial',
            'items'  => [
                ['Do I need a credit card for the trial?', 'No. The 14-day Growth-tier trial needs only a work email, your name, company name, and a workspace URL. We never ask for card details to start.'],
                ['What happens on day 15?', 'We email you on day 10, 13, and the morning of day 15 with a "pick a plan" link. If you do nothing, the workspace auto-downgrades to Starter on day 15 — your data stays, your team keeps working, and you can upgrade any time.'],
                ['Can I extend my trial?', 'Yes — reply to any trial-reminder email and tell us why. Trial extensions are case-by-case but we\'ve never said no to a genuine evaluation.'],
                ['Can I invite my team during the trial?', 'Yes. Up to 50 users during trial, full functionality. Invite-but-not-active employees don\'t count toward billed headcount.'],
            ],
        ],
        [
            'number' => '02',
            'title'  => 'Billing',
            'items'  => [
                ['How is "per active employee" calculated?', 'We bill for employees with an active record in your workspace on the first day of each billing cycle. Invited-but-not-started, terminated, and deactivated records don\'t count.'],
                ['What if I grow past my tier\'s headcount?', 'No auto-upgrade, no surprise charges. We email you on the 25th of the month, offering three paths: pay per extra seat at the tier rate, upgrade to the next tier, or switch to Enterprise.'],
                ['Do you charge for invited users who never activate?', 'No. Only activated employees count toward billing.'],
                ['Can I pay annually?', 'Yes. Annual billing gets you 2 months free (pay 10 months, get 12). Available on Starter, Growth, and Scale. Enterprise is always annual.'],
                ['What currencies do you support?', 'MYR (primary) and USD at launch. SGD, IDR, and PHP are on the roadmap for Q3 2026. Enterprise can be invoiced in any currency.'],
                ['What payment methods?', 'All major cards via Stripe (Starter, Growth, Scale). Enterprise supports bank transfer, cheque, and LOA/PO billing.'],
            ],
        ],
        [
            'number' => '03',
            'title'  => 'Data',
            'items'  => [
                ['Where is my data hosted?', 'Railway production region. Postgres with daily encrypted backups retained 30 days, weekly retained 12 months.'],
                ['Can I export everything?', 'Yes — full export of your tenant as CSV (for humans) or JSON Lines (for re-import) from Admin → Export. Audit log export is Scale+ tier.'],
                ['What happens if I cancel?', 'Your data goes read-only for 30 days so your team can finish any outstanding work and export. After 30 days it\'s deleted from primary storage; encrypted backups are purged within 90 days.'],
                ['Can I migrate existing data in?', 'Yes. Common HRMS / payroll exports can be imported via the onboarding wizard. Accounting migration (opening balances, chart of accounts, prior-period JE) is a paid setup engagement on Scale+.'],
                ['Do you use my data to train AI models?', 'No. Your data is never used to train or fine-tune models — not ours, not third parties\'. Anthropic\'s API also does not train on customer data by default.'],
            ],
        ],
        [
            'number' => '04',
            'title'  => 'Security',
            'items'  => [
                ['How is tenant data isolated?', 'Postgres Row-Level Security (FORCE mode) on every tenant-tagged table. The database rejects queries that don\'t match the session\'s tenant_id — controllers can\'t leak what the DB won\'t let through. See the <a href="'.route('marketing.security').'">Security page</a> for the full architecture.'],
                ['Do you have SOC 2?', 'SOC 2 Type I is in progress for Q3 2026. Type II follows 6 months after. SAML 2.0 / OIDC SSO lands alongside Type I.'],
                ['Is my data encrypted?', 'In transit: TLS 1.3 enforced. At rest: AES-256 on Postgres disk, encrypted file storage for the private disk.'],
                ['Do you have 2FA?', 'Yes. TOTP-based 2FA available for every user. Enterprise can enforce 2FA workspace-wide.'],
                ['What\'s in the audit log?', 'Every auth event, approval, AI query, export, and admin action — HMAC-chained so tampering is detectable. Scale tier gets audit export; Enterprise gets SIEM forwarding.'],
                ['How do I report a vulnerability?', 'Email <a href="mailto:security@eiaawsolutions.com">security@eiaawsolutions.com</a>. We respond within 2 business days and publish a responsible-disclosure policy.'],
            ],
        ],
        [
            'number' => '05',
            'title'  => 'Onboarding',
            'items'  => [
                ['How long does implementation take?', 'Starter: self-serve in a day. Growth (HRM + assets): self-serve in 1–3 days, longer if you have an existing payroll history to import. Scale (adds full accounting): usually 2–4 weeks with our implementation team for CoA migration, opening balances, and integration setup.'],
                ['Can I trial with real employee data?', 'Yes. You own the data you import, even during trial. If you don\'t convert, export + delete takes one click.'],
                ['Do you support single sign-on?', 'Yes on Enterprise — SAML 2.0 and OIDC. Starter / Growth / Scale use email + password with optional TOTP 2FA.'],
                ['What integrations do you have?', 'At launch: Stripe, Slack (notifications), Gmail/Outlook (email). Q3 2026: Xero, QuickBooks, ADP. Enterprise custom integrations via API.'],
                ['Do you have a mobile app?', 'Web app is fully responsive and works on phone / tablet. Native iOS / Android apps on the Q4 2026 roadmap.'],
            ],
        ],
    ];
@endphp

<section class="faq-hero">
    <div class="mk-container mk-container--narrow">
        <span class="eyebrow">FAQ</span>
        <h1 class="mk-display">Questions, <em>answered directly.</em></h1>
        <p>No PR-speak. If we couldn't explain it simply, we probably shouldn't ship it.</p>
    </div>
</section>

<div class="mk-container mk-container--narrow">
    @foreach($faqGroups as $group)
        <section class="faq-group">
            <div class="faq-group-head">
                <div class="faq-group-number">{{ $group['number'] }}</div>
                <h2 class="faq-group-title">{{ $group['title'] }}</h2>
            </div>

            @foreach($group['items'] as [$q, $a])
                <details class="faq-item">
                    <summary>{{ $q }}</summary>
                    <div class="faq-body">
                        <p>{!! $a !!}</p>
                    </div>
                </details>
            @endforeach
        </section>
    @endforeach

    <div class="faq-contact">
        <h3>Still have a question? <em>Ask us.</em></h3>
        <p>We answer sales, security, and implementation questions within one business day.</p>
        <div class="faq-contact-ctas">
            <a href="#" data-ep-action="talk" class="eiaaw-btn eiaaw-btn--primary">Talk to us</a>
            <a href="{{ route('marketing.pricing') }}" class="eiaaw-btn eiaaw-btn--outline">Or just start the trial →</a>
        </div>
    </div>
</div>

@endsection
