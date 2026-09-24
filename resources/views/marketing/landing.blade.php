@extends('layouts.marketing')

@section('title', 'HR, Payroll & Accounting Software Malaysia | EIAAW Workforce')
@section('description', 'HR, payroll (EPF, SOCSO, EIS, PCB, EA forms), IT-asset and accounting software for Malaysian SMEs in one workspace. 14-day free trial.')

@push('head')
{{-- ── Structured data ────────────────────────────────────────────────────
     Canonical, og:* and twitter:* come from layouts.marketing. The entity
     graph points at the parent site's Organization and product @ids so
     search and answer engines see one EIAAW, not two. --}}
@php
    $marketingHost = config('eiaaw.marketing_host', 'ep.eiaawsolutions.com');
    $base = app()->environment('production') ? 'https://'.trim($marketingHost, '/') : rtrim(url('/'), '/');
    $canonical = $base.'/';
    $currency = $pricing['currency']['code'] ?? 'USD';
    $priced = collect($pricing['tiers'] ?? [])->pluck('monthly_usd')->filter(fn ($p) => $p !== null);
    $fromPrice = $priced->min();

    $jsonFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
    $orgRef = ['@id' => 'https://eiaawsolutions.com/#organization'];

    // One source for the visible FAQ below and its FAQPage schema.
    $faqs = [
        ['What is EIAAW Workforce?', 'EIAAW Workforce is HR, payroll, IT-asset and accounting software for Malaysian SMEs. It runs the employee journey from onboarding to offboarding, tracks company assets with signed acceptance and return forms (AARF), handles leave, attendance, claims and payroll, and keeps a full accounting ledger — all on one employee record in one workspace.'],
        ['Which Malaysian statutory items does payroll cover?', 'EPF, SOCSO, EIS and PCB are calculated on every payroll run, and EA forms are generated for each employee at year-end. You review and approve each pay run and make the statutory submissions yourself.'],
        ['Is the AI assistant safe to use on real employee data?', 'The assistant answers only from records the signed-in person is already allowed to see, shows which records it used, cannot change anything in your workspace, and runs under a monthly usage cap per workspace. Your data is never used to train AI models.'],
        ['How is tenant data isolated?', 'EIAAW Workforce runs on Postgres with Row-Level Security in FORCE mode, enforcing each workspace’s tenant ID at the database level. The database refuses cross-tenant reads even if application code has a bug.'],
        ['Can I start without a credit card?', 'Yes. Sign up with your work email, pick a workspace URL and set a password. Your workspace starts a 14-day free trial of the plan you chose, with no credit card.'],
    ];

    $graph = [
        [
            '@type' => 'Organization',
            '@id' => 'https://eiaawsolutions.com/#organization',
            'name' => 'EIAAW Solutions',
            'legalName' => 'EIAAW SOLUTIONS',
            'identifier' => '202603133419 (CT0164540-H)',
            'url' => 'https://eiaawsolutions.com',
            'logo' => $base.'/brand/logo-full.png',
            'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Kuala Lumpur', 'addressCountry' => 'MY'],
        ],
        [
            '@type' => 'SoftwareApplication',
            '@id' => 'https://eiaawsolutions.com/products.html#workforce',
            'name' => 'EIAAW Workforce',
            'url' => $canonical,
            'applicationCategory' => 'BusinessApplication',
            'applicationSubCategory' => 'HR, payroll, IT asset management and accounting',
            'operatingSystem' => 'Web browser',
            'image' => $base.'/images/landing/employee-journey.jpg',
            'description' => 'HR, payroll, IT-asset and accounting software for Malaysian SMEs: onboarding and offboarding, leave, attendance, claims, EPF/SOCSO/EIS/PCB payroll with EA forms, asset tracking with signed AARF forms, and a full accounting ledger on one employee record.',
            'featureList' => [
                'Employee onboarding by invite link, records and offboarding',
                'IT asset inventory with signed acceptance and return forms (AARF)',
                'Leave, attendance and expense claims with approvals',
                'Payroll with EPF, SOCSO, EIS and PCB, payslips and EA forms',
                'Accounting: chart of accounts, general ledger, AR/AP, bank reconciliation, fixed assets, budgets, SST returns',
                'AI assistant that answers from the records you can see and cites them',
                'Postgres Row-Level Security per workspace',
            ],
            'offers' => [
                '@type' => 'AggregateOffer',
                'priceCurrency' => $currency,
                'lowPrice' => $priced->min(),
                'highPrice' => $priced->max(),
                'offerCount' => count($pricing['tiers'] ?? []),
                'url' => $base.'/pricing',
            ],
            'publisher' => $orgRef,
            'inLanguage' => 'en',
            'areaServed' => ['@type' => 'Country', 'name' => 'Malaysia'],
        ],
        [
            '@type' => 'WebSite',
            '@id' => $canonical.'#website',
            'name' => 'EIAAW Workforce',
            'url' => $canonical,
            'inLanguage' => 'en-MY',
            'publisher' => $orgRef,
        ],
        [
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($f) => [
                '@type' => 'Question',
                'name' => $f[0],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
            ], $faqs),
        ],
    ];
    foreach ([
        ['HR & payroll', 'Human resources and payroll software', 'hrm', 'Onboarding, employee records, offboarding, leave with accruals, attendance, expense claims, and payroll with EPF, SOCSO, EIS and PCB, payslips and EA forms.'],
        ['IT asset management', 'IT asset management software', 'assets', 'Asset inventory with lifecycle history, signed acceptance (AARF) on assignment, return forms on offboarding, and a disposed-asset register that feeds depreciation.'],
        ['Accounting', 'Accounting software', 'finance', 'Chart of accounts, general ledger, AR/AP, invoices, purchase orders, bank reconciliation, fixed assets and depreciation, budgets, SST returns, AI invoice scanning and approved-claim posting.'],
    ] as [$svcName, $svcType, $svcAnchor, $svcDesc]) {
        $graph[] = [
            '@type' => 'Service',
            'name' => 'EIAAW Workforce — '.$svcName,
            'serviceType' => $svcType,
            'provider' => $orgRef,
            'areaServed' => ['@type' => 'Country', 'name' => 'Malaysia'],
            'description' => $svcDesc,
            'url' => $base.'/features#'.$svcAnchor,
        ];
    }
