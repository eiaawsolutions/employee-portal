<!DOCTYPE html>
<html lang="en">
<head>
    @include('auth.partials._shell-head', ['title' => 'Reset password · ' . config('eiaaw.product_name', 'EIAAW Workforce')])
</head>
<body>
<div class="auth-shell">

    @include('auth.partials._aside', [
        'quote' => 'Forgot it happens. We\'ll send a link to your <em>work inbox</em> — no security theatre.',
    ])

    <main class="auth-main">
        <div class="auth-form">
            <span class="eyebrow">Account recovery</span>
            <h1>Reset your <em>password</em>.</h1>
            <p class="lead">Enter your work email and we'll send a sign-in link valid for 60 minutes.</p>

            @if(session('status'))
                <div class="alert alert-success mb-3">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
            @endif

            <form action="{{ route('password.email') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Work email</label>
                    <input type="email" name="email" id="email"
                           class="form-control"
                           value="{{ old('email', session('prefill_email')) }}"
                           placeholder="you@yourcompany.com" required autofocus>
                </div>

                <button type="submit" class="auth-submit">
                    Send reset link
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <div class="auth-divider"></div>

            <p style="text-align:center; font-size: 13px; color: var(--mute);">
                <a href="{{ route('login') }}" class="auth-link">&larr; Back to sign in</a>
            </p>

            <p class="footer-mini text-center">&copy; {{ date('Y') }} EIAAW Solutions</p>
        </div>
    </main>

</div>
</body>
</html>
