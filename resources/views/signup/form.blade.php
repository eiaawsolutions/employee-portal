<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Start your EIAAW Workforce trial · {{ config('eiaaw.product_name', 'EIAAW Workforce') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('brand/shield.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="{{ asset('brand/eiaaw-tokens.css') }}" rel="stylesheet">
    <style>
        body { background: var(--bg); font-family: var(--sans); color: var(--ink); min-height: 100vh; margin: 0; }
        .auth-shell { min-height: 100vh; display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
        .auth-aside {
            background: var(--bg-warm);
            padding: clamp(28px, 4vw, 56px);
            display: flex; align-items: center; justify-content: center;
            border-right: 1px solid var(--line-soft);
            position: relative;
        }
        .aside-inner { width: 100%; max-width: 460px; }
        .aside-lockup { display: inline-flex; margin-bottom: 28px; }
        .auth-main { display: flex; align-items: center; justify-content: center; padding: clamp(28px, 4vw, 56px); background: var(--surface); position: relative; }
        .auth-form { width: 100%; max-width: 460px; }
        .auth-form .eyebrow { display: block; margin-bottom: 4px; }
        .auth-form h1 {
            font-family: var(--sans); font-weight: 500;
            font-size: clamp(32px, 3.5vw, 44px); line-height: 1.05;
            letter-spacing: -0.025em; color: var(--ink); margin: 24px 0 12px;
        }
        .auth-form h1 em { font-family: var(--serif); font-style: italic; font-weight: 400; color: var(--primary-dark); }
        .lead { font-size: 15px; color: var(--ink-2); margin-bottom: 28px; line-height: 1.5; }
        .field { margin-bottom: 18px; }
        .field label { display: block; font-size: 13px; font-weight: 500; color: var(--ink-2); margin-bottom: 6px; letter-spacing: -0.005em; }
        .field .hint { font-size: 12px; color: var(--mute); margin-top: 4px; font-family: var(--mono); letter-spacing: 0.04em; }
        .field .error { font-size: 12.5px; color: var(--danger); margin-top: 4px; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%; box-sizing: border-box;
            border: 1px solid var(--line); border-radius: 10px;
            padding: 11px 14px; font-family: var(--sans); font-size: 14.5px;
            background: var(--surface); color: var(--ink);
            transition: border-color 0.18s var(--ease), box-shadow 0.18s var(--ease);
        }
        input::placeholder { color: var(--mute); opacity: 0.7; }
        input:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(31,168,150,0.12); }
        .slug-input { display: flex; align-items: center; border: 1px solid var(--line); border-radius: 10px; background: var(--surface); overflow: hidden; }
        .slug-input:focus-within { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(31,168,150,0.12); }
        .slug-input input { border: none; flex: 1; padding-right: 4px; }
        .slug-input input:focus { box-shadow: none; }
        .slug-input .suffix { font-family: var(--mono); font-size: 12px; color: var(--mute); padding: 0 14px 0 4px; white-space: nowrap; }
        .submit {
            width: 100%; padding: 14px 22px;
            background: var(--ink); color: var(--bg);
            border: 1px solid var(--ink); border-radius: 999px;
            font-family: var(--sans); font-size: 14px; font-weight: 500;
            cursor: pointer; letter-spacing: -0.005em; margin-top: 8px;
            transition: background 0.35s var(--ease), transform 0.35s var(--ease);
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        }
        .submit:hover { background: var(--primary-dark); border-color: var(--primary-dark); transform: translateY(-1px); }
        .legal { font-size: 11.5px; color: var(--mute); margin-top: 22px; line-height: 1.55; }
        .legal a { color: var(--primary-dark); }
        .alert-danger { background: #FBE9E4; border: 1px solid var(--danger); color: var(--danger); border-radius: 10px; font-size: 13.5px; padding: 10px 14px; margin-bottom: 16px; }
        .plan-summary {
            background: var(--bg-warm);
            border: 1px solid var(--line-soft);
            border-radius: 12px;
            padding: 14px 16px;
            margin: 0 0 22px;
        }
        .plan-summary-line { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
        .plan-summary-label { font-family: var(--mono); font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.14em; color: var(--mute); }
        .plan-summary-change { font-size: 12px; color: var(--primary-dark); text-decoration: none; }
        .plan-summary-change:hover { text-decoration: underline; }
        .plan-summary-row { display: flex; flex-wrap: wrap; align-items: baseline; gap: 8px; }
        .plan-summary-row strong { font-size: 17px; font-weight: 600; color: var(--ink); }
        .plan-summary-price { font-size: 12.5px; color: var(--ink-2); }
        .plan-summary-trial { font-size: 12px; color: var(--primary-dark); margin-top: 4px; font-family: var(--mono); letter-spacing: 0.02em; }
        .aside-hero { font-family: var(--sans); font-weight: 500; font-size: clamp(28px, 3vw, 40px); line-height: 1.1; letter-spacing: -0.02em; max-width: 22ch; }
        .aside-hero em { font-family: var(--serif); font-style: italic; font-weight: 400; color: var(--primary-dark); }
        .aside-bullets { list-style: none; padding: 0; margin: 32px 0 0; font-size: 14px; color: var(--ink-2); }
        .aside-bullets li { padding: 10px 0; border-bottom: 1px solid var(--line-soft); display: flex; align-items: flex-start; gap: 10px; }
        .aside-bullets li:last-child { border-bottom: none; }
        .aside-bullets li::before { content: '✓'; color: var(--primary-dark); font-weight: 600; flex-shrink: 0; }
        .aside-meta {
            font-family: var(--mono); font-size: 11px; font-weight: 500;
            text-transform: uppercase; letter-spacing: 0.14em; color: var(--mute);
            position: absolute; left: clamp(28px, 4vw, 56px); bottom: clamp(28px, 4vw, 40px);
            margin: 0;
        }
        @media (max-width: 880px) { .auth-shell { grid-template-columns: 1fr; } .auth-aside { display: none; } }
    </style>
</head>
<body>
<div class="auth-shell">

    <aside class="auth-aside">
        <div class="aside-inner">
            <a href="/" class="eiaaw-lockup aside-lockup">
                <img src="{{ asset('brand/shield.png') }}" alt="EIAAW Workforce">
                <span class="eiaaw-lockup-text">
                    <strong>EIAAW Workforce</strong>
                    <small>AI &middot; Human Partnerships</small>
                </span>
            </a>

            <p class="aside-hero">
                Start your <em>14-day trial</em> in two minutes — no card, full feature access.
            </p>

            <ul class="aside-bullets">
                <li>Full HR, payroll, claims, leave, attendance, IT assets</li>
                <li>AI assistant trained on your workspace</li>
                <li>Up to 5 users free during trial</li>
                <li>Cancel anytime; auto-converts to Starter on day&nbsp;15</li>
            </ul>
        </div>

        <p class="aside-meta">EIAAW Solutions &middot; Made in Malaysia</p>
    </aside>

    <main class="auth-main">
        <form action="{{ route('signup.start') }}" method="POST" class="auth-form">
            @csrf
            <input type="hidden" name="plan" value="{{ $plan }}">

            <span class="eyebrow">New workspace</span>
            <h1>Tell us where to send the <em>confirmation link</em>.</h1>
            <p class="lead">We'll email a link to confirm your address and set your password. The 14-day trial begins after you confirm.</p>

            @php $tier = config('eiaaw.pricing.tiers.' . $plan, []); @endphp
            <div class="plan-summary">
                <div class="plan-summary-line">
                    <span class="plan-summary-label">Selected plan</span>
                    <a href="{{ route('marketing.pricing') }}" class="plan-summary-change">Change</a>
                </div>
                <div class="plan-summary-row">
                    <strong>{{ $tier['name'] ?? ucfirst($plan) }}</strong>
                    @if(!empty($tier['monthly_usd']))
                        <span class="plan-summary-price">US${{ $tier['monthly_usd'] }}/active employee/mo after trial</span>
                    @endif
                </div>
                <div class="plan-summary-trial">14-day free trial · no credit card required</div>
            </div>

            @if($errors->any() && !$errors->hasAny(['work_email','full_name','company_name','desired_slug','plan']))
                <div class="alert-danger">{{ $errors->first() }}</div>
            @endif

            @error('plan')
                <div class="alert-danger">{{ $message }}</div>
            @enderror

            <div class="field">
                <label for="full_name">Your name</label>
                <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" autocomplete="name" required maxlength="255" placeholder="Amos Lim">
                @error('full_name')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="work_email">Work email</label>
                <input type="email" id="work_email" name="work_email" value="{{ old('work_email') }}" autocomplete="email" required maxlength="255" placeholder="you@yourcompany.com">
                @error('work_email')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="company_name">Company name</label>
                <input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}" autocomplete="organization" required maxlength="255" placeholder="Acme Sdn Bhd">
                @error('company_name')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="desired_slug">Workspace URL</label>
                <div class="slug-input">
                    <input type="text" id="desired_slug" name="desired_slug" value="{{ old('desired_slug') }}" required minlength="3" maxlength="60" pattern="[a-z0-9](?:[a-z0-9-]{1,58}[a-z0-9])?" placeholder="acme">
                    <span class="suffix">.{{ config('eiaaw.tenant_domain') }}</span>
                </div>
                <div class="hint">Lowercase letters, numbers, hyphens. 3–60 characters.</div>
                @error('desired_slug')<div class="error">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="submit">
                Continue → confirm by email
            </button>

            <p class="legal">
                By signing up you agree to our
                <a href="/terms">Terms of Service</a> and
                <a href="/privacy">Privacy Policy</a>.
                Already have an account?
                <a href="/find-workspace">Find your workspace</a>.
            </p>
        </form>
    </main>

</div>
</body>
</html>
