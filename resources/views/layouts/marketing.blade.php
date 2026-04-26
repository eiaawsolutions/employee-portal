<!DOCTYPE html>
<html lang="en-MY">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'EIAAW Workforce — HR, IT & Accounting on one AI-native platform')</title>
    <meta name="description" content="@yield('description', 'EIAAW Workforce — the AI-native HR, IT and full-fledged accounting platform built for Malaysian and APAC mid-market teams. The full employee journey, automated IT asset workflow, full HRM (leave, payroll, EA, attendance, statutory), and complete accounting on one tenant.')">

    {{-- Crawler directives (per-page can override via @section('robots')) --}}
    <meta name="robots" content="@yield('robots', 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1')">

    {{-- Brand · favicons · theme --}}
    <meta name="theme-color" content="#11766A">
    <meta name="application-name" content="EIAAW Workforce">
    <meta name="apple-mobile-web-app-title" content="EIAAW Workforce">
    <meta name="format-detection" content="telephone=no">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('brand/shield.png') }}">
    <link rel="icon" type="image/png" href="{{ asset('brand/shield.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('brand/shield.png') }}">

    {{-- Authorship / publisher --}}
    <meta name="author" content="EIAAW Solutions Sdn. Bhd.">
    <meta name="publisher" content="EIAAW Solutions Sdn. Bhd.">
    <meta name="copyright" content="© {{ now()->year }} EIAAW Solutions Sdn. Bhd.">

    {{-- AI / LLM-crawler discovery hint --}}
    <link rel="alternate" type="text/plain" href="{{ url('/llms.txt') }}" title="LLM-readable site summary">

    {{-- Sitemap link (search engines also discover via robots.txt) --}}
    <link rel="sitemap" type="application/xml" href="{{ url('/sitemap.xml') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <link href="{{ asset('brand/eiaaw-tokens.css') }}" rel="stylesheet">

    <style>
        /* ── Base reset for marketing surfaces ── */
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            background: var(--bg);
            font-family: var(--sans);
            color: var(--ink);
            font-size: 16px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        a { color: var(--primary-dark); text-decoration: none; }
        a:hover { color: var(--ink); }
        img { max-width: 100%; display: block; }

        /* ── Layout primitives ── */
        .mk-container { max-width: 1240px; margin: 0 auto; padding: 0 clamp(20px, 4vw, 48px); }
        .mk-container--narrow { max-width: 920px; }

        /* ── Nav ── */
        .mk-nav {
            position: sticky; top: 0; z-index: 50;
            background: rgba(250, 247, 242, 0.82);
            backdrop-filter: saturate(140%) blur(16px);
            -webkit-backdrop-filter: saturate(140%) blur(16px);
            border-bottom: 1px solid var(--line-soft);
        }
        .mk-nav-inner {
            display: flex; align-items: center; justify-content: space-between;
            padding: 18px 0;
        }
        .mk-nav-links {
            display: flex; align-items: center; gap: clamp(18px, 2.5vw, 34px);
        }
        .mk-nav-links a {
            color: var(--ink-2); font-size: 14px; font-weight: 500;
            letter-spacing: -0.005em;
            transition: color 0.2s var(--ease);
        }
        .mk-nav-links a:hover { color: var(--ink); }
        .mk-nav-links a.active { color: var(--primary-dark); }
        .mk-nav-cta { display: flex; align-items: center; gap: 12px; }
        .mk-nav-cta .sign-in {
            color: var(--ink-2); font-size: 14px; font-weight: 500;
            padding: 8px 14px;
        }
        .mk-nav-mobile-toggle {
            display: none; background: none; border: 0; cursor: pointer;
            width: 40px; height: 40px; color: var(--ink);
            align-items: center; justify-content: center;
        }
        @media (max-width: 860px) {
            .mk-nav-links, .mk-nav-cta .sign-in { display: none; }
            .mk-nav-mobile-toggle { display: inline-flex; }
        }

        /* ── Footer ── */
        .mk-footer {
            background: var(--bg-warm);
            border-top: 1px solid var(--line-soft);
            padding: clamp(56px, 8vw, 96px) 0 40px;
            margin-top: clamp(80px, 10vw, 140px);
        }
        .mk-footer-grid {
            display: grid;
            grid-template-columns: 1.3fr repeat(4, 1fr);
            gap: clamp(24px, 4vw, 60px);
            margin-bottom: 56px;
        }
        @media (max-width: 860px) {
            .mk-footer-grid { grid-template-columns: 1fr 1fr; }
            .mk-footer-brand-col { grid-column: span 2; margin-bottom: 12px; }
        }
        .mk-footer-brand-col p {
            color: var(--ink-2); font-size: 14px; line-height: 1.6;
            margin: 18px 0 0; max-width: 280px;
        }
        .mk-footer-col h4 {
            font-family: var(--mono); font-size: 11px;
            text-transform: uppercase; letter-spacing: 0.14em;
            color: var(--mute); margin: 6px 0 18px; font-weight: 500;
        }
        .mk-footer-col ul { list-style: none; padding: 0; margin: 0; }
        .mk-footer-col li { margin-bottom: 10px; }
        .mk-footer-col a {
            color: var(--ink-2); font-size: 14px;
            transition: color 0.18s var(--ease);
        }
        .mk-footer-col a:hover { color: var(--primary-dark); }
        .mk-footer-trust {
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
            gap: 20px;
            padding-top: 32px;
            border-top: 1px solid var(--line-soft);
            font-family: var(--mono); font-size: 11px; letter-spacing: 0.08em;
            color: var(--mute); text-transform: uppercase;
        }
        .mk-footer-trust-chips { display: flex; flex-wrap: wrap; gap: 22px; }
        .mk-footer-trust-chips span { display: inline-flex; align-items: center; gap: 8px; }
        .mk-footer-trust-chips span::before {
            content: ''; width: 6px; height: 6px; border-radius: 50%;
            background: var(--success);
        }

        /* ── Reusable section primitives ── */
        .mk-section { padding: clamp(60px, 8vw, 120px) 0; }
        .mk-section--tight { padding: clamp(40px, 5vw, 72px) 0; }

        /* ── Pill / chip ── */
        .mk-pill {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: var(--primary-tint);
            color: var(--primary-dark);
            font-family: var(--mono); font-size: 11px;
            text-transform: uppercase; letter-spacing: 0.12em;
            font-weight: 500;
        }
        .mk-pill-dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--primary);
        }

        /* ── Display heading (editorial style, Instrument Serif accent) ── */
        .mk-display {
            font-family: var(--sans);
            font-weight: 500;
            font-size: clamp(38px, 6vw, 76px);
            line-height: 1.02;
            letter-spacing: -0.03em;
            color: var(--ink);
            margin: 0;
        }
        .mk-display em {
            font-family: var(--serif);
            font-style: italic;
            font-weight: 400;
            color: var(--primary-dark);
        }
        .mk-lede {
            font-size: clamp(16px, 1.6vw, 19px);
            line-height: 1.55;
            color: var(--ink-2);
            max-width: 640px;
        }
    </style>

    @stack('head')