@endphp

<script type="application/ld+json" nonce="{{ $cspNonce ?? '' }}">{!! json_encode(['@context' => 'https://schema.org', '@graph' => $graph], $jsonFlags) !!}</script>

<style>
    /* ── Hero ── */
    .ln-hero {
        padding: clamp(64px, 8vw, 110px) 0 clamp(56px, 8vw, 96px);
        position: relative; overflow: hidden;
    }
    .ln-hero::before {
        content: ''; position: absolute;
        inset: auto -10% -30% -10%;
        height: 460px;
        background:
            radial-gradient(60% 60% at 20% 40%, rgba(31,168,150,0.10), transparent 70%),
            radial-gradient(50% 50% at 80% 60%, rgba(17,118,106,0.08), transparent 70%);
        pointer-events: none; z-index: 0;
    }
    .ln-hero-grid {
        display: grid;
        grid-template-columns: 1.05fr 1fr;
        gap: clamp(32px, 5vw, 72px);
        align-items: center;
        position: relative; z-index: 1;
    }
    @media (max-width: 1080px) {
        .ln-hero-grid { grid-template-columns: 1fr; gap: 48px; align-items: start; }
    }
    .ln-hero-meta { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; margin-bottom: 24px; }
    .ln-hero-meta .mk-pill {
        background: var(--surface); color: var(--primary-dark);
        border: 1px solid var(--line);
    }
    .ln-hero h1 {
        margin: 0 0 28px;
        font-size: clamp(38px, 5.4vw, 68px);
        line-height: 1.02;
        max-width: 14ch;
    }
    .ln-hero-lede {
        font-size: clamp(16px, 1.4vw, 18px);
        line-height: 1.6;
        color: var(--ink-2);
        max-width: 52ch;
        margin: 0 0 22px;
    }
    .ln-hero-lede strong {
        color: var(--ink); font-weight: 600;
    }
    .ln-hero-pillars {
        display: flex; flex-wrap: wrap; gap: 8px 18px;
        margin: 0 0 32px;
        font-family: var(--mono); font-size: 11px;
        text-transform: uppercase; letter-spacing: 0.14em;
        color: var(--mute);
    }
    .ln-hero-pillars span {
        display: inline-flex; align-items: center; gap: 8px;
    }
    .ln-hero-pillars span::before {
        content: ''; width: 6px; height: 6px; border-radius: 50%;
        background: var(--primary); opacity: 0.7;
    }
    .ln-hero-ctas { display: flex; gap: 14px; flex-wrap: wrap; align-items: center; margin-bottom: 22px; }
    .ln-hero-trust {
        font-family: var(--mono); font-size: 11px;
        text-transform: uppercase; letter-spacing: 0.14em;
        color: var(--mute);
    }

    /* ── Hero product mock — layered, floating, shadow-rich ── */
    @keyframes ln-hero-float-main {
        0%, 100% { transform: translate3d(0, 0, 0) rotate(-0.2deg); }
        50%      { transform: translate3d(0, -12px, 0) rotate(0.1deg); }
    }
    @keyframes ln-hero-float-card {
        0%, 100% { transform: translate3d(0, 0, 0) rotate(0.4deg); }
        50%      { transform: translate3d(0, -18px, 0) rotate(-0.2deg); }
    }
    @keyframes ln-hero-pulse {
        0%, 100% { opacity: 0.55; transform: scale(1); }
        50%      { opacity: 0.85; transform: scale(1.04); }
    }

    .ln-hero-mock {
        position: relative;
        width: 100%;
        max-width: 620px;
        aspect-ratio: 4/3.4;
        margin-left: auto;
        isolation: isolate;
    }
    @media (max-width: 1080px) {
        .ln-hero-mock { margin: 0 auto; max-width: 560px; }
    }

    /* Ambient glow behind the whole composition */
    .ln-hero-mock::before {
        content: '';
        position: absolute;
        inset: 6% -4% -6% -4%;
        background:
            radial-gradient(48% 60% at 60% 55%, rgba(34,184,165,0.22), transparent 72%),
            radial-gradient(40% 50% at 25% 30%, rgba(17,118,106,0.18), transparent 72%);
        filter: blur(28px);
        z-index: 0;
        animation: ln-hero-pulse 6s ease-in-out infinite;
        will-change: opacity, transform;
    }

    /* Primary product mock — multi-layer cinematic shadow */
    .ln-hero-mock-main {
        position: absolute;
        inset: 0 8% 12% 0;
        border-radius: 18px;
        overflow: hidden;
        background: var(--surface);
        box-shadow:
            0 1px 2px rgba(15, 26, 29, 0.04),
            0 4px 8px rgba(15, 26, 29, 0.05),
            0 16px 32px -8px rgba(15, 26, 29, 0.14),
            0 36px 64px -16px rgba(15, 26, 29, 0.22),
            0 80px 140px -28px rgba(15, 26, 29, 0.28);
        animation: ln-hero-float-main 8s ease-in-out infinite;
        will-change: transform;
        z-index: 1;
        transform: translateZ(0);
    }
    .ln-hero-mock-main::after {
        content: '';
        position: absolute; inset: 0;
        border-radius: 18px;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.5);
        pointer-events: none;
    }
    .ln-hero-mock-main img,
    .ln-hero-mock-main svg {
        width: 100%; height: 100%; display: block;
    }

    /* Floating "one-click" command-palette card — overlaid bottom-right */
    .ln-hero-mock-card {
        position: absolute;
        right: -2%;
        bottom: -2%;
        width: 46%;
        aspect-ratio: 320/360;
        border-radius: 20px;
        overflow: hidden;
        box-shadow:
            0 1px 3px rgba(15, 26, 29, 0.08),
            0 12px 28px -6px rgba(15, 26, 29, 0.28),
            0 36px 80px -16px rgba(15, 26, 29, 0.40),
            0 0 0 1px rgba(255, 255, 255, 0.05);
        animation: ln-hero-float-card 6.5s ease-in-out infinite;
        animation-delay: 0.8s;
        will-change: transform;
        z-index: 2;
        transform: translateZ(0);
    }
    .ln-hero-mock-card img,
    .ln-hero-mock-card svg { width: 100%; height: 100%; display: block; }

    @media (max-width: 600px) {
        .ln-hero-mock-card { width: 58%; right: -4%; bottom: -4%; }
    }
    @media (prefers-reduced-motion: reduce) {
        .ln-hero-mock::before,
        .ln-hero-mock-main,
        .ln-hero-mock-card { animation: none; }
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

    /* ── Floating-elegant imagery (hero + sections) ── */
    @keyframes ln-float-a {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50%      { transform: translateY(-14px) rotate(-0.4deg); }
    }
    @keyframes ln-float-b {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50%      { transform: translateY(-10px) rotate(0.3deg); }
    }
    @keyframes ln-float-c {
        0%, 100% { transform: translateY(0); }
        50%      { transform: translateY(-8px); }
    }

    .ln-figure {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        background: var(--bg-warm);
        box-shadow:
            0 2px 4px rgba(15,26,29,0.05),
            0 16px 40px -12px rgba(15,26,29,0.18),
            0 48px 96px -24px rgba(15,26,29,0.28),
            0 80px 140px -40px rgba(15,26,29,0.22);
        transition: transform 0.9s var(--ease), box-shadow 0.9s, filter 0.9s;
        will-change: transform;
    }
    .ln-figure::after {
        content: ''; position: absolute; inset: 0; border-radius: 20px;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.32);
        pointer-events: none;
    }
    .ln-figure img {
        width: 100%; height: 100%; display: block;
        object-fit: cover;
        filter: saturate(0.94) contrast(1.02);
        transition: filter 0.9s var(--ease), transform 0.9s var(--ease);
    }
    .ln-figure:hover img { filter: saturate(1.06) contrast(1.05); transform: scale(1.03); }
    .ln-figure:hover {
        box-shadow:
            0 4px 8px rgba(15,26,29,0.06),
            0 24px 56px -12px rgba(15,26,29,0.22),
            0 64px 120px -24px rgba(15,26,29,0.32),
            0 100px 180px -40px rgba(15,26,29,0.28);
    }

    .ln-figure--float-a { animation: ln-float-a 7.5s ease-in-out infinite; }
    .ln-figure--float-b { animation: ln-float-b 9s   ease-in-out infinite; animation-delay: 1.2s; }
    .ln-figure--float-c { animation: ln-float-c 6s   ease-in-out infinite; animation-delay: 0.6s; }

    /* Plain badge variant: same float + hover lift, no card chrome */
    .ln-badge-float {
        display: block; width: 100%; max-width: 320px; height: auto;
        margin-top: 28px;
        filter: drop-shadow(0 8px 20px rgba(15,26,29,0.08));
        transition: transform 0.9s var(--ease), filter 0.9s var(--ease);
        animation: ln-float-b 9s ease-in-out infinite;
        animation-delay: 1.2s;
        will-change: transform;
    }
    .ln-badge-float:hover {
        transform: scale(1.03);
        filter: drop-shadow(0 12px 28px rgba(15,26,29,0.14));
    }

    @media (prefers-reduced-motion: reduce) {
        .ln-figure--float-a,
        .ln-figure--float-b,
        .ln-figure--float-c,
        .ln-badge-float { animation: none; }
    }

    /* Module-1 storyline section */
    .ln-module {
        display: grid;
        grid-template-columns: 1fr 1.05fr;
        gap: clamp(32px, 6vw, 96px);
        align-items: center;
        margin-top: clamp(40px, 5vw, 64px);
    }
    .ln-module + .ln-module { margin-top: clamp(64px, 8vw, 120px); }
    .ln-module--reverse { grid-template-columns: 1.05fr 1fr; }
    .ln-module--reverse .ln-module-media { order: -1; }
    @media (max-width: 860px) {
        .ln-module,
        .ln-module--reverse { grid-template-columns: 1fr; gap: 32px; }
        .ln-module--reverse .ln-module-media { order: 0; }
    }
    .ln-module-eyebrow {
        font-family: var(--mono); font-size: 11px;
        text-transform: uppercase; letter-spacing: 0.16em;
        color: var(--primary-dark); margin-bottom: 14px;
        display: inline-flex; align-items: center; gap: 10px;
    }
    .ln-module-eyebrow::before {
        content: ''; width: 24px; height: 1px; background: var(--primary); opacity: 0.6;
    }
    .ln-module-step {
        font-family: var(--serif); font-style: italic; font-weight: 400;
        font-size: clamp(54px, 6vw, 84px); line-height: 0.9;
        color: var(--primary-dark); letter-spacing: -0.03em;
        margin: 0 0 8px;
    }
    .ln-module h3 {
        font-family: var(--sans); font-weight: 500;
        font-size: clamp(26px, 2.6vw, 36px); line-height: 1.12;
        letter-spacing: -0.025em; margin: 0 0 18px; color: var(--ink);
    }
    .ln-module h3 em { font-family: var(--serif); font-style: italic; font-weight: 400; color: var(--primary-dark); }
    .ln-module p {
        color: var(--ink-2); font-size: 16px; line-height: 1.65;
        max-width: 520px; margin: 0 0 22px;
    }
    .ln-module-bullets { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px; }
    .ln-module-bullets li {
        color: var(--ink-2); font-size: 14.5px; line-height: 1.5;
        padding-left: 22px; position: relative;
    }
    .ln-module-bullets li::before {
        content: ''; position: absolute; left: 0; top: 9px;
        width: 12px; height: 1px; background: var(--primary); opacity: 0.7;
    }

    .ln-module-media {
        position: relative;
        aspect-ratio: 5/4;
        max-width: 540px;
        margin: 0 auto;
        width: 100%;
    }
    .ln-module-media .ln-figure {
        position: absolute; inset: 0;
        width: 100%; height: 100%;
    }
    /* Soft halo behind module figure */
    .ln-module-media::before {
        content: ''; position: absolute;
        inset: -8% -6% -10% -6%;
        background:
            radial-gradient(60% 60% at 30% 40%, rgba(31,168,150,0.14), transparent 70%),
            radial-gradient(50% 50% at 80% 70%, rgba(17,118,106,0.10), transparent 70%);
        pointer-events: none; z-index: 0;
        filter: blur(10px);
    }

    /* AI mock — give the right column a floating image companion */
    .ln-ai-mock { position: relative; }

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

