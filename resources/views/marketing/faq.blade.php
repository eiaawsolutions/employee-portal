@extends('layouts.marketing')

@section('title', 'EIAAW Workforce FAQ — Signing up, Billing, Data, PDPA & Security')
@section('description', 'Straight answers about signing up for EIAAW Workforce, per-employee billing in ringgit, where data is hosted, PDPA, security, AI and getting started.')

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
    // Plain-text answers: the same strings feed the visible FAQ and the FAQPage schema.
    $faqGroups = [
        [
            'number' => '01',
            'title'  => 'Signing up',
            'items'  => [
                ['Is there a free trial?', 'No. You choose a plan and pay for the first month or year at Stripe checkout. Your workspace is created as soon as payment goes through and you set a password.'],
                ['What do I need to sign up?', 'Your work email, name, company name, a workspace URL, the number of employees, and a card for checkout.'],
                ['Can I ask questions before I buy?', 'Yes. Click “Talk to us” and we answer sales questions within one business day.'],
                ['Can I invite my team straight away?', 'Yes. Once your workspace is created you can invite your team. Invited employees who haven’t started don’t count toward billed headcount.'],
            ],
        ],
        [
            'number' => '02',
            'title'  => 'Billing',
            'items'  => [
                ['How is "per active employee" calculated?', 'You set the number of employees at checkout, minimum 5. Invited-but-not-started, terminated and deactivated records don’t count. If your active headcount changes, tell us and we adjust your subscription from the next billing period.'],
                ['Is there a minimum?', 'Starter, Growth and Scale have a minimum of 5 billable employees per workspace. Enterprise minimums are agreed in the order form.'],
                ['Can I pay annually?', 'Yes. Annual billing gets you 2 months free (pay 10 months, get 12) on Starter, Growth and Scale.'],
                ['What currency do you bill in?', 'Plans are priced and billed in Malaysian ringgit (MYR).'],
                ['How do I pay?', 'By card at Stripe checkout; your subscription then renews automatically on the same card. Enterprise can also pay by bank transfer against an invoice.'],
            ],
        ],
        [
            'number' => '03',
            'title'  => 'Data',
            'items'  => [
                ['Where is my data hosted?', 'On Railway in Singapore, behind Cloudflare. The full list of providers that handle personal data is in our Privacy Notice.'],
                ['Can I export my data?', 'Yes. Employee, asset, claims and onboarding records export as CSV from their modules, and EA forms are generated per employee. On request we provide a full export of your workspace.'],
                ['What happens if I cancel?', 'Your workspace goes read-only for 30 days so your team can finish outstanding work and export. It is then deleted from the primary database, and any remaining copies are removed within 90 days of cancellation.'],
                ['Can I move existing data in?', 'Yes. Employees and assets can be imported from CSV templates. Accounting opening balances can be entered when you set up the ledger.'],
                ['Do you use my data to train AI models?', 'No. Your data is never used to train or fine-tune models, ours or anyone else’s. Anthropic, whose models power the assistant, does not train on data sent through its commercial API.'],
            ],
        ],
        [
            'number' => '04',
            'title'  => 'Security',
            'items'  => [
                ['How is tenant data isolated?', 'Postgres Row-Level Security in FORCE mode on every tenant-tagged table. The database rejects queries that don’t match the session’s tenant ID, so a bug in application code can’t leak another workspace’s data.'],
                ['Do you have SOC 2?', 'No, we are not SOC 2 certified. The controls we run today are Postgres Row-Level Security, HTTPS everywhere, a tamper-evident audit log, TOTP two-factor authentication, and SAML 2.0 / OIDC single sign-on on Enterprise.'],
                ['Is my data encrypted?', 'All traffic uses HTTPS, and passwords are stored as one-way hashes.'],
                ['Do you have 2FA?', 'Yes. TOTP two-factor authentication is available to every user, and admin roles are required to set it up.'],
                ['What’s in the audit log?', 'Security-relevant actions such as sign-ins, approvals, AI queries and exports, chained with HMAC so tampering is detectable. Enterprise can export the log to its own SIEM.'],
                ['How do I report a vulnerability?', 'Email eiaawsolutions@gmail.com with “Security” in the subject. We acknowledge within 2 business days. Our security.txt file lists the same contact.'],
            ],
        ],
        [
            'number' => '05',
            'title'  => 'Getting started',
            'items'  => [
                ['How long does setup take?', 'Starter is self-serve in a day. Growth usually takes 1–3 days, longer if you are bringing in payroll history. Scale, which adds accounting, depends on how much of your ledger you are moving across.'],
                ['Who owns the data I enter?', 'You do. If you cancel, you can export it during the 30-day read-only period, or ask us to delete the workspace and we will.'],
                ['Do you support single sign-on?', 'Yes on Enterprise, with SAML 2.0 and OIDC. Starter, Growth and Scale use email and password with TOTP two-factor authentication.'],
                ['What integrations do you have?', 'Stripe for billing, and email for invitations, approvals and notifications. Talk to us about a specific system.'],
                ['Do you have a mobile app?', 'The web app works on phones and tablets. There is no separate native iOS or Android app.'],
            ],
        ],
    ];
    $faqSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => collect($faqGroups)->flatMap(fn ($g) => $g['items'])->map(fn ($qa) => [
            '@type' => 'Question',
            'name' => $qa[0],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
        ])->values()->all(),
    ];
@endphp
@push('head')
<script type="application/ld+json" nonce="{{ $cspNonce ?? '' }}">{!! json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

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
                        <p>{{ $a }}</p>
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
            <a href="{{ route('marketing.pricing') }}" class="eiaaw-btn eiaaw-btn--outline">Or choose a plan →</a>
        </div>
    </div>
</div>

@endsection