</head>
<body>

<header class="mk-nav">
    <div class="mk-container mk-nav-inner">
        <a href="{{ route('marketing.landing') }}" class="eiaaw-lockup" aria-label="EIAAW Workforce home">
            <img src="{{ asset('brand/shield.png') }}" alt="">
            <span class="eiaaw-lockup-text">
                <strong>EIAAW</strong>
                <small>Workforce</small>
            </span>
        </a>

        <nav class="mk-nav-links" aria-label="Primary">
            <a href="{{ route('marketing.features') }}" class="{{ request()->routeIs('marketing.features') ? 'active' : '' }}">Features</a>
            <a href="{{ route('marketing.pricing') }}" class="{{ request()->routeIs('marketing.pricing') ? 'active' : '' }}">Pricing</a>
            <a href="{{ route('marketing.security') }}" class="{{ request()->routeIs('marketing.security') ? 'active' : '' }}">Security</a>
            <a href="{{ route('marketing.faq') }}" class="{{ request()->routeIs('marketing.faq') ? 'active' : '' }}">FAQ</a>
        </nav>

        <div class="mk-nav-cta">
            <a href="{{ route('login') }}" class="sign-in">Sign in</a>
            <a href="{{ route('signup.form') }}" class="eiaaw-btn eiaaw-btn--primary">
                Start 14-day trial
            </a>
            <button class="mk-nav-mobile-toggle" aria-label="Open menu" type="button" id="mk-nav-toggle">
                <svg width="20" height="14" viewBox="0 0 20 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1h18M1 7h18M1 13h18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
    </div>

    <div class="mk-nav-mobile-drawer" id="mk-nav-drawer" hidden>
        <div class="mk-container" style="padding: 24px 20px 32px;">
            <nav style="display: flex; flex-direction: column; gap: 18px; margin-bottom: 24px;">
                <a href="{{ route('marketing.features') }}">Features</a>
                <a href="{{ route('marketing.pricing') }}">Pricing</a>
                <a href="{{ route('marketing.security') }}">Security</a>
                <a href="{{ route('marketing.faq') }}">FAQ</a>
                <a href="{{ route('login') }}">Sign in</a>
                <a href="{{ route('marketing.find-workspace') }}">Find your workspace</a>
            </nav>
            <a href="{{ route('signup.form') }}" class="eiaaw-btn eiaaw-btn--primary" style="width: 100%; justify-content: center;">Start 14-day trial</a>
        </div>
    </div>
