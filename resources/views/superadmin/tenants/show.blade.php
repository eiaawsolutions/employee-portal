@extends('layouts.app')

@section('title', $tenant->name . ' · Tenant detail')
@section('page-title', $tenant->name)

@section('content')
<style>
    .tenant-wrap { max-width: 1180px; margin: 24px auto; padding: 0 clamp(16px, 3vw, 28px); }
    .tenant-back { color: var(--mute); text-decoration: none; font-size: 13px; display: inline-block; margin-bottom: 14px; }
    .tenant-back:hover { color: var(--ink); }
    .tenant-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; flex-wrap: wrap; margin-bottom: 28px; }
    .tenant-head h1 { font-family: var(--sans); font-weight: 500; font-size: clamp(26px, 2.6vw, 34px); letter-spacing: -0.02em; margin: 4px 0 6px; color: var(--ink); }
    .tenant-head h1 em { font-family: var(--serif); font-style: italic; font-weight: 400; color: var(--primary-dark); }
    .tenant-head .url { font-family: var(--mono); font-size: 13px; color: var(--mute); }
    .tenant-head .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-family: var(--sans); font-size: 11px; font-weight: 500; text-transform: capitalize; margin-right: 6px; }
    .badge.status-active     { background: rgba(47,140,110,0.10); color: var(--success); }
    .badge.status-suspended  { background: rgba(180,65,43,0.10); color: var(--danger); }
    .badge.status-canceled   { background: rgba(107,122,127,0.12); color: var(--mute); }
    .badge.plan-starter      { background: rgba(107,122,127,0.10); color: var(--ink-2); }
    .badge.plan-growth       { background: rgba(31,168,150,0.12); color: var(--primary-dark); }
    .badge.plan-scale        { background: rgba(17,118,106,0.18); color: var(--primary-dark); }
    .badge.plan-enterprise   { background: var(--ink); color: var(--bg); }

    .grid { display: grid; grid-template-columns: 2fr 1fr; gap: 22px; }
    .card { background: var(--surface); border: 1px solid var(--line-soft); border-radius: 14px; padding: 22px 24px; margin-bottom: 18px; }
    .card h3 { font-family: var(--sans); font-weight: 500; font-size: 16px; color: var(--ink); margin: 0 0 14px; letter-spacing: -0.01em; }
    .kv { display: grid; grid-template-columns: 1fr 2fr; gap: 8px 16px; font-size: 14px; }
    .kv dt { color: var(--mute); font-family: var(--mono); font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.08em; padding-top: 2px; }
    .kv dd { margin: 0; color: var(--ink); }

    .danger-zone { border-color: rgba(180,65,43,0.25); background: #FBF6F4; }
    .danger-zone h3 { color: var(--danger); }
    .danger-zone p { font-size: 13px; color: var(--ink-2); margin: 0 0 14px; line-height: 1.5; }

    .btn-action {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 18px; border-radius: 999px;
        font-family: var(--sans); font-size: 13px; font-weight: 500;
        cursor: pointer; border: 1px solid transparent;
    }
    .btn-suspend { background: var(--danger); color: var(--bg); border-color: var(--danger); }
    .btn-suspend:hover { background: #8e3422; border-color: #8e3422; }
    .btn-reactivate { background: var(--success); color: var(--bg); border-color: var(--success); }
    .btn-reactivate:hover { background: #266f57; border-color: #266f57; }

    .users-table { width: 100%; border-collapse: collapse; }
    .users-table th { text-align: left; padding: 10px 12px; font-family: var(--mono); font-size: 10.5px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.1em; color: var(--mute); border-bottom: 1px solid var(--line-soft); }
    .users-table td { padding: 12px; border-bottom: 1px solid var(--line-soft); font-size: 13.5px; }
    .users-table tr:last-child td { border-bottom: 0; }

    @media (max-width: 880px) {
        .grid { grid-template-columns: 1fr; }
    }
</style>

<div class="tenant-wrap">
    <a href="{{ route('superadmin.tenants.index') }}" class="tenant-back">&larr; All tenants</a>

    @if (session('status'))
        <div style="background: rgba(47,140,110,0.08); color: var(--success); padding: 10px 14px; border-radius: 10px; margin-bottom: 16px; font-size: 13.5px;">{{ session('status') }}</div>
    @endif

    <div class="tenant-head">
        <div>
            <span class="badge plan-{{ $tenant->plan }}">{{ $tenant->plan }}</span>
            <span class="badge status-{{ $tenant->status }}">{{ $tenant->status }}</span>
            <h1>{{ $tenant->name }} <em>workspace</em></h1>
            <div class="url">https://{{ $tenant->slug }}.ep.eiaawsolutions.com</div>
        </div>
    </div>

    <div class="grid">
        <div>
            <div class="card">
                <h3>Workspace details</h3>
                <dl class="kv">
                    <dt>Name</dt>          <dd>{{ $tenant->name }}</dd>
                    <dt>Legal name</dt>    <dd>{{ $tenant->legal_name ?? '—' }}</dd>
                    <dt>Slug</dt>          <dd><code>{{ $tenant->slug }}</code></dd>
                    <dt>Country</dt>       <dd>{{ $tenant->country_code }}</dd>
                    <dt>Currency</dt>      <dd>{{ $tenant->billing_currency }}</dd>
                    <dt>Plan</dt>          <dd>{{ $tenant->plan }} · {{ $tenant->plan_seats }} seats</dd>
                    <dt>Created</dt>       <dd>{{ $tenant->created_at->format('Y-m-d H:i') }}</dd>
                    @if ($tenant->trial_ends_at)
                        <dt>Trial ends</dt>    <dd>{{ $tenant->trial_ends_at->format('Y-m-d') }} ({{ $tenant->trial_ends_at->diffForHumans() }})</dd>
                    @endif
                    @if ($tenant->suspended_at)
                        <dt>Suspended</dt>     <dd>{{ $tenant->suspended_at->format('Y-m-d H:i') }}</dd>
                        <dt>Reason</dt>        <dd>{{ $tenant->suspension_reason ?? '—' }}</dd>
                    @endif
                    @if ($tenant->canceled_at)
                        <dt>Canceled</dt>      <dd>{{ $tenant->canceled_at->format('Y-m-d H:i') }}</dd>
                    @endif
                </dl>
            </div>

            <div class="card">
                <h3>Stripe billing</h3>
                <dl class="kv">
                    <dt>Customer ID</dt>      <dd><code>{{ $tenant->stripe_customer_id ?? '—' }}</code></dd>
                    <dt>Subscription ID</dt>  <dd><code>{{ $tenant->stripe_subscription_id ?? '—' }}</code></dd>
                    <dt>Status</dt>           <dd>{{ $tenant->subscription_status ?? '—' }}</dd>
                    <dt>Past due since</dt>   <dd>{{ $tenant->past_due_at?->format('Y-m-d') ?? '—' }}</dd>
                    <dt>Payment method</dt>   <dd>{{ $tenant->pm_type ?? '—' }} {{ $tenant->pm_last_four ? '··'.$tenant->pm_last_four : '' }}</dd>
                </dl>
            </div>

            <div class="card">
                <h3>Users on this workspace ({{ $tenant->users->count() }})</h3>
                @if ($tenant->users->isEmpty())
                    <p style="font-size: 13px; color: var(--mute); margin: 0;">No users yet.</p>
                @else
                    <table class="users-table">
                        <thead>
                            <tr><th>Name</th><th>Email</th><th>Tenant role</th><th>System role</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($tenant->users as $u)
                                <tr>
                                    <td>{{ $u->name }}</td>
                                    <td><code style="font-size: 12px;">{{ $u->work_email }}</code></td>
                                    <td>{{ $u->pivot->tenant_role ?? '—' }}</td>
                                    <td>{{ $u->role ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <div>
            <div class="card">
                <h3>AI usage · last 30 days</h3>
                @if ($usage->isEmpty())
                    <p style="font-size: 13px; color: var(--mute); margin: 0;">No AI activity recorded.</p>
                @else
                    <dl class="kv">
                        <dt>Requests</dt>           <dd>{{ number_format($aiTotals['requests']) }}</dd>
                        <dt>Total tokens</dt>       <dd>{{ number_format($aiTotals['tokens']) }}</dd>
                        <dt>Total spend</dt>        <dd>USD {{ number_format($aiTotals['cost_usd'], 2) }}</dd>
                        <dt>Days with activity</dt> <dd>{{ $aiTotals['days'] }}</dd>
                        <dt>Plan budget</dt>        <dd>USD {{ number_format($tenant->aiBudgetUsd(), 2) }} /mo</dd>
                    </dl>
                @endif
            </div>

            <div class="card">
                <h3>Cost stack <span style="color:var(--mute); font-weight:400; font-size:13px;">· latest snapshot</span></h3>
                @if (!$snapshot)
                    <p style="font-size: 13px; color: var(--mute); margin: 0;">
                        No usage snapshot yet — run <code>php artisan meter:tenant-usage --tenant={{ $tenant->slug }}</code>
                        or wait for the daily 03:45 schedule.
                    </p>
                @else
                    <dl class="kv">
                        <dt>Estimated MRR</dt>
                        <dd>
                            @if ($mrr !== null)
                                USD {{ number_format($mrr, 2) }} /mo
                                <span style="color:var(--mute); font-size:12px;">({{ $tenant->planPriceUsdMonthly() }} × {{ $tenant->plan_seats }} seats)</span>
                            @else
                                Custom (Enterprise)
                            @endif
                        </dd>
                        <dt>AI cost (30d)</dt>      <dd>USD {{ number_format($snapshot->ai_cost_usd_30d, 2) }}</dd>
                        <dt>Storage</dt>            <dd>{{ number_format($snapshot->storage_mb) }} MB</dd>
                        <dt>DB rows</dt>            <dd>{{ number_format($snapshot->db_row_count_total) }}</dd>
                        <dt>Users</dt>              <dd>{{ $snapshot->users_count }} / {{ $tenant->plan_seats }} seats</dd>
                        <dt>Employees</dt>          <dd>{{ number_format($snapshot->employees_count) }}</dd>
                        <dt>Last active</dt>        <dd>{{ $snapshot->last_active_at?->diffForHumans() ?? '—' }}</dd>
                        <dt>Snapshot date</dt>      <dd style="color:var(--mute); font-size:12px;">{{ $snapshot->usage_date->format('Y-m-d') }}</dd>
                    </dl>
                @endif
            </div>

            <div class="card danger-zone">
                <h3>Danger zone</h3>

                @if ($tenant->status === 'suspended')
                    <p>This workspace is currently <strong>suspended</strong>. Users cannot sign in. Reactivate to restore access.</p>
                    <form method="POST" action="{{ route('superadmin.tenants.reactivate', $tenant) }}">
                        @csrf
                        <button type="submit" class="btn-action btn-reactivate">
                            <i class="bi bi-play-fill"></i> Reactivate workspace
                        </button>
                    </form>
                @elseif ($tenant->status === 'active')
                    <p>Suspending blocks all sign-ins for this workspace's users until reactivated. Use for non-payment, abuse, or compliance issues.</p>
                    <form method="POST" action="{{ route('superadmin.tenants.suspend', $tenant) }}">
                        @csrf
                        <label for="reason" style="display:block; font-size:12px; color:var(--ink-2); margin-bottom:6px;">Reason (audit-logged)</label>
                        <input type="text" name="reason" id="reason" required maxlength="500"
                               placeholder="e.g. Past-due subscription, payment retries exhausted"
                               style="width:100%; padding:9px 12px; border:1px solid var(--line); border-radius:8px; font-size:13px; margin-bottom:10px;">
                        <button type="submit" class="btn-action btn-suspend">
                            <i class="bi bi-pause-fill"></i> Suspend workspace
                        </button>
                    </form>
                @else
                    <p>This workspace is <strong>{{ $tenant->status }}</strong>. No actions available.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
