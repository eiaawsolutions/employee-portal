<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Set your password · {{ config('eiaaw.product_name', 'EIAAW Workforce') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('brand/shield.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="{{ asset('brand/eiaaw-tokens.css') }}" rel="stylesheet">
    <style>
        body { background: var(--bg); font-family: var(--sans); color: var(--ink); margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 32px 20px; }
        .card {
            max-width: 480px; width: 100%; background: var(--surface);
            border: 1px solid var(--line-soft); border-radius: 18px;
            padding: clamp(32px, 5vw, 56px);
            box-shadow: 0 1px 2px rgba(15,26,29,0.04), 0 24px 60px -24px rgba(15,26,29,0.16);
        }
        h1 { font-weight: 500; font-size: clamp(28px, 3vw, 36px); letter-spacing: -0.02em; line-height: 1.1; margin: 20px 0 12px; color: var(--ink); }
        h1 em { font-family: var(--serif); font-style: italic; font-weight: 400; color: var(--primary-dark); }
        .lead { font-size: 15px; line-height: 1.55; color: var(--ink-2); margin-bottom: 24px; }
        .summary { background: var(--bg-warm); border: 1px solid var(--line-soft); border-radius: 12px; padding: 14px 18px; font-size: 13.5px; color: var(--ink-2); margin-bottom: 24px; line-height: 1.55; }
        .summary strong { color: var(--ink); }
        .summary code { font-family: var(--mono); background: var(--surface); padding: 2px 6px; border-radius: 4px; border: 1px solid var(--line); font-size: 12px; }
        .field { margin-bottom: 16px; }
        .field label { display: block; font-size: 13px; font-weight: 500; color: var(--ink-2); margin-bottom: 6px; }
        .field .hint { font-size: 12px; color: var(--mute); margin-top: 4px; font-family: var(--mono); }
        .field .error { font-size: 12.5px; color: var(--danger); margin-top: 4px; }
        input[type="password"] {
            width: 100%; box-sizing: border-box;
            border: 1px solid var(--line); border-radius: 10px;
            padding: 11px 14px; font-family: var(--sans); font-size: 14.5px;
            background: var(--surface); color: var(--ink);
            transition: border-color 0.18s var(--ease), box-shadow 0.18s var(--ease);
        }
        input:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(31,168,150,0.12); }
        .submit {
            width: 100%; padding: 14px 22px;
            background: var(--ink); color: var(--bg);
            border: 1px solid var(--ink); border-radius: 999px;
            font-family: var(--sans); font-size: 14px; font-weight: 500;
            cursor: pointer; letter-spacing: -0.005em; margin-top: 8px;
            transition: background 0.35s var(--ease), transform 0.35s var(--ease);
        }
        .submit:hover { background: var(--primary-dark); border-color: var(--primary-dark); transform: translateY(-1px); }
        .alert-danger { background: #FBE9E4; border: 1px solid var(--danger); color: var(--danger); border-radius: 10px; font-size: 13.5px; padding: 10px 14px; margin-bottom: 16px; }
        .meta { font-family: var(--mono); font-size: 11px; text-transform: uppercase; letter-spacing: 0.14em; color: var(--mute); margin-top: 32px; }
    </style>
</head>
<body>
    <div class="card">
        <a href="/" class="eiaaw-lockup" style="margin-bottom: 12px;">
            <img src="{{ asset('brand/shield.png') }}" alt="EIAAW Workforce">
            <span class="eiaaw-lockup-text">
                <strong>EIAAW Workforce</strong>
                <small>AI &middot; Human Partnerships</small>
            </span>
        </a>

        <span class="eyebrow">Last step</span>
        <h1>Welcome, <em>{{ $invite->full_name }}</em>.</h1>
        <p class="lead">Set a password and we'll spin up your workspace. The 14-day trial begins now.</p>

        <div class="summary">
            <strong>Workspace:</strong> {{ $invite->company_name }}<br>
            <strong>URL:</strong> <code>{{ $invite->desired_slug }}.{{ config('eiaaw.tenant_domain') }}</code><br>
            <strong>Sign-in email:</strong> {{ $invite->work_email }}
        </div>

        @if($errors->any() && !$errors->has('password'))
            <div class="alert-danger">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('signup.confirm.submit', $invite->confirmation_token) }}" method="POST">
            @csrf

            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" autocomplete="new-password" required minlength="12">
                <div class="hint">At least 12 characters</div>
                @error('password')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required minlength="12">
            </div>

            <button type="submit" class="submit">Create workspace and start trial →</button>
        </form>

        <p class="meta">EIAAW Solutions &middot; Made in Malaysia</p>
    </div>
</body>
</html>
