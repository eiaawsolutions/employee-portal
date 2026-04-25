<!DOCTYPE html>
<html lang="en">
<head>
    @include('auth.partials._shell-head', ['title' => 'New password · ' . config('eiaaw.product_name', 'EIAAW Workforce')])
    <style>
        .req-list { list-style: none; padding: 0; margin: 10px 0 0; font-size: 12px; color: var(--mute); }
        .req-list li { margin-bottom: 4px; transition: color 0.18s var(--ease); }
        .req-list li.met { color: var(--success); }
        .req-list li i { margin-right: 6px; }
        .pw-toggle {
            position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
            background: none; border: 0; color: var(--mute); cursor: pointer; padding: 6px;
        }
        .pw-toggle:hover { color: var(--ink); }
        .pw-wrapper { position: relative; }
    </style>
</head>
<body>
<div class="auth-shell">

    @include('auth.partials._aside', [
        'quote' => 'Pick something <em>memorable but unguessable</em>. The system enforces the basics; you bring the discipline.',
    ])

    <main class="auth-main">
        <div class="auth-form">
            <span class="eyebrow">Set new password</span>
            <h1>Choose a <em>new password</em>.</h1>
            <p class="lead">Make it 8+ characters, with at least one number and one symbol.</p>

            @if($errors->any())
                <div class="alert alert-danger mb-3">
                    @foreach($errors->all() as $e)
                        <div>{{ $e }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('password.update') }}" method="POST" id="resetForm">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-3">
                    <label for="email" class="form-label">Work email</label>
                    <input type="email" name="email" id="email"
                           class="form-control"
                           value="{{ old('email', $email ?? '') }}"
                           required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">New password</label>
                    <div class="pw-wrapper">
                        <input type="password" name="password" id="password"
                               class="form-control" autocomplete="new-password" required>
                        <button type="button" class="pw-toggle" data-target="password" aria-label="Toggle password visibility">
                            <i class="bi bi-eye" id="eyeIcon1"></i>
                        </button>
                    </div>
                    <ul class="req-list" id="reqList">
                        <li id="req-len"><i class="bi bi-circle"></i>At least 8 characters</li>
                        <li id="req-num"><i class="bi bi-circle"></i>At least one number</li>
                        <li id="req-sym"><i class="bi bi-circle"></i>At least one symbol (@, #, !, etc.)</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">Confirm new password</label>
                    <div class="pw-wrapper">
                        <input type="password" name="password_confirmation" id="password_confirmation"
                               class="form-control" autocomplete="new-password" required>
                        <button type="button" class="pw-toggle" data-target="password_confirmation" aria-label="Toggle password visibility">
                            <i class="bi bi-eye" id="eyeIcon2"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="auth-submit">
                    Reset password
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
<script nonce="{{ $cspNonce ?? '' }}">
    document.querySelectorAll('.pw-toggle').forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.dataset.target;
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            if (input.type === 'password') { input.type = 'text'; icon.className = 'bi bi-eye-slash'; }
            else { input.type = 'password'; icon.className = 'bi bi-eye'; }
        });
    });

    document.getElementById('password').addEventListener('input', function () {
        const val = this.value;
        const checks = {
            'req-len': val.length >= 8,
            'req-num': /[0-9]/.test(val),
            'req-sym': /[\W_]/.test(val),
        };
        Object.entries(checks).forEach(([id, met]) => {
            const el = document.getElementById(id);
            el.classList.toggle('met', met);
            el.querySelector('i').className = met ? 'bi bi-check-circle-fill' : 'bi bi-circle';
        });
    });
</script>
</body>
</html>
