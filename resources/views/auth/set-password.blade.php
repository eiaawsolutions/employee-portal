<!DOCTYPE html>
<html lang="en">
<head>
    @include('auth.partials._shell-head', ['title' => 'Set password · ' . config('eiaaw.product_name', 'EIAAW Workforce')])
    <style>
        .strength-row { display: flex; gap: 6px; margin-bottom: 10px; }
        .strength-bar { height: 4px; flex: 1; border-radius: 4px; background: var(--line-soft); transition: background 0.18s var(--ease); }
        .req-list { list-style: none; padding: 0; margin: 8px 0 0; font-size: 12px; color: var(--mute); }
        .req-list li { margin-bottom: 4px; transition: color 0.18s var(--ease); }
        .req-list li.met { color: var(--success); }
        .req-list li i { margin-right: 6px; }
        .pw-toggle { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: 0; color: var(--mute); cursor: pointer; padding: 6px; }
        .pw-toggle:hover { color: var(--ink); }
        .pw-wrapper { position: relative; }
        .strength-label { font-family: var(--mono); font-size: 11px; text-transform: uppercase; letter-spacing: 0.12em; }
        .match-msg { font-size: 12px; margin-top: 6px; display: none; }
    </style>
</head>
<body>
<div class="auth-shell">

    @include('auth.partials._aside', [
        'quote' => 'Almost in. Pick a password your team\'s <em>compliance officer</em> would approve of — and you can still remember.',
    ])

    <main class="auth-main">
        <div class="auth-form">
            <span class="eyebrow">Activate account</span>
            <h1>Set your <em>password</em>.</h1>
            <p class="lead">Final step. Activating <strong>{{ $verified_email }}</strong>.</p>

            <a href="{{ route('register') }}" class="auth-link" style="font-size: 13px; display: inline-block; margin-bottom: 18px;">
                &larr; Use a different email
            </a>

            @if ($errors->any())
                <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
            @endif

            <form action="{{ route('register') }}" method="POST" id="registerForm">
                @csrf
                <input type="hidden" name="work_email" value="{{ $verified_email }}">

                <div class="mb-3">
                    <label for="password" class="form-label">Create password</label>
                    <div class="pw-wrapper">
                        <input type="password" name="password" id="password"
                               class="form-control"
                               placeholder="Min. 8 characters, number &amp; symbol"
                               autocomplete="new-password" required>
                        <button type="button" class="pw-toggle" data-target="password" aria-label="Toggle password visibility">
                            <i class="bi bi-eye" id="eyePw"></i>
                        </button>
                    </div>

                    <div id="strengthSection" style="display:none; margin-top: 12px;">
                        <div class="strength-row">
                            <div class="strength-bar" id="bar1"></div>
                            <div class="strength-bar" id="bar2"></div>
                            <div class="strength-bar" id="bar3"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <ul class="req-list">
                                <li id="req-length"><i class="bi bi-circle"></i>At least 8 characters</li>
                                <li id="req-number"><i class="bi bi-circle"></i>At least one number</li>
                                <li id="req-symbol"><i class="bi bi-circle"></i>At least one symbol (@, #, !, etc.)</li>
                            </ul>
                            <span class="strength-label" id="strengthLabel"></span>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">Confirm password</label>
                    <div class="pw-wrapper">
                        <input type="password" name="password_confirmation" id="password_confirmation"
                               class="form-control"
                               placeholder="Re-enter your password"
                               autocomplete="new-password" required>
                        <button type="button" class="pw-toggle" data-target="password_confirmation" aria-label="Toggle password visibility">
                            <i class="bi bi-eye" id="eyeCf"></i>
                        </button>
                    </div>
                    <div id="matchMsg" class="match-msg"></div>
                </div>

                <button type="submit" class="auth-submit" id="submitBtn" disabled>
                    Create account
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <div class="auth-divider"></div>

            <p style="text-align:center; font-size: 13px; color: var(--mute);">
                Already activated? <a href="{{ route('login') }}" class="auth-link">Sign in</a>
            </p>

            <p class="footer-mini text-center">&copy; {{ date('Y') }} EIAAW Solutions</p>
        </div>
    </main>

</div>

<script nonce="{{ $cspNonce ?? '' }}">
    const colors = ['#B4412B', '#C68A2E', '#1FA896', '#11766A'];
    const labels = ['Weak', 'Fair', 'Good', 'Strong'];

    document.querySelectorAll('.pw-toggle').forEach(btn => {
        btn.addEventListener('click', function () {
            const t = document.getElementById(this.dataset.target);
            const i = this.querySelector('i');
            if (t.type === 'password') { t.type = 'text'; i.className = 'bi bi-eye-slash'; }
            else { t.type = 'password'; i.className = 'bi bi-eye'; }
        });
    });

    document.getElementById('password').addEventListener('input', checkStrength);
    document.getElementById('password_confirmation').addEventListener('input', checkMatch);

    function checkStrength() {
        const pw = document.getElementById('password').value;
        const section = document.getElementById('strengthSection');

        if (pw.length === 0) { section.style.display = 'none'; updateBtn(); return; }
        section.style.display = 'block';

        const hasLen = pw.length >= 8;
        const hasNum = /[0-9]/.test(pw);
        const hasSym = /[^A-Za-z0-9]/.test(pw);
        const score = [hasLen, hasNum, hasSym].filter(Boolean).length;
        const color = colors[score];

        setReq('req-length', hasLen);
        setReq('req-number', hasNum);
        setReq('req-symbol', hasSym);

        for (let i = 1; i <= 3; i++) {
            document.getElementById('bar' + i).style.background = i <= score ? color : 'var(--line-soft)';
        }

        const lbl = document.getElementById('strengthLabel');
        lbl.textContent = labels[score];
        lbl.style.color = color;

        updateBtn();
        checkMatch();
    }

    function setReq(id, met) {
        const el = document.getElementById(id);
        el.classList.toggle('met', met);
        el.querySelector('i').className = met ? 'bi bi-check-circle-fill' : 'bi bi-circle';
    }

    function checkMatch() {
        const pw = document.getElementById('password').value;
        const cf = document.getElementById('password_confirmation').value;
        const msg = document.getElementById('matchMsg');

        if (cf.length === 0) { msg.style.display = 'none'; updateBtn(); return; }

        msg.style.display = 'block';
        if (pw === cf) {
            msg.textContent = 'Passwords match.';
            msg.style.color = 'var(--success)';
        } else {
            msg.textContent = 'Passwords do not match.';
            msg.style.color = 'var(--danger)';
        }
        updateBtn();
    }

    function updateBtn() {
        const pw = document.getElementById('password').value;
        const cf = document.getElementById('password_confirmation').value;
        const hasLen = pw.length >= 8;
        const hasNum = /[0-9]/.test(pw);
        const hasSym = /[^A-Za-z0-9]/.test(pw);
        const allMet = hasLen && hasNum && hasSym && pw === cf && cf.length > 0;
        document.getElementById('submitBtn').disabled = !allMet;
    }
</script>
</body>
</html>
