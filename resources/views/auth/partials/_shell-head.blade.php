{{--
    Shared <head> + auth-shell CSS for every standalone auth page.
    Keeps two-factor-setup, two-factor-challenge, register, set-password,
    forgot-password, reset-password, and recovery-codes visually consistent
    with the canonical login page.

    Pages should:
      @include('auth.partials._shell-head', ['title' => 'Page · EIAAW Workforce'])
    then render their own <body> contents using the .auth-* classes below.
--}}
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

{{-- Meta Pixel Code — nonce'd to satisfy the enforced CSP (SecurityHeaders.php) --}}
<script nonce="{{ $cspNonce ?? '' }}">
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window,document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '1516303113491153');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=1516303113491153&ev=PageView&noscript=1"/></noscript>
{{-- End Meta Pixel Code --}}

<title>{{ $title ?? config('eiaaw.product_name', 'EIAAW Workforce') }}</title>
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
        padding: clamp(40px, 5vw, 72px) clamp(28px, 4vw, 56px);
        display: flex; flex-direction: column; justify-content: space-between;
        border-right: 1px solid var(--line-soft);
    }
    .auth-aside-quote {
        font-family: var(--serif); font-style: italic; font-weight: 400;
        font-size: clamp(28px, 3vw, 44px); line-height: 1.18;
        color: var(--ink); letter-spacing: -0.015em;
        max-width: 22ch;
    }
    .auth-aside-quote em { font-family: var(--serif); color: var(--primary-dark); }
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
    .auth-form { width: 100%; max-width: 420px; }
    .auth-form h1 {
        font-family: var(--sans); font-weight: 500;
        font-size: 36px; line-height: 1.05;
        letter-spacing: -0.025em; color: var(--ink);
        margin: 28px 0 8px;
    }
    .auth-form h1 em {
        font-family: var(--serif); font-style: italic; font-weight: 400;
        color: var(--primary-dark);
    }
    .auth-form .lead {
        font-size: 15px; color: var(--ink-2); margin-bottom: 32px; line-height: 1.5;
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
    .auth-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
    .auth-link { color: var(--primary-dark); text-decoration: none; font-weight: 500; }
    .auth-link:hover { color: var(--primary); text-decoration: underline; }
    .auth-otp-input {
        width: 100%; padding: 18px 20px; border: 1px solid var(--line);
        border-radius: 12px; background: var(--bg);
        font-family: var(--mono); font-size: 24px; font-weight: 500;
        letter-spacing: 0.4em; text-align: center;
        color: var(--ink);
    }
    .auth-otp-input:focus {
        border-color: var(--primary); outline: none;
        box-shadow: 0 0 0 3px rgba(31,168,150,0.12);
    }
    .auth-secret-code {
        font-family: var(--mono); font-size: 16px; font-weight: 500;
        letter-spacing: 0.18em; color: var(--ink);
        background: var(--bg); border: 1px solid var(--line);
        padding: 14px 18px; border-radius: 10px; text-align: center;
        word-break: break-all;
    }
    .auth-qr-frame {
        display: flex; align-items: center; justify-content: center;
        padding: 18px; background: var(--surface);
        border: 1px solid var(--line-soft); border-radius: 16px;
        margin: 0 auto;
    }
    .auth-qr-frame img { display: block; max-width: 200px; height: auto; border-radius: 8px; }
    .alert { border-radius: 10px; font-size: 13.5px; padding: 10px 14px; }
    .alert-success { background: var(--primary-tint); border: 1px solid var(--primary); color: var(--primary-dark); }
    .alert-danger  { background: #FBE9E4; border: 1px solid var(--danger); color: var(--danger); }
    .alert-warning { background: #FBF1DD; border: 1px solid var(--warn); color: var(--warn); }
    .alert-info    { background: var(--bg-warm); border: 1px solid var(--line); color: var(--ink-2); }
    .form-check-input { border-color: var(--line); }
    .form-check-input:checked { background-color: var(--primary-dark); border-color: var(--primary-dark); }
    .form-check-label { font-size: 13px; color: var(--ink-2); }
    .footer-mini {
        margin-top: 40px; font-family: var(--mono); font-size: 10px;
        text-transform: uppercase; letter-spacing: 0.12em; color: var(--mute);
    }
    .auth-divider {
        height: 1px; background: var(--line-soft); margin: 28px 0;
    }
    @media (max-width: 880px) {
        .auth-shell { grid-template-columns: 1fr; }
        .auth-aside { display: none; }
    }
</style>