<section class="ln-hero" aria-labelledby="hero-heading">
    <div class="mk-container">
        <div class="ln-hero-grid">
            <div>
                <div class="ln-hero-meta">
                    <span class="mk-pill"><span class="mk-pill-dot"></span>AI · Human Partnerships</span>
                    <span style="font-family: var(--mono); font-size: 11px; color: var(--mute); text-transform: uppercase; letter-spacing: 0.14em;">Built for Malaysia &amp; APAC</span>
                </div>

                <h1 id="hero-heading" class="mk-display">
                    Run an entire organisation <br><em>in one click.</em>
                </h1>

                <p class="ln-hero-lede">
                    <strong>EIAAW Workforce is HR, payroll, IT-asset and accounting software for Malaysian SMEs</strong>, from {{ $currency }}&nbsp;{{ $fromPrice }} per active employee per month. Onboarding, leave, EPF/SOCSO/EIS/PCB payroll, EA forms, company assets and a full ledger share one employee record, so HR, IT and Finance stop re-keying the same data.
                </p>

                <div class="ln-hero-pillars" aria-label="Departments included">
                    <span>HR · onboarding · payroll · EA · statutory</span>
                    <span>IT · assets · auto AARF</span>
                    <span>Finance · GL · invoices · claims</span>
                </div>

                <div class="ln-hero-ctas">
                    <a href="{{ route('marketing.pricing') }}" class="eiaaw-btn eiaaw-btn--primary">Start 14-day trial · no credit card</a>
                    <a href="{{ route('marketing.features') }}" class="eiaaw-btn eiaaw-btn--outline">See every feature →</a>
                    <a href="#" data-ep-action="talk" class="eiaaw-btn eiaaw-btn--outline">Talk to us</a>
                </div>

                <div class="ln-hero-trust">
                    Postgres RLS isolation · PDPA-aligned · EPF / SOCSO / EIS / PCB ready
                </div>
            </div>

            <aside class="ln-hero-mock" aria-label="Product preview: EIAAW Workforce dashboard with one-click platform run">
                <figure class="ln-hero-mock-main">
                    <img src="{{ asset('images/landing/platform-hero-v2.svg') }}"
                         alt="Illustrative EIAAW Workforce dashboard with sample data: HR, IT and Finance modules in the sidebar, headcount, assets and revenue tiles, and an activity stream covering onboarding, claims, AARF acknowledgements and payroll."
                         width="720" height="540" loading="eager" fetchpriority="high">
                </figure>
                <figure class="ln-hero-mock-card" aria-hidden="true">
                    <img src="{{ asset('images/landing/one-click-card.svg') }}"
                         alt=""
                         width="320" height="360" loading="eager">
                </figure>
            </aside>
        </div>
    </div>
