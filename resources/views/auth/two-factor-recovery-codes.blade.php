<!DOCTYPE html>
<html lang="en">
<head>
    @include('auth.partials._shell-head', ['title' => 'Recovery codes · ' . config('eiaaw.product_name', 'EIAAW Workforce')])
    <style>
        .recovery-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
            margin: 18px 0 24px;
        }
        .recovery-code {
            font-family: var(--mono); font-size: 14px; font-weight: 500;
            color: var(--ink); letter-spacing: 0.06em;
            background: var(--bg); border: 1px solid var(--line);
            padding: 12px 14px; border-radius: 10px; text-align: center;
        }
    </style>
</head>
<body>
<div class="auth-shell">

    @include('auth.partials._aside', [
        'quote' => 'Phone lost? Authenticator wiped? These ten codes are your <em>way back in</em>. Each works once.',
    ])

    <main class="auth-main">
        <div class="auth-form">
            <span class="eyebrow">Two-factor enabled</span>
            <h1>Save your <em>recovery codes</em>.</h1>
            <p class="lead">Each code works once. Store them in your password manager — you'll need them if you lose access to your authenticator app.</p>

            <div class="alert alert-warning mb-3">
                <strong>Important.</strong> We will not show these again. Print them, save them to 1Password, or write them down — but store them somewhere only you can reach.
            </div>

            <div class="recovery-grid">
                @foreach($recoveryCodes as $code)
                    <span class="recovery-code">{{ $code }}</span>
                @endforeach
            </div>

            <a href="{{ route('profile') }}" class="auth-submit" style="text-decoration: none;">
                I've saved them — return to profile
                <i class="bi bi-arrow-right"></i>
            </a>

            <p class="footer-mini text-center">&copy; {{ date('Y') }} EIAAW Solutions</p>
        </div>
    </main>

</div>
</body>
</html>
