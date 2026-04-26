@extends('layouts.marketing')

@section('title', 'EIAAW Workforce — HR, IT & Accounting on one AI-native platform | Malaysia & APAC')
@section('description', 'EIAAW Workforce runs your full employee journey, IT asset workflow, HRM (leave, payroll, attendance, EA forms, statutory submissions) and full-fledged accounting on one AI-native platform. Three departments, one tenant, zero CSV exports. 14-day trial, no credit card.')

@push('head')
{{-- ── SEO · GEO · AI-discoverability · Social cards ─────────────────────── --}}
@php
    $canonical = url('/');
    $ogImage = asset('images/landing/employee-journey.jpg');
    $pricingTiers = $pricing['tiers'] ?? [];
    $starterPrice = $pricingTiers['starter']['monthly_usd'] ?? 6;
    $scalePrice   = $pricingTiers['scale']['monthly_usd'] ?? 29;

    $jsonFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    $orgSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'EIAAW Solutions Sdn. Bhd.',
        'legalName' => 'EIAAW Solutions Sdn. Bhd.',
        'alternateName' => 'EIAAW Workforce',
        'url' => $canonical,
        'logo' => asset('brand/logo-full.png'),
        'description' => 'EIAAW Solutions builds AI-native business platforms for Malaysian and APAC mid-market teams — including EIAAW Workforce, the unified HR, IT and Accounting suite.',
        'foundingLocation' => [
            '@type' => 'Place',
            'address' => [
                '@type' => 'PostalAddress',
                'addressCountry' => 'MY',
                'addressRegion' => 'Kuala Lumpur',
            ],
        ],
        'contactPoint' => [
            [
                '@type' => 'ContactPoint',
                'contactType' => 'sales',
                'email' => config('eiaaw.sales_email', 'sales@eiaawsolutions.com'),
                'areaServed' => ['MY', 'SG', 'ID', 'TH', 'PH', 'VN'],
                'availableLanguage' => ['en', 'ms'],
            ],
            [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'email' => config('eiaaw.support_email', 'hello@eiaawsolutions.com'),
                'availableLanguage' => ['en', 'ms'],
            ],
        ],
        'sameAs' => ['https://eiaawsolutions.com'],
    ];

    $appSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'EIAAW Workforce',
        'applicationCategory' => 'BusinessApplication',
        'applicationSubCategory' => 'Human Resources, IT Asset Management, Accounting',
        'operatingSystem' => 'Web (any modern browser)',
        'url' => $canonical,
        'image' => $ogImage,
        'description' => 'EIAAW Workforce unifies the full employee journey (onboarding, records, offboarding), IT asset management with auto AARF acceptance/return, full HRM (leave, payroll, EA forms, attendance, government statutory submissions) and full-fledged accounting on one AI-native multi-tenant platform.',
        'featureList' => [
            'Automated employee onboarding with self-service invite link',
            'Active employee records with cryptographic re-acknowledgement on edits',
            'Automated offboarding with IT/Finance handoff',
            'IT asset inventory with auto-acceptance and return AARF flows',
            'Leave management with accruals and manager reminders',
            'Payroll with payslips, EA forms and statutory deductions (EPF/SOCSO/EIS/PCB)',
            'Attendance tracking',
            'Expense claims (eClaim) auto-posting to GL',
            'Full accounting: Chart of Accounts, GL, AR/AP, invoices, POs, budgeting, tax returns',
            'AI assistant grounded on tenant data with row-level citations',
            'Postgres Row-Level Security per tenant',
        ],
        'offers' => [
            [
                '@type' => 'Offer',
                'name' => 'Starter',
                'price' => $starterPrice,
                'priceCurrency' => 'USD',
                'priceSpecification' => [
                    '@type' => 'UnitPriceSpecification',
                    'price' => $starterPrice,
                    'priceCurrency' => 'USD',
                    'unitText' => 'per active employee per month',
                ],
                'availability' => 'https://schema.org/InStock',
            ],
            [
                '@type' => 'Offer',
                'name' => 'Scale (everything)',
                'price' => $scalePrice,
                'priceCurrency' => 'USD',
                'priceSpecification' => [
                    '@type' => 'UnitPriceSpecification',
                    'price' => $scalePrice,
                    'priceCurrency' => 'USD',
                    'unitText' => 'per active employee per month',
                ],
                'availability' => 'https://schema.org/InStock',
            ],
        ],
        'creator' => [
            '@type' => 'Organization',
            'name' => 'EIAAW Solutions Sdn. Bhd.',
        ],
        'inLanguage' => ['en', 'ms'],
        'audience' => [
            '@type' => 'BusinessAudience',
            'audienceType' => 'Mid-market businesses in Malaysia and APAC',
        ],
    ];

    $faqSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => [
            [
                '@type' => 'Question',
                'name' => 'What is EIAAW Workforce?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'EIAAW Workforce is an AI-native platform that runs three departments — HR, IT, and Accounting — on a single multi-tenant backbone. It carries the full employee journey from onboarding through active management to offboarding, automates IT asset acceptance and return via signed AARF links, and posts approved claims and payroll directly to a full accounting ledger.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'Which Malaysian statutory submissions are covered?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'EPF, SOCSO, EIS, and PCB are calculated on every payroll run. EA forms are auto-generated for each employee at year-end. Statutory file exports are formatted for direct submission to LHDN, KWSP, PERKESO, and HRDC.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'Is the AI assistant safe to use on real employee data?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Yes. Every AI answer is retrieval-grounded on your tenant only, cites the records it read, respects role-based access, and runs under a per-tenant cost circuit breaker so a runaway prompt never shocks your bill.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'How is tenant data isolated?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'EIAAW Workforce runs on Postgres with Row-Level Security enforcing tenant_id at the driver level. Cross-tenant data leaks are impossible even through a bugged controller.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'Can I start without a credit card?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Yes. Sign up with your work email, pick a workspace URL, set a password — your tenant is provisioned with a 14-day Growth trial. No credit card is required.',
                ],
            ],
        ],
    ];

    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'EIAAW Workforce',
                'item' => $canonical,
            ],
        ],
    ];
