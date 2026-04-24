@extends('layouts.app')

@section('title', 'Dedicated database · Enterprise')
@section('page-title', 'Dedicated database')

@section('content')
<style>
    .dd-wrap {
        max-width: 720px; margin: 40px auto;
        padding: clamp(28px, 4vw, 48px);
        background: var(--surface, #FFFFFF);
        border: 1px solid var(--line-soft, #E8DFCC);
        border-radius: 20px;
    }
    .dd-wrap h1 {
        font-family: var(--sans, 'Inter', sans-serif);
        font-weight: 500; font-size: clamp(26px, 3vw, 34px);
        letter-spacing: -0.02em; margin: 16px 0 8px;
    }
    .dd-wrap h1 em {
        font-family: var(--serif, 'Instrument Serif', serif);
        font-style: italic; font-weight: 400;
        color: var(--primary-dark, #11766A);
    }
    .dd-lede { color: var(--ink-2, #2A3438); font-size: 15px; line-height: 1.55; margin: 0 0 24px; }
    .dd-pill {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 5px 12px; border-radius: 999px;
        font-family: var(--mono, monospace); font-size: 11px; font-weight: 500;
        text-transform: uppercase; letter-spacing: 0.12em;
    }
    .dd-pill-enterprise { background: var(--primary-tint, #E5F4F1); color: var(--primary-dark, #11766A); }
    .dd-pill-enterprise::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: var(--primary, #1FA896); }
    .dd-status-box {
        padding: 16px 20px; border-radius: 14px;
        margin: 20px 0 28px;
        font-size: 14px;
        border: 1px solid;
    }
    .dd-status-requested { background: rgba(198,138,46,0.08); border-color: rgba(198,138,46,0.3); color: var(--warn, #C68A2E); }
    .dd-status-live { background: rgba(47,140,110,0.08); border-color: rgba(47,140,110,0.3); color: var(--success, #2F8C6E); }

    .dd-field { margin-bottom: 18px; }
    .dd-field label {
        display: block; font-size: 13px; font-weight: 500;
        color: var(--ink-2, #2A3438); margin-bottom: 6px;
    }
    .dd-field select, .dd-field input[type=date], .dd-field textarea {
        width: 100%; box-sizing: border-box;
        padding: 11px 14px; border: 1px solid var(--line, #D9CFBC); border-radius: 10px;
        background: var(--surface, #FFFFFF); color: var(--ink, #0F1A1D);
        font-family: var(--sans, sans-serif); font-size: 14.5px;
    }
    .dd-field textarea { resize: vertical; min-height: 90px; }
    .dd-field .hint { font-family: var(--mono, monospace); font-size: 11px; color: var(--mute, #6B7A7F); margin-top: 4px; letter-spacing: 0.04em; }
    .dd-ack {
        padding: 14px 18px; border-radius: 12px;
        background: var(--bg-warm, #F3EDE0); border: 1px solid var(--line-soft, #E8DFCC);
        font-size: 13.5px; line-height: 1.5; color: var(--ink-2, #2A3438);
        margin: 20px 0 24px;
        display: flex; gap: 12px; align-items: flex-start;
    }
    .dd-ack input[type=checkbox] { margin-top: 3px; flex-shrink: 0; }
    .dd-submit {
        padding: 12px 22px; border-radius: 999px;
        background: var(--ink, #0F1A1D); color: var(--bg, #FAF7F2); border: 0;
        font-family: var(--sans, sans-serif); font-size: 14px; font-weight: 500;
        cursor: pointer; letter-spacing: -0.005em;
    }
    .dd-submit:hover { background: var(--primary-dark, #11766A); }
</style>

<div class="dd-wrap">
    <span class="dd-pill dd-pill-enterprise">Enterprise only</span>
    <h1>Dedicated <em>Postgres instance.</em></h1>
    <p class="dd-lede">
        Your workspace currently shares a Postgres pool with other tenants, isolated by Row-Level Security.
        Enterprise customers can request a dedicated Postgres instance — no shared backend, separate backups, separate resource pool.
    </p>

    @if($alreadyRequested)
        <div class="dd-status-box {{ $tenant->dedicated_db_dsn ? 'dd-status-live' : 'dd-status-requested' }}">
            @if($tenant->dedicated_db_dsn)
                <strong>Status: live.</strong> Your workspace is running on its own Postgres instance.
            @else
                <strong>Status: request on file.</strong> Our ops team is scoping provisioning. We'll email when the migration window is scheduled.
            @endif
        </div>
    @else
        <form method="POST" action="{{ route('superadmin.dedicated-db.request') }}">
            @csrf

            <div class="dd-field">
                <label for="region_preference">Preferred region</label>
                <select name="region_preference" id="region_preference" required>
                    <option value="ap-southeast-1">Asia-Pacific · Singapore (ap-southeast-1)</option>
                    <option value="ap-southeast-3">Asia-Pacific · Jakarta (ap-southeast-3)</option>
                    <option value="eu-west-1">Europe · Ireland (eu-west-1)</option>
                    <option value="us-east-1">US · N. Virginia (us-east-1)</option>
                </select>
                <div class="hint">Data residency · pick the region closest to your users</div>
            </div>

            <div class="dd-field">
                <label for="target_go_live">Target go-live date (optional)</label>
                <input type="date" name="target_go_live" id="target_go_live" min="{{ now()->addDays(14)->toDateString() }}">
                <div class="hint">Minimum 14 days lead time · scoping + provisioning + migration window</div>
            </div>

            <div class="dd-field">
                <label for="compliance_note">Compliance / context note (optional)</label>
                <textarea name="compliance_note" id="compliance_note" placeholder="E.g. regulator requirement, parent-company policy, data-residency mandate…"></textarea>
            </div>

            <label class="dd-ack">
                <input type="checkbox" name="acknowledged" value="1" required>
                <span>
                    I understand that provisioning is a manual operation that requires a scoped quote,
                    coordinated migration window, and adjustment to our subscription terms.
                    EIAAW Solutions will contact the workspace owner within 2 business days.
                </span>
            </label>

            <button type="submit" class="dd-submit">Submit request</button>
        </form>
    @endif
</div>
@endsection
