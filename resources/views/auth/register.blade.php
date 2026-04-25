<!DOCTYPE html>
<html lang="en">
<head>
    @include('auth.partials._shell-head', ['title' => 'Activate account · ' . config('eiaaw.product_name', 'EIAAW Workforce')])
</head>
<body>
<div class="auth-shell">

    @include('auth.partials._aside', [
        'quote' => 'Already invited? Activate the account your <em>HR team set up</em> for you. New here? Try the trial signup instead.',
    ])

    <main class="auth-main">
        <div class="auth-form">
            <span class="eyebrow">Activate account</span>
            <h1>Welcome to the <em>workspace</em>.</h1>
            <p class="lead">Use the work email your HR or IT team assigned you. We'll verify it exists, then let you set a password.</p>

            <div class="alert alert-info mb-3">
                <i class="bi bi-info-circle me-2"></i>
                If you don't have a work email yet, contact your HR or IT team — your account is created when they invite you.
            </div>

            @if ($errors->any())
                <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
            @endif

            <form action="{{ route('register.checkEmail') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="work_email" class="form-label">Work email</label>
                    <input type="email" name="work_email" id="work_email"
                           class="form-control"
                           value="{{ old('work_email') }}"
                           placeholder="you@yourcompany.com" required autofocus>
                </div>

                <button type="submit" class="auth-submit">
                    Continue
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <div class="auth-divider"></div>

            <p style="text-align:center; font-size: 13px; color: var(--mute);">
                Already activated? <a href="{{ route('login') }}" class="auth-link">Sign in</a>
            </p>
            <p style="text-align:center; font-size: 13px; color: var(--mute); margin-top: 8px;">
                New customer? <a href="{{ route('signup.form') }}" class="auth-link">Start a 14-day trial</a>
            </p>

            <p class="footer-mini text-center">&copy; {{ date('Y') }} EIAAW Solutions</p>
        </div>
    </main>

</div>
</body>
</html>
