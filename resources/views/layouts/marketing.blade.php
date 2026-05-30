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
    <meta name="author" content="EIAAW SOLUTIONS">
    <meta name="publisher" content="EIAAW SOLUTIONS">
    <meta name="copyright" content="© {{ now()->year }} EIAAW SOLUTIONS (SSM Reg. No. 202603133419 / CT0164540-H)">

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
        .mk-social-row { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 20px; }
        .mk-social-link {
            display: inline-flex; align-items: center; justify-content: center;
            width: 38px; height: 38px; border-radius: 9px;
            color: var(--ink-2);
            transition: color 0.18s var(--ease), background-color 0.18s var(--ease), transform 0.18s var(--ease);
        }
        .mk-social-link svg { display: block; }
        .mk-social-link:hover { color: var(--primary-dark); background-color: rgba(17,118,106,0.08); transform: translateY(-1px); }
        .mk-social-link:focus-visible { outline: 2px solid var(--primary-dark); outline-offset: 2px; }
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
            <a href="{{ route('marketing.pricing') }}" class="eiaaw-btn eiaaw-btn--primary">
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
            <a href="{{ route('marketing.pricing') }}" class="eiaaw-btn eiaaw-btn--primary" style="width: 100%; justify-content: center;">Start 14-day trial</a>
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
                <div class="mk-social-row">
                    <a class="mk-social-link" href="https://www.linkedin.com/in/eiaawsolutions" target="_blank" rel="noopener me" aria-label="EIAAW Solutions on LinkedIn" title="LinkedIn">
                        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false" fill="currentColor"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.34V9h3.42v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28zM5.34 7.43a2.07 2.07 0 1 1 0-4.14 2.07 2.07 0 0 1 0 4.14zM7.12 20.45H3.55V9h3.57v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.22.79 24 1.77 24h20.45c.98 0 1.78-.78 1.78-1.73V1.73C24 .77 23.2 0 22.22 0z"/></svg>
                    </a>
                    <a class="mk-social-link" href="https://www.youtube.com/@EIAAWSOLUTIONS" target="_blank" rel="noopener me" aria-label="EIAAW Solutions on YouTube" title="YouTube">
                        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false" fill="currentColor"><path d="M23.5 6.2a3.02 3.02 0 0 0-2.12-2.14C19.5 3.55 12 3.55 12 3.55s-7.5 0-9.38.51A3.02 3.02 0 0 0 .5 6.2C0 8.08 0 12 0 12s0 3.92.5 5.8a3.02 3.02 0 0 0 2.12 2.14c1.88.51 9.38.51 9.38.51s7.5 0 9.38-.51a3.02 3.02 0 0 0 2.12-2.14C24 15.92 24 12 24 12s0-3.92-.5-5.8zM9.6 15.6V8.4l6.27 3.6-6.27 3.6z"/></svg>
                    </a>
                    <a class="mk-social-link" href="https://www.instagram.com/eiaawsolutions" target="_blank" rel="noopener me" aria-label="EIAAW Solutions on Instagram" title="Instagram">
                        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false" fill="currentColor"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41a3.72 3.72 0 0 1-1.38-.9 3.72 3.72 0 0 1-.9-1.38c-.16-.42-.36-1.06-.41-2.23-.06-1.27-.07-1.65-.07-4.85s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.17 8.8 2.16 12 2.16zM12 0C8.74 0 8.33.01 7.05.07 5.78.13 4.9.33 4.14.63c-.79.3-1.46.72-2.12 1.38C1.36 2.67.94 3.34.63 4.14c-.3.76-.5 1.64-.56 2.91C.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.06 1.27.26 2.15.56 2.91.3.8.72 1.47 1.38 2.13.66.66 1.33 1.08 2.12 1.38.76.3 1.64.5 2.91.56C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c1.27-.06 2.15-.26 2.91-.56a5.88 5.88 0 0 0 2.13-1.38c.66-.66 1.08-1.33 1.38-2.13.3-.76.5-1.64.56-2.91.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.06-1.27-.26-2.15-.56-2.91a5.88 5.88 0 0 0-1.38-2.13A5.88 5.88 0 0 0 19.86.63c-.76-.3-1.64-.5-2.91-.56C15.67.01 15.26 0 12 0zm0 5.84a6.16 6.16 0 1 0 0 12.32 6.16 6.16 0 0 0 0-12.32zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm7.85-10.41a1.44 1.44 0 1 1-2.88 0 1.44 1.44 0 0 1 2.88 0z"/></svg>
                    </a>
                    <a class="mk-social-link" href="https://www.threads.com/@eiaawsolutions" target="_blank" rel="noopener me" aria-label="EIAAW Solutions on Threads" title="Threads">
                        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false" fill="currentColor"><path d="M12.19 0h-.38C5.46.04.04 5.46 0 11.81v.38C.04 18.54 5.46 23.96 11.81 24h.38c6.35-.04 11.77-5.46 11.81-11.81v-.38C23.96 5.46 18.54.04 12.19 0zm.06 18.36c-2.7 0-4.34-1.25-5.27-2.95-.66-1.2-1-2.7-1.06-4.48v-.01c.06-1.78.4-3.27 1.06-4.47.93-1.71 2.57-2.96 5.27-2.96 1.86 0 3.4.58 4.49 1.7.65.66 1.13 1.48 1.45 2.45l-1.74.6c-.24-.7-.57-1.27-.99-1.7-.7-.71-1.71-1.08-3.21-1.08-1.93 0-3.06.86-3.7 2.02-.42.77-.66 1.83-.72 3.17v.27c0 1.34.24 2.4.66 3.17.64 1.16 1.77 2.02 3.7 2.02 1.5 0 2.51-.37 3.21-1.08.3-.3.55-.68.74-1.13-.71.18-1.5.27-2.35.27-2.4 0-3.92-1.13-3.92-2.91 0-1.6 1.45-2.79 3.57-2.79 1.52 0 2.78.46 3.57 1.36.04-.18.06-.37.06-.57 0-1.3-.86-2.3-2.66-2.3-.1 0-.2 0-.29.01l-.18-1.71c.16-.01.31-.02.47-.02 2.85 0 4.36 1.74 4.36 4.02 0 2.81-2.13 5.42-6.99 5.42z"/></svg>
                    </a>
                    <a class="mk-social-link" href="https://www.tiktok.com/@eiaawsolutions" target="_blank" rel="noopener me" aria-label="EIAAW Solutions on TikTok" title="TikTok">
                        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false" fill="currentColor"><path d="M12.53.02C13.84 0 15.14.01 16.44 0c.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                    </a>
                    <a class="mk-social-link" href="https://www.facebook.com/profile.php?id=61590414930468" target="_blank" rel="noopener me" aria-label="EIAAW Solutions on Facebook" title="Facebook">
                        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false" fill="currentColor"><path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07c0 6.03 4.39 11.03 10.13 11.93v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07z"/></svg>
                    </a>
                </div>
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
                    <li><a href="{{ route('marketing.pricing') }}">Start trial</a></li>
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
            <div>© {{ now()->year }} {{ config('eiaaw.company_legal') }} · SSM Reg. No. {{ config('eiaaw.company_reg_no') }}</div>
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

{{-- Marketing chatbot + Talk-to-us modal (apex-only; included on every marketing page) --}}
@include('partials.marketing-chatbot')

{{-- Voice agent launcher — reuses the Sales-marketing-agent Retell web-call
     infrastructure with site_scope=workforce. No new infra, no new cost. --}}
@include('partials.marketing-voice')

@stack('scripts')

</body>
</html>
