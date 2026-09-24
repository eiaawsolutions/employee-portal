<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="robots" content="noindex, nofollow">
    {{-- Consent gate: the Meta Pixel loads only after the visitor opts in (public/consent.js) --}}
    <script src="{{ asset('consent.js') }}?v=20260924ep" nonce="{{ $cspNonce ?? '' }}"></script>

    <title>Set up your workspace · {{ config('eiaaw.product_name', 'EIAAW Workforce') }}</title>
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
        input[type="text"], input[type="email"], input[type="password"], input[type="number"] {
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
        .plan-summary-note { font-size: 12px; color: var(--primary-dark); margin-top: 4px; font-family: var(--mono); letter-spacing: 0.02em; }
        .period-options { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .period-option { display: flex; align-items: center; gap: 8px; border: 1px solid var(--line); border-radius: 10px; padding: 10px 12px; cursor: pointer; font-size: 14px; color: var(--ink-2); }
        .period-option:has(input:checked) { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(31,168,150,0.12); color: var(--ink); }
        .period-option input { margin: 0; accent-color: var(--primary-dark); }
        .period-option small { color: var(--primary-dark); font-family: var(--mono); font-size: 10.5px; letter-spacing: 0.04em; }
        .checkout-total { display: flex; justify-content: space-between; align-items: baseline; border-top: 1px solid var(--line-soft); margin-top: 10px; padding-top: 10px; font-size: 13px; color: var(--ink-2); }
        .checkout-total strong { font-size: 16px; color: var(--ink); font-variant-numeric: tabular-nums; }
        .alert-info { background: var(--bg-warm); border: 1px solid var(--line); color: var(--ink-2); border-radius: 10px; font-size: 13.5px; padding: 10px 14px; margin-bottom: 16px; }
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
                Your workspace, <em>live in minutes</em> — pay once at checkout, then set your password.
            </p>

            <ul class="aside-bullets">
                <li>Full HR, payroll, claims, leave, attendance, IT assets</li>
                <li>AI assistant that answers from your own records</li>
                <li>Billed in ringgit, monthly or annually, through Stripe</li>
                <li>Cancel any time; access runs to the end of the paid period</li>
            </ul>
        </div>

        <p class="aside-meta">EIAAW Solutions &middot; Made in Malaysia</p>
    </aside>

    <main class="auth-main">
        <form action="{{ route('signup.start') }}" method="POST" class="auth-form">
            @csrf
            <input type="hidden" name="plan" value="{{ $plan }}">

            <span class="eyebrow">New workspace</span>
            <h1>Set up your <em>workspace</em>.</h1>
            <p class="lead">Pay securely with Stripe, then set your password — your workspace is created as soon as payment goes through. No free trial.</p>

            @php
                $tier = config('eiaaw.pricing.tiers.' . $plan, []);
                $monthly = (int) ($tier['monthly_myr'] ?? 0);
                $annualMonths = 12 - (int) config('eiaaw.pricing.annual_months_free', 2);
                $minSeats = (int) config("plans.{$plan}.min_seats", 5);
                $period = old('period', request('period') === 'annual' ? 'annual' : 'monthly');
            @endphp
            <div class="plan-summary" id="plan-summary"
                 data-monthly="{{ $monthly }}" data-annual-months="{{ $annualMonths }}" data-min="{{ $minSeats }}">
                <div class="plan-summary-line">
                    <span class="plan-summary-label">Selected plan</span>
                    <a href="{{ route('marketing.pricing') }}" class="plan-summary-change">Change</a>
                </div>
                <div class="plan-summary-row">
                    <strong>{{ $tier['name'] ?? ucfirst($plan) }}</strong>
                    <span class="plan-summary-price">RM&nbsp;{{ $monthly }} per active employee / month</span>
                </div>
                <div class="plan-summary-note">Minimum {{ $minSeats }} employees · billed in MYR</div>
                <div class="checkout-total">
                    <span id="checkout-total-label">Due today</span>
                    <strong id="checkout-total">RM&nbsp;{{ number_format($monthly * max($minSeats, (int) old('headcount', $minSeats))) }}</strong>
                </div>
            </div>

            @if(request('canceled'))
                <div class="alert-info" role="status">Checkout was cancelled — nothing was charged. You can pick up where you left off.</div>
            @endif

            @if($errors->any() && !$errors->hasAny(['work_email','full_name','company_name','desired_slug','plan','consent','period','headcount']))
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

            <div class="field">
                <label for="headcount">Number of employees</label>
                <input type="number" id="headcount" name="headcount" value="{{ old('headcount', $minSeats) }}" required min="1" max="5000" step="1" inputmode="numeric">
                <div class="hint">Billed per active employee, minimum {{ $minSeats }}.</div>
                @error('headcount')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label>Billing</label>
                <div class="period-options">
                    <label class="period-option">
                        <input type="radio" name="period" value="monthly" @checked($period === 'monthly')> Monthly
                    </label>
                    <label class="period-option">
                        <input type="radio" name="period" value="annual" @checked($period === 'annual')>
                        <span>Annual <small>2 months free</small></span>
                    </label>
                </div>
                @error('period')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="consent" style="display:flex;gap:10px;align-items:flex-start;font-weight:400;line-height:1.5;cursor:pointer">
                    <input type="checkbox" id="consent" name="consent" value="1" required style="margin-top:3px;width:auto;flex:none" @checked(old('consent'))>
                    <span>I agree to the <a href="{{ route('marketing.terms') }}" target="_blank" rel="noopener" style="color:#11766A">Terms of Service</a> and to EIAAW processing my details, including through service providers outside my country, as described in the <a href="{{ route('marketing.privacy') }}" target="_blank" rel="noopener" style="color:#11766A">Privacy Notice</a>.</span>
                </label>
                @error('consent')<div class="error">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="submit">
                Continue to secure checkout →
            </button>

            <p class="legal">
                Payment is handled by Stripe; we never see your card number.
                Already have an account?
                <a href="/find-workspace">Find your workspace</a>.
            </p>
        </form>
    </main>

</div>
<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    var box = document.getElementById('plan-summary');
    var out = document.getElementById('checkout-total');
    var label = document.getElementById('checkout-total-label');
    var input = document.getElementById('headcount');
    if (!box || !out || !input) return;
    var monthly = +box.dataset.monthly, months = +box.dataset.annualMonths, min = +box.dataset.min;

    function render() {
        var seats = Math.max(min, parseInt(input.value, 10) || 0);
        var annual = (document.querySelector('input[name="period"]:checked') || {}).value === 'annual';
        var total = monthly * seats * (annual ? months : 1);
        out.textContent = 'RM ' + total.toLocaleString('en-MY');
        label.textContent = 'Due today (' + seats + ' employees, ' + (annual ? 'billed yearly' : 'billed monthly') + ')';
    }
    input.addEventListener('input', render);
    document.querySelectorAll('input[name="period"]').forEach(function (r) { r.addEventListener('change', render); });
    render();
})();
</script>
</body>
</html>