</header>

<main>
    @yield('content')
</main>

<footer class="mk-footer">
    <div class="mk-container">
        <div class="mk-footer-grid">
            <div class="mk-footer-brand-col">
                <a href="{{ route('marketing.landing') }}" class="eiaaw-lockup">
                    <img src="{{ asset('brand/shield.png') }}" alt="">
                    <span class="eiaaw-lockup-text">
                        <strong>EIAAW</strong>
                        <small>Workforce</small>
                    </span>
                </a>
                <p>The AI-native HR, payroll, and accounting platform built for Malaysian and APAC mid-market teams.</p>
            </div>

            <div class="mk-footer-col">
                <h4>Product</h4>
                <ul>
                    <li><a href="{{ route('marketing.features') }}">Features</a></li>
                    <li><a href="{{ route('marketing.pricing') }}">Pricing</a></li>
                    <li><a href="{{ route('marketing.security') }}">Security</a></li>
                    <li><a href="{{ route('marketing.faq') }}">FAQ</a></li>
                </ul>
            </div>

            <div class="mk-footer-col">
                <h4>Account</h4>
                <ul>
                    <li><a href="{{ route('signup.form') }}">Start trial</a></li>
                    <li><a href="{{ route('marketing.find-workspace') }}">Find workspace</a></li>
                    <li><a href="mailto:{{ config('eiaaw.support_email') }}">Support</a></li>
                </ul>
            </div>

            <div class="mk-footer-col">
                <h4>Company</h4>
                <ul>
                    <li><a href="https://eiaawsolutions.com" rel="noopener">EIAAW Solutions</a></li>
                    <li><a href="mailto:{{ config('eiaaw.sales_email') }}">Contact sales</a></li>
                </ul>
            </div>

            <div class="mk-footer-col">
                <h4>Legal</h4>
                <ul>
                    <li><a href="{{ route('marketing.terms') }}">Terms of service</a></li>
                    <li><a href="{{ route('marketing.privacy') }}">Privacy policy</a></li>
                    <li><a href="{{ route('marketing.dpa') }}">Data processing (DPA)</a></li>
                </ul>
            </div>
        </div>

        <div class="mk-footer-trust">
            <div class="mk-footer-trust-chips">
                <span>Postgres RLS isolation</span>
                <span>PDPA-aligned</span>
                <span>Encrypted at rest</span>
                <span>Audit-logged</span>
            </div>
            <div>© {{ now()->year }} {{ config('eiaaw.company_legal') }}</div>
        </div>
    </div>
</footer>

<script nonce="{{ $cspNonce ?? '' }}">
    (function () {
        var toggle = document.getElementById('mk-nav-toggle');
        var drawer = document.getElementById('mk-nav-drawer');
        if (!toggle || !drawer) return;
        toggle.addEventListener('click', function () {
            var hidden = drawer.hasAttribute('hidden');
            if (hidden) drawer.removeAttribute('hidden');
            else drawer.setAttribute('hidden', '');
        });
    })();
</script>

@stack('scripts')

</body>
</html>