@endphp

<link rel="canonical" href="{{ $canonical }}">

{{-- Open Graph --}}
<meta property="og:type" content="website">
<meta property="og:site_name" content="EIAAW Workforce">
<meta property="og:title" content="EIAAW Workforce — HR, IT & Accounting on one AI-native platform">
<meta property="og:description" content="Three departments. One platform. The full employee journey — onboard, manage, offboard — wired live to IT asset workflows, full HRM (leave, payroll, EA, attendance, statutory) and full-fledged accounting. Built for Malaysia & APAC.">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="en_MY">
<meta property="og:locale:alternate" content="en_SG">
<meta property="og:locale:alternate" content="en_GB">

{{-- Twitter / X card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="EIAAW Workforce — HR, IT & Accounting on one AI-native platform">
<meta name="twitter:description" content="Run the full employee journey, IT asset workflow, HRM and accounting on one tenant. Built for Malaysia & APAC.">
<meta name="twitter:image" content="{{ $ogImage }}">

{{-- GEO targeting (Malaysia / APAC) --}}
<meta name="geo.region" content="MY-14">
<meta name="geo.placename" content="Kuala Lumpur">
<meta name="ICBM" content="3.1390,101.6869">
<meta name="DC.coverage" content="Malaysia, Singapore, Indonesia, Thailand, Philippines, Vietnam, APAC">

{{-- AI-search discovery hint (non-standard but read by some indexers) --}}
<link rel="alternate" type="text/plain" href="{{ url('/llms.txt') }}" title="LLM-readable summary">

{{-- JSON-LD: Organization --}}
<script type="application/ld+json" nonce="{{ $cspNonce ?? '' }}">{!! json_encode($orgSchema, $jsonFlags) !!}</script>

{{-- JSON-LD: SoftwareApplication (EIAAW Workforce) --}}
<script type="application/ld+json" nonce="{{ $cspNonce ?? '' }}">{!! json_encode($appSchema, $jsonFlags) !!}</script>

{{-- JSON-LD: FAQ (mirrors the visible FAQ section below) --}}
<script type="application/ld+json" nonce="{{ $cspNonce ?? '' }}">{!! json_encode($faqSchema, $jsonFlags) !!}</script>

{{-- JSON-LD: BreadcrumbList --}}
<script type="application/ld+json" nonce="{{ $cspNonce ?? '' }}">{!! json_encode($breadcrumbSchema, $jsonFlags) !!}</script>

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

    @media (prefers-reduced-motion: reduce) {
        .ln-figure--float-a,
        .ln-figure--float-b,
        .ln-figure--float-c { animation: none; }
    }

    /* Hero stack — two floating images, slightly overlapped */
    .ln-hero-stack {
        position: relative;
        aspect-ratio: 4/5;
        max-width: 460px;
        margin-left: auto;
    }
    .ln-hero-stack .ln-figure { position: absolute; }
    .ln-hero-stack .ln-figure--main {
        inset: 0 0 18% 18%;
        aspect-ratio: 3/4;
    }
    .ln-hero-stack .ln-figure--accent {
        right: 0; top: 8%;
        width: 56%; aspect-ratio: 4/5;
        z-index: 2;
    }
    @media (max-width: 960px) {
        .ln-hero-stack { max-width: 380px; margin: 12px auto 0; }
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

<section class="ln-hero">
    <div class="mk-container">
        <div class="ln-hero-grid">
            <div>
                <div class="ln-hero-meta">
                    <span class="mk-pill"><span class="mk-pill-dot"></span>AI · Human Partnerships</span>
                    <span style="font-family: var(--mono); font-size: 11px; color: var(--mute); text-transform: uppercase; letter-spacing: 0.14em;">Built for Malaysia &amp; APAC</span>
                </div>
                <h1 class="mk-display">
                    EIAAW Workforce — three departments, <em>one platform.</em>
                </h1>
                <p class="ln-hero-lede">
                    HR, IT, and Accounting on a single AI-native backbone. The full employee journey — onboard, manage, offboard — runs end-to-end alongside an automated IT asset workflow with auto acceptance and return AARFs, a complete HRM suite (leave, payroll, EA forms, attendance, government statutory submissions), and a full-fledged accounting platform. Three departments. One tenant. Zero CSV exports. More modules in active development.
                </p>
                <div class="ln-hero-ctas">
                    <a href="{{ route('signup.form') }}" class="eiaaw-btn eiaaw-btn--primary">Start 14-day trial · no credit card</a>
                    <a href="{{ route('marketing.features') }}" class="eiaaw-btn eiaaw-btn--outline">See every feature →</a>
                </div>
            </div>

            <aside class="ln-hero-meta-side" aria-label="Product at a glance">
                <div class="ln-hero-stack" aria-hidden="true">
                    <div class="ln-figure ln-figure--main ln-figure--float-a">
                        <img src="{{ asset('images/landing/hr-onboarding.jpg') }}" alt="" loading="eager" fetchpriority="high">
                    </div>
                    <div class="ln-figure ln-figure--accent ln-figure--float-b">
                        <img src="{{ asset('images/landing/it-assets.jpg') }}" alt="" loading="eager">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-top: 32px;">
                    <div>
                        <div class="ln-hero-stat" style="font-size: clamp(28px, 2.6vw, 36px);">HR</div>
                        <div class="ln-hero-stat-label">Onboarding · Records · Leave</div>
                    </div>
                    <div>
                        <div class="ln-hero-stat" style="font-size: clamp(28px, 2.6vw, 36px);">IT</div>
                        <div class="ln-hero-stat-label">Assets · Provisioning · AARF</div>
                    </div>
                    <div>
                        <div class="ln-hero-stat" style="font-size: clamp(28px, 2.6vw, 36px);">Finance</div>
                        <div class="ln-hero-stat-label">Payroll · Claims · Ledger</div>
                    </div>
                </div>
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
            <div class="eyebrow">The Automated Employee Journey</div>
            <div>
                <h2>Onboard, manage, offboard — <em>one continuous flow</em> across HR, IT, and Accounting.</h2>
                <p style="max-width: 620px; margin-top: 24px;">A new hire fills the invite form once. From that moment, every downstream system — HR records, IT assets, payroll, EA forms, statutory submissions, the general ledger — moves in step. No exports. No reconciliations. No SaaS sprawl.</p>
            </div>
        </div>

        <div class="ln-module">
            <div>
                <div class="ln-module-eyebrow">Pillar 01 · HR &amp; People Operations</div>
                <div class="ln-module-step">01.</div>
                <h3>A full HRM suite — onboard, manage, offboard, <em>and pay them right.</em></h3>
                <p>The employee journey runs end-to-end on one record. Section A–I of the invite link captures personal, work, education, dependent, and statutory data. AI auto-activates the account on start date, cascades into every relationship table, and runs the same chain in reverse on offboarding. Leave, attendance, payslips, EA forms, and government statutory submissions — EPF, SOCSO, EIS, PCB — all live on the same timeline.</p>
                <ul class="ln-module-bullets">
                    <li>Tokenised invite link with self-service NRIC + photo capture</li>
                    <li>Auto-activation on start date · auto-offboarding handoff to IT &amp; Finance</li>
                    <li>Leave management with accruals, manager reminders, weekly pending-sweep</li>
                    <li>Attendance · payslips · EA forms · eClaim under one record</li>
                    <li>EPF / SOCSO / EIS / PCB calculated every payroll · statutory file exports for LHDN, KWSP, PERKESO, HRDC</li>
                    <li>Edit history with cryptographic re-acknowledgement on sensitive fields</li>
                </ul>
            </div>
            <div class="ln-module-media">
                <div class="ln-figure ln-figure--float-a">
                    <img src="{{ asset('images/landing/employee-journey.jpg') }}" alt="HR onboarding a new hire on day one across the EIAAW Workforce platform" loading="lazy">
                </div>
            </div>
        </div>

        <div class="ln-module ln-module--reverse">
            <div>
                <div class="ln-module-eyebrow">Pillar 02 · IT Asset Management</div>
                <div class="ln-module-step">02.</div>
                <h3>Every laptop, phone, and licence — <em>tracked, signed, returned</em>, automatically.</h3>
                <p>The moment HR confirms a start date, IT sees the request. Assets are picked from live inventory, assigned to the employee, and acknowledged through a signed Asset Acceptance &amp; Return Form (AARF) sent by email — no spreadsheets, no Slack threads. On exit, the same chain runs in reverse: a return AARF is dispatched, the employee signs off, and the asset is unassigned and either restocked or booked into the disposed-asset register that feeds Finance depreciation.</p>
                <ul class="ln-module-bullets">
                    <li>Live asset inventory with full lifecycle history per unit</li>
                    <li>Auto acceptance AARF on assignment · auto return AARF on offboarding</li>
                    <li>Tokenised email acknowledgement with full audit trail</li>
                    <li>Provisioning workflows that wait for IT sign-off before payroll finalises</li>
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
                    <li>AI invoice scanning &amp; budget-drift anomaly alerts</li>
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
                    <img src="{{ asset('images/landing/hr-onboarding.jpg') }}" alt="EIAAW Workforce roadmap and active development" loading="lazy">
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
                <p>Postgres Row-Level Security enforces tenant_id on every query at the driver level. No accidental cross-tenant leak is possible, even through a bugged controller.</p>
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
                <div class="eyebrow">Fully AI · Fully Automated</div>
                <h2>The assistant that runs the in-between work — <em>so your team stops chasing approvals.</em></h2>
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

            <div style="position: relative; z-index: 1; display: flex; flex-direction: column; gap: 22px;">
            <div class="ln-figure ln-figure--float-c" style="aspect-ratio: 16/10;">
                <img src="{{ asset('images/landing/ai-automation.jpg') }}" alt="AI automating workforce workflows" loading="lazy">
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
            <article class="ln-three-cell">
                <h3>What is EIAAW Workforce?</h3>
                <p>An AI-native platform that runs three departments — HR, IT, and Accounting — on a single multi-tenant backbone. It carries the full employee journey from onboarding through active management to offboarding, automates IT asset acceptance and return via signed AARF links, and posts approved claims and payroll directly to a full accounting ledger.</p>
            </article>
            <article class="ln-three-cell">
                <h3>Which Malaysian statutory submissions are covered?</h3>
                <p>EPF, SOCSO, EIS, and PCB are calculated on every payroll run. EA forms are auto-generated for each employee at year-end. Statutory file exports are formatted for direct submission to LHDN, KWSP, PERKESO, and HRDC.</p>
            </article>
            <article class="ln-three-cell">
                <h3>Is the AI assistant safe to use on real employee data?</h3>
                <p>Every AI answer is retrieval-grounded on your tenant only, cites the records it read, respects role-based access, and runs under a per-tenant cost circuit breaker so a runaway prompt never shocks your bill.</p>
            </article>
            <article class="ln-three-cell">
                <h3>How is tenant data isolated?</h3>
                <p>EIAAW Workforce runs on Postgres with Row-Level Security enforcing <code style="font-family: var(--mono); font-size: 13px; background: var(--bg-warm); padding: 2px 6px; border-radius: 4px;">tenant_id</code> at the driver level. Cross-tenant data leaks are impossible even through a bugged controller.</p>
            </article>
            <article class="ln-three-cell">
                <h3>Can I start without a credit card?</h3>
                <p>Yes. Sign up with your work email, pick a workspace URL, set a password — your tenant is provisioned with a 14-day Growth trial. No credit card required.</p>
            </article>
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
        <h2>Run HR, IT, and Finance <em>on one platform</em> — starting this week.</h2>
        <p>Sign up with your work email, pick a workspace URL, set a password. Your tenant is provisioned with a 14-day Growth trial — no credit card. AI workflows are switched on by default.</p>
        <div class="ln-cta-row">
            <a href="{{ route('signup.form') }}" class="eiaaw-btn eiaaw-btn--primary">Start 14-day trial</a>
            <a href="{{ route('marketing.pricing') }}" class="eiaaw-btn eiaaw-btn--outline">See pricing</a>
        </div>
        <div class="ln-cta-note">No credit card · 14-day Growth trial · Postgres RLS isolation</div>
    </div>
</section>

@endsection