</section>

<section class="ln-proof">
    <div class="mk-container ln-proof-inner">
        <div class="ln-proof-label">Three departments · One platform · Zero duplicated data:</div>
        <div class="ln-proof-modules">
            <span>HR &amp; People Ops</span>
            <span>IT Asset Management</span>
            <span>Full Accounting</span>
            <span>Payroll · EA · Statutory</span>
            <span>AI Workflow Automation</span>
        </div>
    </div>
</section>

<section class="mk-section">
    <div class="mk-container">
        <div class="ln-sec-head">
            <div>
                <div class="eyebrow">The Automated Employee Journey</div>
                <img src="{{ asset('images/landing/journey-badge.svg') }}"
                     alt="Invite link drops into a unified employee record that fans out to HR, IT and Finance"
                     loading="lazy"
                     class="ln-badge-float">
            </div>
            <div>
                <h2>Onboard, manage, offboard — <em>one continuous flow</em> across HR, IT, and Accounting.</h2>
                <p style="max-width: 620px; margin-top: 24px;">A new hire fills the invite form once. From that moment HR records, IT assets, payroll, EA forms and the general ledger work from the same record. No exports between systems, no re-keying.</p>
            </div>
        </div>

        <div class="ln-module">
            <div>
                <div class="ln-module-eyebrow">Pillar 01 · HR &amp; People Operations</div>
                <div class="ln-module-step">01.</div>
                <h3>A full HRM suite — onboard, manage, offboard, <em>and pay them right.</em></h3>
                <p>The employee journey runs end-to-end on one record. Sections A–I of the invite link capture personal, work, education, dependant and statutory details. The account activates automatically on the start date, and offboarding runs the same chain in reverse. Leave, attendance, payslips, EA forms and EPF, SOCSO, EIS and PCB deductions all live on the same timeline.</p>
                <ul class="ln-module-bullets">
                    <li>Tokenised invite link with self-service NRIC + photo capture</li>
                    <li>Auto-activation on start date · auto-offboarding handoff to IT &amp; Finance</li>
                    <li>Leave management with accruals, manager reminders, weekly pending-sweep</li>
                    <li>Attendance · payslips · EA forms · eClaim under one record</li>
                    <li>EPF / SOCSO / EIS / PCB calculated on every payroll run · EA forms at year-end</li>
                    <li>Edit history, with the employee asked to re-acknowledge changes to their record</li>
                </ul>
            </div>
            <div class="ln-module-media">
                <div class="ln-figure ln-figure--float-a">
                    <img src="{{ asset('images/landing/employee-journey.svg') }}" alt="A new hire fills the invite form once — the record cascades into HR, IT and Finance with onboarding, asset acceptance, payroll and offboarding on one timeline" loading="lazy">
                </div>
            </div>
        </div>

        <div class="ln-module ln-module--reverse">
            <div>
                <div class="ln-module-eyebrow">Pillar 02 · IT Asset Management</div>
                <div class="ln-module-step">02.</div>
                <h3>Every laptop, phone, and licence — <em>tracked, signed, returned</em>, automatically.</h3>
                <p>The moment HR confirms a start date, IT sees the request. Assets are picked from live inventory, assigned to the employee, and acknowledged through a signed Asset Acceptance &amp; Return Form (AARF) sent by email — no spreadsheets, no chat threads. On exit, the same chain runs in reverse: a return AARF is dispatched, the employee signs off, and the asset is unassigned and either restocked or booked into the disposed-asset register that feeds Finance depreciation.</p>
                <ul class="ln-module-bullets">
                    <li>Live asset inventory with full lifecycle history per unit</li>
                    <li>Auto acceptance AARF on assignment · auto return AARF on offboarding</li>
                    <li>Tokenised email acknowledgement with full audit trail</li>
                    <li>Disposed-asset register flows straight to Finance depreciation</li>
                </ul>
            </div>
            <div class="ln-module-media">
                <div class="ln-figure ln-figure--float-b">
                    <img src="{{ asset('images/landing/it-assets.jpg') }}" alt="IT asset inventory and AARF acceptance workflow in EIAAW Workforce" loading="lazy">
                </div>
            </div>
        </div>

        <div class="ln-module">
            <div>
                <div class="ln-module-eyebrow">Pillar 03 · Full-Fledged Accounting</div>
                <div class="ln-module-step">03.</div>
                <h3>A complete accounting platform — <em>not a "lite" module bolted on.</em></h3>
                <p>Chart of Accounts, General Ledger, Accounts Receivable, Accounts Payable, invoices, purchase orders, banking &amp; reconciliation, fixed assets &amp; depreciation, budgeting, and tax returns — all wired to the same employee, approver chain, and audit log the HR and IT modules trust. Approved expense claims auto-post to the GL. Payroll runs against the live HR roster. Asset disposals book against the right cost centre. Your accountants stop reconciling and start reviewing.</p>
                <ul class="ln-module-bullets">
                    <li>Chart of Accounts · GL · AR/AP · Invoices · Purchase Orders</li>
                    <li>Banking, reconciliation, fixed assets &amp; depreciation, budgeting, tax returns</li>
                    <li>Approved eClaims auto-post to the ledger with full traceability</li>
                    <li>AI invoice scanning and bank reconciliation matching</li>
                    <li>Executive dashboard reads live from the books — no exports</li>
                </ul>
            </div>
            <div class="ln-module-media">
                <div class="ln-figure ln-figure--float-c">
                    <img src="{{ asset('images/landing/finance.jpg') }}" alt="Full accounting dashboards in EIAAW Workforce" loading="lazy">
                </div>
            </div>
        </div>

        <div class="ln-module ln-module--reverse">
            <div>
                <div class="ln-module-eyebrow">And more · In active development</div>
                <div class="ln-module-step">04.</div>
                <h3>The platform is alive — <em>new modules are landing every cycle.</em></h3>
                <p>EIAAW Workforce is built as a backbone, not a finished bundle. Every quarter brings new capability — deeper analytics, richer AI partners, additional government integrations, and lateral modules that compound on the same single-tenant data. When you adopt the platform, you're adopting a roadmap.</p>
                <ul class="ln-module-bullets">
                    <li>Knowledge base &amp; internal docs — live</li>
                    <li>Executive system overview &amp; reports — live</li>
                    <li>Deeper government integrations &amp; e-invoicing — rolling</li>
                    <li>Additional AI partners across HR, IT &amp; Finance workflows — rolling</li>
                </ul>
            </div>
            <div class="ln-module-media">
                <div class="ln-figure ln-figure--float-a">
                    <img src="{{ asset('images/landing/hr-onboarding.jpg') }}" alt="HR team reviewing a new starter's onboarding in EIAAW Workforce" loading="lazy">
                </div>
            </div>
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
                <p>Postgres Row-Level Security (FORCE mode) enforces tenant_id on every query at the database level. The database rejects cross-tenant reads even if a controller is buggy — isolation doesn't rely on app code being correct.</p>
            </article>
            <article class="ln-three-cell">
                <span class="mk-pill"><span class="mk-pill-dot"></span>02 · One backbone</span>
                <h3>Payroll reads from HR, claims post to the ledger, <em>no exports.</em></h3>
                <p>Every module shares the same employee, the same approver chain, the same audit log. When someone offboards, the whole org-chart, assets, and claims react at once.</p>
            </article>
            <article class="ln-three-cell">
                <span class="mk-pill"><span class="mk-pill-dot"></span>03 · AI with receipts</span>
                <h3>The assistant cites <em>which records</em> it read before answering.</h3>
                <p>The assistant answers from your workspace's records, lists the ones it used, and only sees what the person asking is allowed to see. It reads; it never changes your data.</p>
            </article>
        </div>
    </div>
