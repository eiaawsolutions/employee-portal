@extends('layouts.app')

@section('title', 'Tenants · Platform admin')
@section('page-title', 'Tenants')

@section('content')
<style>
    .tenants-wrap { max-width: 1180px; margin: 24px auto; padding: 0 clamp(16px, 3vw, 28px); }
    .tenants-head { margin-bottom: 24px; }
    .tenants-head .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; background: rgba(17,118,106,0.08); color: var(--primary-dark); font-size: 11px; letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 10px; }
    .tenants-head h1 { font-family: var(--sans); font-weight: 500; font-size: clamp(24px, 2.6vw, 32px); letter-spacing: -0.02em; margin: 0 0 6px; color: var(--ink); }
    .tenants-head h1 em { font-family: var(--serif); font-style: italic; font-weight: 400; color: var(--primary-dark); }
    .tenants-head p { color: var(--ink-2); font-size: 15px; max-width: 640px; margin: 0; line-height: 1.5; }

    .stat-row { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; margin: 24px 0 28px; }
    .stat-card { background: var(--surface); border: 1px solid var(--line-soft); border-radius: 14px; padding: 18px 18px; }
    .stat-card .label { font-family: var(--mono); font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--mute); margin-bottom: 6px; }
    .stat-card .value { font-family: var(--sans); font-size: 28px; font-weight: 500; color: var(--ink); letter-spacing: -0.02em; line-height: 1; }
    .stat-card.warn .value { color: var(--warn); }
    .stat-card.danger .value { color: var(--danger); }

    .filter-row { display: flex; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; align-items: center; }
    .filter-row input, .filter-row select {
        padding: 9px 12px; border: 1px solid var(--line); border-radius: 10px;
        font-family: var(--sans); font-size: 13.5px; background: var(--surface); color: var(--ink);
    }
    .filter-row input:focus, .filter-row select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(31,168,150,0.12); }
    .filter-row input[name=q] { min-width: 280px; }
    .filter-row button { padding: 9px 16px; background: var(--ink); color: var(--bg); border: 0; border-radius: 999px; font-family: var(--sans); font-size: 13px; font-weight: 500; cursor: pointer; }
    .filter-row button:hover { background: var(--primary-dark); }
    .filter-row a.clear { color: var(--mute); font-size: 13px; text-decoration: none; }
    .filter-row a.clear:hover { color: var(--ink); }

    .tenant-table { width: 100%; border-collapse: separate; border-spacing: 0; background: var(--surface); border: 1px solid var(--line-soft); border-radius: 14px; overflow: hidden; }
    .tenant-table th { text-align: left; padding: 12px 16px; background: var(--bg-warm); font-family: var(--mono); font-size: 10.5px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.1em; color: var(--mute); border-bottom: 1px solid var(--line-soft); }
    .tenant-table td { padding: 14px 16px; border-bottom: 1px solid var(--line-soft); font-size: 14px; color: var(--ink); vertical-align: middle; }
    .tenant-table tr:last-child td { border-bottom: 0; }
    .tenant-table tr:hover td { background: var(--bg); }
    .tenant-table .name a { color: var(--ink); text-decoration: none; font-weight: 500; }
    .tenant-table .name a:hover { color: var(--primary-dark); }
    .tenant-table .slug { font-family: var(--mono); font-size: 12px; color: var(--mute); }
    .tenant-table .badge { display: inline-block; padding: 3px 9px; border-radius: 999px; font-family: var(--sans); font-size: 11px; font-weight: 500; text-transform: capitalize; }
    .badge.status-active     { background: rgba(47,140,110,0.10); color: var(--success); }
    .badge.status-suspended  { background: rgba(180,65,43,0.10); color: var(--danger); }
    .badge.status-canceled   { background: rgba(107,122,127,0.12); color: var(--mute); }
    .badge.plan-starter      { background: rgba(107,122,127,0.10); color: var(--ink-2); }
    .badge.plan-growth       { background: rgba(31,168,150,0.12); color: var(--primary-dark); }
    .badge.plan-scale        { background: rgba(17,118,106,0.18); color: var(--primary-dark); }
    .badge.plan-enterprise   { background: var(--ink); color: var(--bg); }
    .badge.trial             { background: rgba(198,138,46,0.12); color: var(--warn); margin-left: 6px; }
    .badge.past-due          { background: rgba(180,65,43,0.10); color: var(--danger); margin-left: 6px; }

    .pagination { display: flex; gap: 6px; justify-content: center; margin-top: 22px; }
    .pagination a, .pagination span { padding: 8px 12px; border: 1px solid var(--line); border-radius: 8px; font-size: 13px; color: var(--ink-2); text-decoration: none; }
    .pagination a:hover { background: var(--bg-warm); }
    .pagination .active { background: var(--ink); color: var(--bg); border-color: var(--ink); }
    .pagination .disabled { opacity: 0.4; }

    .empty-state { text-align: center; padding: 60px 20px; color: var(--mute); }
    .empty-state h3 { color: var(--ink); font-weight: 500; margin: 12px 0 6px; }

    @media (max-width: 1024px) {
        .stat-row { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 640px) {
        .stat-row { grid-template-columns: repeat(2, 1fr); }
        .tenant-table { font-size: 13px; }
        .tenant-table th:nth-child(3), .tenant-table td:nth-child(3) { display: none; }
    }
</style>

<div class="tenants-wrap">
    <div class="tenants-head">
        <span class="pill">Platform · EIAAW staff only</span>
        <h1>Tenants <em>directory</em></h1>
        <p>Every workspace on EIAAW Workforce. Suspend, reactivate, view usage. RLS-bypassed query — visible only to platform admins listed in <code>EIAAW_PLATFORM_ADMINS</code>.</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success" style="background: rgba(47,140,110,0.08); color: var(--success); padding: 10px 14px; border-radius: 10px; margin-bottom: 16px; font-size: 13.5px;">{{ session('status') }}</div>
    @endif

    <div class="stat-row">
        <div class="stat-card"><div class="label">Total</div><div class="value">{{ number_format($stats['total']) }}</div></div>
        <div class="stat-card"><div class="label">Active</div><div class="value">{{ number_format($stats['active']) }}</div></div>
        <div class="stat-card warn"><div class="label">Suspended</div><div class="value">{{ number_format($stats['suspended']) }}</div></div>
        <div class="stat-card"><div class="label">Canceled</div><div class="value">{{ number_format($stats['canceled']) }}</div></div>
        <div class="stat-card"><div class="label">In trial</div><div class="value">{{ number_format($stats['in_trial']) }}</div></div>
        <div class="stat-card danger"><div class="label">Past due</div><div class="value">{{ number_format($stats['past_due']) }}</div></div>
    </div>

    <form method="GET" action="{{ route('superadmin.tenants.index') }}" class="filter-row">
        <input type="search" name="q" value="{{ $q }}" placeholder="Search by name, slug, or legal name…">
        <select name="status">
            <option value="">All statuses</option>
            <option value="active"    @selected($status === 'active')>Active</option>
            <option value="suspended" @selected($status === 'suspended')>Suspended</option>
            <option value="canceled"  @selected($status === 'canceled')>Canceled</option>
        </select>
        <select name="plan">
            <option value="">All plans</option>
            <option value="starter"    @selected($plan === 'starter')>Starter</option>
            <option value="growth"     @selected($plan === 'growth')>Growth</option>
            <option value="scale"      @selected($plan === 'scale')>Scale</option>
            <option value="enterprise" @selected($plan === 'enterprise')>Enterprise</option>
        </select>
        <button type="submit">Filter</button>
        @if ($q || $status || $plan)
            <a href="{{ route('superadmin.tenants.index') }}" class="clear">Clear</a>
        @endif
    </form>

    @if ($tenants->isEmpty())
        <div class="empty-state">
            <i class="bi bi-buildings" style="font-size: 36px; color: var(--line);"></i>
            <h3>No tenants match.</h3>
            <p>Try clearing filters, or sign up the first workspace at <a href="{{ url('/signup') }}" class="auth-link">/signup</a>.</p>
        </div>
    @else
        <table class="tenant-table">
            <thead>
                <tr>
                    <th>Workspace</th>
                    <th>Plan</th>
                    <th>Status</th>
                    <th>Users</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tenants as $t)
                    <tr>
                        <td class="name">
                            <a href="{{ route('superadmin.tenants.show', $t) }}">{{ $t->name }}</a>
                            <div class="slug">{{ $t->slug }}.ep.eiaawsolutions.com</div>
                        </td>
                        <td>
                            <span class="badge plan-{{ $t->plan }}">{{ $t->plan }}</span>
                        </td>
                        <td>
                            <span class="badge status-{{ $t->status }}">{{ $t->status }}</span>
                            @if ($t->trial_ends_at && $t->trial_ends_at->isFuture())
                                <span class="badge trial">Trial · {{ $t->trial_ends_at->diffForHumans(null, true) }}</span>
                            @endif
                            @if ($t->past_due_at)
                                <span class="badge past-due">Past due</span>
                            @endif
                        </td>
                        <td>{{ $t->users_count }} / {{ $t->plan_seats }}</td>
                        <td style="color: var(--mute); font-size: 13px;">{{ $t->created_at->format('Y-m-d') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pagination">
            {{ $tenants->links() }}
        </div>
    @endif
</div>
@endsection
