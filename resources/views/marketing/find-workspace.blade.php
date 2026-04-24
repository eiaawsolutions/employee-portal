@extends('layouts.marketing')

@section('title', "Find your workspace — EIAAW Workforce")
@section('description', 'Enter your work email and we will send you a list of workspaces your account belongs to.')

@push('head')
<style>
    .fw-wrap {
        padding: clamp(60px, 8vw, 120px) 0 clamp(60px, 8vw, 100px);
        display: grid; place-items: center;
    }
    .fw-card {
        width: 100%; max-width: 520px;
        background: var(--surface);
        border: 1px solid var(--line-soft);
        border-radius: 20px;
        padding: clamp(32px, 4vw, 48px);
        box-shadow: 0 20px 60px -30px rgba(15,26,29,0.15);
    }
    .fw-card h1 {
        font-family: var(--sans); font-weight: 500;
        font-size: clamp(28px, 3vw, 36px); line-height: 1.1;
        letter-spacing: -0.025em; margin: 18px 0 16px; color: var(--ink);
    }
    .fw-card h1 em { font-family: var(--serif); font-style: italic; font-weight: 400; color: var(--primary-dark); }
    .fw-card > p { color: var(--ink-2); font-size: 15px; line-height: 1.55; margin: 0 0 28px; }

    .fw-field { margin-bottom: 16px; }
    .fw-field label {
        display: block; font-size: 13px; font-weight: 500;
        color: var(--ink-2); margin-bottom: 6px; letter-spacing: -0.005em;
    }
    .fw-field input {
        width: 100%; box-sizing: border-box;
        border: 1px solid var(--line); border-radius: 10px;
        padding: 12px 14px; font-family: var(--sans); font-size: 14.5px;
        background: var(--surface); color: var(--ink);
        transition: border-color 0.18s var(--ease), box-shadow 0.18s var(--ease);
    }
    .fw-field input:focus {
        border-color: var(--primary); outline: none;
        box-shadow: 0 0 0 3px rgba(31,168,150,0.12);
    }
    .fw-field .error { font-size: 12.5px; color: var(--danger); margin-top: 6px; }

    .fw-submit {
        width: 100%; padding: 13px 20px;
        background: var(--ink); color: var(--bg);
        border: 1px solid var(--ink); border-radius: 999px;
        font-family: var(--sans); font-size: 14px; font-weight: 500;
        cursor: pointer; letter-spacing: -0.005em;
        transition: all 0.3s var(--ease);
    }
    .fw-submit:hover { background: var(--primary-dark); border-color: var(--primary-dark); }

    .fw-note {
        margin-top: 22px; padding-top: 22px;
        border-top: 1px solid var(--line-soft);
        font-size: 13px; color: var(--mute); line-height: 1.55;
    }

    .fw-confirm {
        text-align: center;
    }
    .fw-confirm-mark {
        width: 56px; height: 56px; margin: 0 auto 18px;
        border-radius: 50%;
        background: var(--primary-tint);
        color: var(--primary-dark);
        display: inline-flex; align-items: center; justify-content: center;
        font-family: var(--serif); font-size: 32px; font-style: italic;
    }
    .fw-confirm h2 {
        font-family: var(--sans); font-weight: 500;
        font-size: clamp(22px, 2.6vw, 28px); line-height: 1.15;
        letter-spacing: -0.02em; margin: 0 0 14px; color: var(--ink);
    }
    .fw-confirm p { color: var(--ink-2); font-size: 15px; line-height: 1.55; margin: 0 0 20px; }
    .fw-confirm code {
        font-family: var(--mono); font-size: 13px;
        background: var(--bg-warm); padding: 3px 8px;
        border-radius: 6px; color: var(--ink);
    }
    .fw-confirm-back {
        display: inline-block; margin-top: 20px;
        font-size: 13.5px; font-weight: 500;
    }
</style>
@endpush

@section('content')

<div class="fw-wrap">
    <div class="mk-container">
        <div class="fw-card">

            @if(!empty($submitted) && $submitted === true)
                {{-- Neutral confirmation: never leak whether the email matched. --}}
                <div class="fw-confirm">
                    <div class="fw-confirm-mark">✓</div>
                    <h2>Check your inbox</h2>
                    <p>If an EIAAW Workforce account exists for <code>{{ $submittedEmail ?? 'that address' }}</code>, we've sent you a list of the workspaces you can sign into.</p>
                    <p style="font-size: 13px;">The email arrives within a few minutes. Check spam if it doesn't — and make sure to search for <code>{{ config('eiaaw.product_name') }}</code>.</p>
                    <a href="{{ route('marketing.find-workspace') }}" class="fw-confirm-back">← Try another email</a>
                </div>
            @else
                <span class="eyebrow">Sign in</span>
                <h1>Find <em>your workspace.</em></h1>
                <p>Enter the email you use at work. We'll email you every EIAAW Workforce workspace your account belongs to, with a direct sign-in link for each.</p>

                <form method="POST" action="{{ route('marketing.find-workspace.lookup') }}" novalidate>
                    @csrf

                    <div class="fw-field">
                        <label for="work_email">Work email</label>
                        <input type="email" name="work_email" id="work_email"
                               value="{{ old('work_email') }}"
                               placeholder="you@company.com"
                               required autocomplete="email" autofocus>
                        @error('work_email')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="fw-submit">Send me the list</button>
                </form>

                <div class="fw-note">
                    Remember the workspace URL? Go directly to it: <code>workspace.{{ config('eiaaw.tenant_domain') }}/login</code>.
                </div>
            @endif

        </div>
    </div>
</div>

@endsection