</section>

<section class="mk-section mk-section--tight">
    <div class="mk-container">
        <div class="ln-ai">
            <div class="ln-ai-copy">
                <div class="eyebrow">Fully AI · Fully Automated</div>
                <h2>Ask about your people in plain language — <em>and see where the answer came from.</em></h2>
                <p>Included in every plan. The assistant answers from your workspace, lists the records it read, respects each person's role, and runs under a monthly usage cap per workspace so costs stay predictable.</p>
                <ul>
                    <li>Who is on leave, and when, across the people you manage</li>
                    <li>Where expense claims stand and what is waiting for approval</li>
                    <li>Who is in which team, and who reports to whom</li>
                    <li>Read-only: it answers questions and never changes records</li>
                </ul>
                <a href="{{ route('marketing.features') }}" class="eiaaw-btn" style="background: var(--primary); color: var(--bg); border-color: var(--primary);">
                    See the assistant in detail →
                </a>
            </div>

            <div style="position: relative; z-index: 1; display: flex; flex-direction: column; gap: 22px;">
            <div class="ln-figure ln-figure--float-c" style="aspect-ratio: 16/10;">
                <img src="{{ asset('images/landing/ai-automation.jpg') }}" alt="Team using the EIAAW Workforce assistant" loading="lazy">
            </div>

            <div class="ln-ai-mock" aria-label="Example AI conversation">
                <div class="ln-ai-mock-head">
                    <span class="ln-ai-mock-head-dot"></span>
                    <span>Workforce Assistant · illustrative example</span>
                </div>
                <div class="ln-ai-bubble-user">Who's OOO next week and what's blocked on them?</div>
                <div class="ln-ai-bubble-ai">
                    <strong>3 people on leave next week:</strong><br>
                    · Aisha Rahman (Finance) — Mon–Wed · approval queue: 2 invoices<br>
                    · Daniel Lim (IT) — Thu–Fri · AARF pending: 1 laptop return<br>
                    · Priya K (HR) — full week · no pending items<br>
                    <span style="opacity:0.7; font-size: 12px;">Sources: 3 leave records, 2 invoice approvals, 1 AARF</span>
                </div>
                <div class="ln-ai-bubble-user">Which of my team's claims are still pending?</div>
                <div class="ln-ai-bubble-ai">
                    2 claims are waiting for your approval: Daniel Lim, RM 184.50 (travel) and Priya K, RM 62.00 (meals). Aisha Rahman's RM 310.00 claim was approved on Monday.<br>
                    <span style="opacity:0.7; font-size: 12px;">Sources: 3 expense claims</span>
                </div>
            </div>
            </div>
        </div>
    </div>
