<!DOCTYPE html>
<html lang="en">
<head>
    @include('auth.partials._shell-head', ['title' => 'Verify · ' . config('eiaaw.product_name', 'EIAAW Workforce')])
</head>
<body>
<div class="auth-shell">

    @include('auth.partials._aside', [
        'quote' => 'One more step. The <em>second key</em> proves it\'s really you, not just someone who knows your password.',
    ])

    <main class="auth-main">
        <div class="auth-form">
            <span class="eyebrow">Two-factor authentication</span>
            <h1>Enter your <em>code</em>.</h1>
            <p class="lead">From your authenticator app — Google Authenticator, 1Password, Authy, or similar.</p>

            @if($errors->any())
                <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
            @endif

            <form action="{{ route('two-factor.verify') }}" method="POST" id="codeForm">
                @csrf

                <div id="codeSection">
                    <label for="code" class="form-label">Authentication code</label>
                    <input type="text" name="code" id="code"
                           class="auth-otp-input"
                           placeholder="000000" maxlength="6" pattern="[0-9]{6}"
                           inputmode="numeric" autocomplete="one-time-code" autofocus>
                </div>

                <div class="d-none" id="recoverySection">
                    <label for="recovery_code" class="form-label">Recovery code</label>
                    <input type="text" name="recovery_code" id="recovery_code"
                           class="form-control"
                           placeholder="xxxxxxxx-xxxxxxxx" autocomplete="off">
                </div>

                <button type="submit" class="auth-submit" style="margin-top: 24px;">
                    Verify
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <p style="text-align:center; margin-top: 18px;">
                <a href="#" class="auth-link" id="toggleRecovery" style="font-size: 13px;">
                    Use a recovery code instead
                </a>
            </p>

            <div class="auth-divider"></div>

            <p style="text-align:center; font-size: 13px; color: var(--mute);">
                <a href="{{ route('login') }}" class="auth-link">&larr; Back to sign in</a>
            </p>

            <p class="footer-mini text-center">&copy; {{ date('Y') }} EIAAW Solutions</p>
        </div>
    </main>

</div>
<script nonce="{{ $cspNonce ?? '' }}">
    document.getElementById('toggleRecovery').addEventListener('click', toggleRecoveryMode);
    function toggleRecoveryMode(e) {
        e.preventDefault();
        var codeSection = document.getElementById('codeSection');
        var recoverySection = document.getElementById('recoverySection');
        var toggleLink = document.getElementById('toggleRecovery');
        if (recoverySection.classList.contains('d-none')) {
            recoverySection.classList.remove('d-none');
            codeSection.classList.add('d-none');
            codeSection.querySelector('input').value = '';
            toggleLink.textContent = 'Use authenticator code instead';
            recoverySection.querySelector('input').focus();
        } else {
            codeSection.classList.remove('d-none');
            recoverySection.classList.add('d-none');
            recoverySection.querySelector('input').value = '';
            toggleLink.textContent = 'Use a recovery code instead';
            codeSection.querySelector('input').focus();
        }
    }
</script>
</body>
</html>
