<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in &middot; {{ config('eiaaw.product_name', 'EIAAW Workforce') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('brand/shield.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('brand/eiaaw-tokens.css') }}" rel="stylesheet">
    <style>
        body {
            background: var(--bg);
            font-family: var(--sans);
            color: var(--ink);
            min-height: 100vh; margin: 0;
        }
        .auth-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        }
        .auth-aside {
            background: var(--bg-warm);
            padding: clamp(28px, 4vw, 56px);
            display: flex; flex-direction: column;
            border-right: 1px solid var(--line-soft);
            position: relative;
        }
        .auth-aside-meta {
            position: absolute;
            bottom: clamp(28px, 4vw, 56px);
            left: clamp(28px, 4vw, 56px);
        }
        .auth-aside-body {
            flex: 1;
            display: flex; flex-direction: column; justify-content: center;
            gap: clamp(20px, 2.4vw, 32px);
            padding-bottom: clamp(60px, 8vw, 100px);
        }
        .auth-aside-lockup {
            margin-bottom: clamp(8px, 1vw, 16px);
        }
        .auth-aside-quote {
            font-family: var(--serif); font-style: italic; font-weight: 400;
            font-size: clamp(26px, 2.6vw, 38px); line-height: 1.15;
            color: var(--ink); letter-spacing: -0.018em;
            max-width: 22ch;
        }
        .auth-aside-quote em {
            font-family: var(--serif); color: var(--primary-dark);
        }
        .auth-aside-quote strong {
            font-family: var(--sans); font-weight: 600; font-style: normal;
            color: var(--ink); letter-spacing: -0.025em;
        }
        .auth-aside-hero {
            display: flex; align-items: center; justify-content: center;
            min-height: 0;
            perspective: 1200px;
        }
        .auth-aside-hero img {
            max-width: 100%; height: auto; max-height: 44vh;
            display: block;
            filter:
                drop-shadow(0 40px 50px rgba(15, 26, 29, 0.28))
                drop-shadow(0 18px 24px rgba(15, 26, 29, 0.18))
                drop-shadow(0 4px 8px rgba(31, 168, 150, 0.12));
            animation: auth-hero-float 5s ease-in-out infinite;
            transform-origin: 50% 70%;
            will-change: transform, filter;
        }
        .auth-aside-hero::after {
            content: '';
            position: absolute;
            bottom: 6%;
            left: 50%;
            width: 60%;
            height: 22px;
            background: radial-gradient(ellipse at center,
                rgba(15, 26, 29, 0.22) 0%,
                rgba(15, 26, 29, 0.08) 45%,
                transparent 70%);
            transform: translateX(-50%);
            filter: blur(6px);
            animation: auth-hero-shadow 5s ease-in-out infinite;
            pointer-events: none;
            z-index: -1;
        }
        .auth-aside-hero { position: relative; }
        @keyframes auth-hero-float {
            0%, 100% {
                transform: translate3d(0, 0, 0) rotate(0deg);
                filter:
                    drop-shadow(0 40px 50px rgba(15, 26, 29, 0.28))
                    drop-shadow(0 18px 24px rgba(15, 26, 29, 0.18))
                    drop-shadow(0 4px 8px rgba(31, 168, 150, 0.12));
            }
            50% {
                transform: translate3d(0, -22px, 0) rotate(-0.6deg);
                filter:
                    drop-shadow(0 60px 70px rgba(15, 26, 29, 0.34))
                    drop-shadow(0 26px 34px rgba(15, 26, 29, 0.22))
                    drop-shadow(0 6px 12px rgba(31, 168, 150, 0.16));
            }
        }
        @keyframes auth-hero-shadow {
            0%, 100% {
                transform: translateX(-50%) scaleX(1);
                opacity: 1;
            }
            50% {
                transform: translateX(-50%) scaleX(0.82);
                opacity: 0.65;
            }
        }
        @media (prefers-reduced-motion: reduce) {
            .auth-aside-hero img { animation: none; }
            .auth-aside-hero::after { animation: none; }
        }
        .auth-aside-meta {
            font-family: var(--mono); font-size: 11px; font-weight: 500;
            text-transform: uppercase; letter-spacing: 0.14em;
            color: var(--mute);
        }
        .auth-main {
            display: flex; align-items: center; justify-content: center;
            padding: clamp(28px, 4vw, 56px);
            background: var(--surface);
        }
        .auth-form {
            width: 100%; max-width: 380px;
        }
        .auth-form h1 {
            font-family: var(--sans); font-weight: 500;
            font-size: 36px; line-height: 1.05;
            letter-spacing: -0.025em; color: var(--ink);
            margin: 28px 0 8px;
        }
        .auth-form .lead {
            font-size: 15px; color: var(--ink-2); margin-bottom: 32px;
        }
        .form-label {
            font-size: 13px; font-weight: 500; color: var(--ink-2);
            margin-bottom: 6px; letter-spacing: -0.005em;
        }
        .form-control {
            border: 1px solid var(--line); border-radius: 10px;
            padding: 11px 14px; font-family: var(--sans); font-size: 14.5px;
            background: var(--surface); color: var(--ink);
            transition: border-color 0.18s var(--ease), box-shadow 0.18s var(--ease);
        }
        .form-control::placeholder { color: var(--mute); opacity: 0.7; }
        .form-control:focus {
            border-color: var(--primary); outline: none;
            box-shadow: 0 0 0 3px rgba(31,168,150,0.12);
        }
        .auth-submit {
            width: 100%; padding: 13px 22px;
            background: var(--ink); color: var(--bg);
            border: 1px solid var(--ink); border-radius: 999px;
            font-family: var(--sans); font-size: 14px; font-weight: 500;
            cursor: pointer; letter-spacing: -0.005em;
            transition: background 0.35s var(--ease), transform 0.35s var(--ease);
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        }
        .auth-submit:hover { background: var(--primary-dark); border-color: var(--primary-dark); transform: translateY(-1px); }
        .auth-link {
            color: var(--primary-dark); text-decoration: none; font-weight: 500;
        }
        .auth-link:hover { color: var(--primary); text-decoration: underline; }
        .auth-meta-row {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 24px; font-size: 13px;
        }
        .form-check-input { border-color: var(--line); }
        .form-check-input:checked { background-color: var(--primary-dark); border-color: var(--primary-dark); }
        .form-check-label { font-size: 13px; color: var(--ink-2); }
        .alert { border-radius: 10px; font-size: 13.5px; padding: 10px 14px; }
        .alert-success { background: var(--primary-tint); border: 1px solid var(--primary); color: var(--primary-dark); }
        .alert-danger  { background: #FBE9E4; border: 1px solid var(--danger); color: var(--danger); }
        .alert-warning { background: #FBF1DD; border: 1px solid var(--warn); color: var(--warn); }
        .alert-info    { background: var(--bg-warm); border: 1px solid var(--line); color: var(--ink-2); }
        .footer-mini {
            margin-top: 40px; font-family: var(--mono); font-size: 10px;
            text-transform: uppercase; letter-spacing: 0.12em; color: var(--mute);
        }
        .auth-helpers {
            margin-top: 24px; padding-top: 20px;
            border-top: 1px solid var(--line-soft);
            display: flex; flex-wrap: wrap; gap: 14px 22px;
            justify-content: space-between; align-items: center;
            font-size: 13px;
        }
        .auth-helpers a {
            color: var(--ink-2); text-decoration: none; font-weight: 500;
            display: inline-flex; align-items: center; gap: 6px;
            transition: color 0.18s var(--ease);
        }
        .auth-helpers a:hover { color: var(--primary-dark); }
        .auth-helpers .auth-helper-find { color: var(--primary-dark); }
        .auth-helpers .auth-helper-find:hover { color: var(--ink); }

        @media (max-width: 880px) {
            .auth-shell { grid-template-columns: 1fr; }
            .auth-aside { display: none; }
        }
    </style>
</head>
<body>
<div class="auth-shell">

    <aside class="auth-aside">
        <div class="auth-aside-body">
            <a href="{{ url('/') }}" class="eiaaw-lockup auth-aside-lockup">
                <img src="{{ asset('brand/shield.png') }}" alt="EIAAW Workforce">
                <span class="eiaaw-lockup-text">
                    <strong>EIAAW Workforce</strong>
                    <small>AI &middot; Human Partnerships</small>
                </span>
            </a>

            <div class="auth-aside-quote">
                Run an entire organisation <em>in one click</em> with <strong>EIAAW&nbsp;Workforce</strong>.
            </div>

            <div class="auth-aside-hero">
                <img src="{{ asset('brand/login-hero.svg') }}"
                     alt="EIAAW Workforce dashboard with Run organisation assistant"
                     loading="lazy">
            </div>
        </div>

        <div class="auth-aside-meta">
            EIAAW Solutions &middot; Made in Malaysia
        </div>
    </aside>

    <main class="auth-main">
        <div class="auth-form">
            <span class="eyebrow">Sign in</span>
            <h1>Welcome back.</h1>
            <p class="lead">Use your work email to access the workspace.</p>

            @if(session('success'))
                <div class="alert alert-success mb-3">{{ session('success') }}</div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning mb-3"><i class="bi bi-clock-history me-1"></i>{{ session('warning') }}</div>
            @endif
            @if(session('status'))
                <div class="alert alert-info mb-3">{{ session('status') }}</div>
            @endif
            @if($errors->has('email'))
                <div class="alert alert-danger mb-3">
                    <i class="bi bi-shield-exclamation me-1"></i>{{ $errors->first('email') }}
                </div>
            @endif

            <form action="{{ route('login') }}{{ isset($redirectIntent) ? '?redirect='.$redirectIntent : '' }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="work_email">Work email</label>
                    <input type="email" id="work_email" name="work_email"
                           class="form-control @error('work_email') is-invalid @enderror"
                           value="{{ old('work_email') }}"
                           placeholder="you@yourcompany.com"
                           autocomplete="username" required autofocus>
                    @error('work_email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Enter your password"
                           autocomplete="current-password" required>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="auth-meta-row">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Keep me signed in</label>
                    </div>
                    <a href="{{ route('password.request') }}" class="auth-link">Forgot password?</a>
                </div>

                <button type="submit" class="auth-submit">
                    Sign in
                    <span aria-hidden="true">→</span>
                </button>
            </form>

            @php
                $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;
                $oidcOn = $tenant && data_get($tenant->sso_config, 'oidc.enabled');
                $samlOn = $tenant && data_get($tenant->sso_config, 'saml.enabled');
            @endphp

            @if($oidcOn || $samlOn)
                <div style="margin-top: 28px; padding-top: 24px; border-top: 1px solid var(--line-soft, #E8DFCC); text-align: center;">
                    <div style="font-family: var(--mono, monospace); font-size: 11px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--mute, #6B7A7F); margin-bottom: 14px;">
                        Or sign in with your organisation
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        @if($oidcOn)
                            <a href="{{ route('sso.oidc.start') }}" style="display: inline-flex; align-items: center; justify-content: center; gap: 10px; padding: 11px 18px; border: 1px solid var(--line, #D9CFBC); border-radius: 999px; background: var(--surface, #FFFFFF); color: var(--ink, #0F1A1D); text-decoration: none; font-family: var(--sans, sans-serif); font-size: 14px; font-weight: 500;">
                                Continue with SSO (OIDC)
                            </a>
                        @endif
                        @if($samlOn)
                            <a href="{{ route('sso.saml.start') }}" style="display: inline-flex; align-items: center; justify-content: center; gap: 10px; padding: 11px 18px; border: 1px solid var(--line, #D9CFBC); border-radius: 999px; background: var(--surface, #FFFFFF); color: var(--ink, #0F1A1D); text-decoration: none; font-family: var(--sans, sans-serif); font-size: 14px; font-weight: 500;">
                                Continue with SSO (SAML)
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            @unless(app()->bound('current_tenant'))
                {{-- Apex-only helpers. On a tenant subdomain the user already
                     knows the workspace, so these links would be noise. --}}
                <div class="auth-helpers">
                    <a href="{{ route('marketing.find-workspace') }}" class="auth-helper-find">
                        <span aria-hidden="true">⌕</span>
                        Find your workspace
                    </a>
                    <a href="{{ url('/') }}">
                        <span aria-hidden="true">←</span>
                        Back to home
                    </a>
                </div>
            @endunless

            <p class="text-center mt-4 mb-0" style="font-size:13px;color:var(--ink-2);">
                New to {{ config('eiaaw.product_name', 'EIAAW Workforce') }}?
                <a href="{{ route('signup.form') }}" class="auth-link">Start a 14-day trial</a>
            </p>

            <p class="footer-mini text-center">
                &copy; {{ date('Y') }} EIAAW Solutions
            </p>
        </div>
    </main>

</div>
<script nonce="{{ $cspNonce ?? '' }}" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
