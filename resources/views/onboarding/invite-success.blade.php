<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Submission received · {{ config('eiaaw.product_name', 'EIAAW Workforce') }}</title>
<link rel="icon" type="image/png" href="{{ asset('brand/shield.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Instrument+Serif:ital@0;1&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="{{ asset('brand/eiaaw-tokens.css') }}" rel="stylesheet">
<style>
body { background: var(--bg); font-family: var(--sans); color: var(--ink); min-height: 100vh; }
.brand-header { background: var(--bg-warm); border-bottom: 1px solid var(--line-soft); }
.brand-header .lockup-text strong { font-family: var(--sans); font-weight: 600; font-size: 15px; color: var(--ink); letter-spacing: -0.01em; }
.brand-header .lockup-text small { font-family: var(--mono); font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--mute); }
.success-card { background: var(--surface); border: 1px solid var(--line-soft); border-radius: 18px; padding: 48px 40px; text-align: center; box-shadow: 0 1px 2px rgba(15,26,29,0.04); }
.success-icon { width: 72px; height: 72px; background: var(--primary-tint); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 22px; color: var(--primary-dark); font-size: 32px; }
.success-card h1 { font-family: var(--sans); font-weight: 500; font-size: clamp(24px, 3vw, 32px); letter-spacing: -0.02em; color: var(--ink); margin: 0 0 10px; }
.success-card h1 em { font-family: var(--serif); font-style: italic; font-weight: 400; color: var(--primary-dark); }
.success-card p { font-size: 15px; line-height: 1.6; color: var(--ink-2); margin: 0; }
.footer-mini { margin-top: 32px; font-family: var(--mono); font-size: 10px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--mute); text-align: center; }
</style>
</head>
<body>
<div class="brand-header py-3 px-4 mb-5">
    <div class="container" style="max-width: 800px;">
        <span class="eiaaw-lockup">
            <img src="{{ asset('brand/shield.png') }}" alt="EIAAW Workforce" style="width:34px; height:34px;">
            <span class="lockup-text d-flex flex-column" style="line-height: 1.05;">
                <strong>EIAAW Workforce</strong>
                <small>AI · Human Partnerships</small>
            </span>
        </span>
    </div>
</div>

<div class="container" style="max-width: 560px;">
    <div class="success-card">
        <div class="success-icon">
            <i class="bi bi-check-lg"></i>
        </div>
        <h1>Submission <em>received</em>.</h1>
        <p>Thank you. Your personal details have been recorded. The HR team will complete your onboarding and contact you shortly.</p>
    </div>
    <p class="footer-mini">EIAAW Solutions · Made in Malaysia</p>
</div>
</body>
</html>