</section>

<section class="mk-section">
    <div class="mk-container">
        <div class="ln-sec-head">
            <div class="eyebrow">Frequently asked</div>
            <div>
                <h2>The five questions <em>every operator asks first.</em></h2>
                <p style="max-width: 620px; margin-top: 24px;">Direct answers, no marketing fluff. Full FAQ on the <a href="{{ route('marketing.faq') }}" style="border-bottom: 1px solid currentColor;">FAQ page</a>.</p>
            </div>
        </div>

        <div class="ln-three" style="grid-template-columns: 1fr;">
            @foreach($faqs as [$q, $a])
                <article class="ln-three-cell">
                    <h3>{{ $q }}</h3>
                    <p>{{ $a }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="mk-section">
    <div class="mk-container">
        <div class="ln-sec-head">
            <div class="eyebrow">Pricing at a glance</div>
            <div>
                <h2>Per active employee, <em>per month.</em></h2>
                <p style="margin-top: 24px;">Annual plans get two months free. Change plan whenever your needs change.</p>
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
        <h2>Run HR, IT, and Finance <em>on one platform</em> — starting this week.</h2>
        <p>Sign up with your work email, pick a workspace URL and set a password. Your workspace starts a 14-day free trial of the plan you choose, with no credit card.</p>
        <div class="ln-cta-row">
            <a href="{{ route('marketing.pricing') }}" class="eiaaw-btn eiaaw-btn--primary">Choose plan & start trial</a>
            <a href="{{ route('marketing.features') }}" class="eiaaw-btn eiaaw-btn--outline">See features</a>
        </div>
        <div class="ln-cta-note">No credit card · 14-day free trial · Postgres RLS isolation</div>
    </div>
</section>

@endsection
