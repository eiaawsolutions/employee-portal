<!DOCTYPE html>
<html lang="en">
<head>
    @include('auth.partials._shell-head', ['title' => 'Set up two-factor · ' . config('eiaaw.product_name', 'EIAAW Workforce')])
</head>
<body>
<div class="auth-shell">

    @include('auth.partials._aside', [
        'quote' => 'A second key for the people who hold the platform\'s <em>integration secrets</em> — and the keys to every workspace inside it.',
    ])

    <main class="auth-main">
        <div class="auth-form">
            <span class="eyebrow">Account security</span>
            <h1>Add your <em>authenticator</em>.</h1>
            <p class="lead">
                Scan the QR code with Google Authenticator, 1Password, Authy, or any TOTP-compatible app. Then enter the 6-digit code below to confirm.
            </p>

            @if(session('warning'))
                <div class="alert alert-warning mb-3">{{ session('warning') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
            @endif

            <div class="auth-qr-frame">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=0&data={{ urlencode($qrCodeUrl) }}"
                     alt="Two-factor QR code" loading="lazy">
            </div>

            <p style="text-align:center; font-size:13px; color:var(--mute); margin: 20px 0 8px;">
                Or enter this key manually
            </p>
            <div class="auth-secret-code">{{ $secret }}</div>

            <div class="auth-divider"></div>

            <form action="{{ route('two-factor.confirm') }}" method="POST">
                @csrf
                <label for="code" class="form-label">Verification code</label>
                <input type="text" name="code" id="code"
                       class="auth-otp-input"
                       placeholder="000000" maxlength="6" pattern="[0-9]{6}"
                       inputmode="numeric" autocomplete="one-time-code"
                       required autofocus>
                <p style="margin: 10px 0 24px; font-size: 12px; color: var(--mute);">
                    Enter the 6-digit code your authenticator app generates right now.
                </p>

                <button type="submit" class="auth-submit">
                    Verify &amp; enable
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <p style="text-align:center; margin-top: 24px; font-size: 13px; color: var(--mute);">
                @if(Auth::user()->mustSetupTwoFactor())
                    Two-factor authentication is required for your role.
                @else
                    <a href="{{ route('profile') }}" class="auth-link">Cancel and return to profile</a>
                @endif
            </p>

            <p class="footer-mini text-center">&copy; {{ date('Y') }} EIAAW Solutions</p>
        </div>
    </main>

</div>
</body>
</html>
