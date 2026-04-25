<!DOCTYPE html>
<html lang="en">
<head>
    @include('auth.partials._shell-head', ['title' => 'Recovery codes · ' . config('eiaaw.product_name', 'EIAAW Workforce')])
    <style>
        .recovery-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
            margin: 18px 0 20px;
        }
        .recovery-code {
            font-family: var(--mono); font-size: 14px; font-weight: 500;
            color: var(--ink); letter-spacing: 0.06em;
            background: var(--bg); border: 1px solid var(--line);
            padding: 12px 14px; border-radius: 10px; text-align: center;
        }
        .recovery-actions {
            display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
            margin-bottom: 20px;
        }
        .recovery-action-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 11px 16px;
            background: var(--surface); color: var(--ink);
            border: 1px solid var(--line); border-radius: 10px;
            font-family: var(--sans); font-size: 13.5px; font-weight: 500;
            cursor: pointer; letter-spacing: -0.005em;
            transition: background 0.18s var(--ease), border-color 0.18s var(--ease), color 0.18s var(--ease);
        }
        .recovery-action-btn:hover {
            background: var(--bg-warm); border-color: var(--ink-2); color: var(--ink);
        }
        .recovery-action-btn.is-success {
            background: var(--primary-tint); border-color: var(--primary); color: var(--primary-dark);
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

            <div class="recovery-actions">
                <button type="button" id="downloadRecoveryCodes" class="recovery-action-btn">
                    <i class="bi bi-download"></i>
                    <span data-default-label>Download .txt</span>
                </button>
                <button type="button" id="copyRecoveryCodes" class="recovery-action-btn">
                    <i class="bi bi-clipboard"></i>
                    <span data-default-label>Copy codes</span>
                </button>
            </div>

            <a href="{{ route('profile') }}" class="auth-submit" style="text-decoration: none;">
                I've saved them — return to profile
                <i class="bi bi-arrow-right"></i>
            </a>

            <p class="footer-mini text-center">&copy; {{ date('Y') }} EIAAW Solutions</p>
        </div>
    </main>

</div>

<script id="recoveryCodesData" type="application/json">@json($recoveryCodes)</script>
<script nonce="{{ $cspNonce ?? '' }}">
    (function () {
        var codes = JSON.parse(document.getElementById('recoveryCodesData').textContent);
        var product = @json(config('eiaaw.product_name', 'EIAAW Workforce'));
        var stamp = new Date().toISOString().slice(0, 10);
        var fileName = product.replace(/[^A-Za-z0-9]+/g, '-').replace(/^-|-$/g, '').toLowerCase()
            + '-recovery-codes-' + stamp + '.txt';

        var header = product + ' — Two-factor recovery codes\n'
            + 'Generated: ' + new Date().toString() + '\n'
            + 'Each code can be used once. Store securely.\n'
            + '------------------------------------------------------------\n\n';
        var body = codes.join('\n') + '\n';
        var fileContents = header + body;

        function flashLabel(button, message) {
            var span = button.querySelector('[data-default-label]');
            if (!span) return;
            var original = span.textContent;
            span.textContent = message;
            button.classList.add('is-success');
            setTimeout(function () {
                span.textContent = original;
                button.classList.remove('is-success');
            }, 2000);
        }

        var downloadBtn = document.getElementById('downloadRecoveryCodes');
        downloadBtn.addEventListener('click', function () {
            var blob = new Blob([fileContents], { type: 'text/plain;charset=utf-8' });
            var url = URL.createObjectURL(blob);
            var link = document.createElement('a');
            link.href = url;
            link.download = fileName;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
            flashLabel(downloadBtn, 'Downloaded');
        });

        var copyBtn = document.getElementById('copyRecoveryCodes');
        copyBtn.addEventListener('click', function () {
            var text = codes.join('\n');
            var done = function () { flashLabel(copyBtn, 'Copied'); };
            var fail = function () { flashLabel(copyBtn, 'Copy failed'); };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(done).catch(function () {
                    fallbackCopy(text) ? done() : fail();
                });
            } else {
                fallbackCopy(text) ? done() : fail();
            }
        });

        function fallbackCopy(text) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'absolute';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            var ok = false;
            try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
            document.body.removeChild(ta);
            return ok;
        }
    })();
</script>
</body>
</html>
