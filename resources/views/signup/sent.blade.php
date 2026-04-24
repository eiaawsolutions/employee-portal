<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check your email · {{ config('eiaaw.product_name', 'EIAAW Workforce') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('brand/shield.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="{{ asset('brand/eiaaw-tokens.css') }}" rel="stylesheet">
    <style>
        body { background: var(--bg); font-family: var(--sans); color: var(--ink); margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 32px 20px; }
        .card {
            max-width: 520px; width: 100%; background: var(--surface);
            border: 1px solid var(--line-soft); border-radius: 18px;
            padding: clamp(32px, 5vw, 56px);
            box-shadow: 0 1px 2px rgba(15,26,29,0.04), 0 24px 60px -24px rgba(15,26,29,0.16);
        }
        h1 { font-weight: 500; font-size: clamp(28px, 3vw, 36px); letter-spacing: -0.02em; line-height: 1.1; margin: 20px 0 12px; color: var(--ink); }
        h1 em { font-family: var(--serif); font-style: italic; font-weight: 400; color: var(--primary-dark); }
        .lead { font-size: 15px; line-height: 1.55; color: var(--ink-2); }
        .email-pill {
            display: inline-block; font-family: var(--mono); font-size: 13px;
            background: var(--primary-tint); color: var(--primary-dark);
            padding: 6px 12px; border-radius: 999px; margin: 4px 0;
            letter-spacing: 0.02em;
        }
        .meta { font-family: var(--mono); font-size: 11px; text-transform: uppercase; letter-spacing: 0.14em; color: var(--mute); margin-top: 32px; }
        .next-steps { background: var(--bg-warm); border: 1px solid var(--line-soft); border-radius: 12px; padding: 20px 24px; margin-top: 28px; font-size: 14px; color: var(--ink-2); line-height: 1.55; }
        .next-steps strong { color: var(--ink); }
        a { color: var(--primary-dark); }
    </style>
</head>
<body>
    <div class="card">
        <a href="/" class="eiaaw-lockup" style="margin-bottom: 12px;">
            <img src="{{ asset('brand/shield.png') }}" alt="EIAAW Workforce">
            <span class="eiaaw-lockup-text">
                <strong>EIAAW Workforce</strong>
                <small>AI &middot; Human Partnerships</small>
            </span>
        </a>

        <span class="eyebrow">Check your inbox</span>
        <h1>We sent a <em>confirmation link</em> to:</h1>

        <p class="lead">
            <span class="email-pill">{{ session('signup_email', 'your work email') }}</span>
        </p>

        <p class="lead">Click the link in the email to set your password and finish creating your workspace. The link expires in 24 hours.</p>

        <div class="next-steps">
            <strong>Didn't get it?</strong>
            Check your spam folder, or
            <a href="{{ route('signup.form') }}">try a different email address</a>.
            Still stuck? Email us at
            <a href="mailto:{{ config('eiaaw.support_email') }}">{{ config('eiaaw.support_email') }}</a>.
        </div>

        <p class="meta">EIAAW Solutions &middot; Made in Malaysia</p>
    </div>
</body>
</html>
