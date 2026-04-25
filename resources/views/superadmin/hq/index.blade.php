@extends('layouts.app')

@section('title', 'HQ Overview · EIAAW Workforce')
@section('page-title', 'HQ Overview')

@section('content')
<style>
    .hq-wrap { max-width: 1280px; margin: 24px auto; padding: 0 clamp(16px, 3vw, 28px); }
    .hq-head { margin-bottom: 28px; }
    .hq-head h1 {
        font-family: var(--sans); font-weight: 500;
        font-size: clamp(26px, 2.6vw, 34px); letter-spacing: -0.02em;
        margin: 4px 0 6px; color: var(--ink);
    }
    .hq-head h1 em { font-family: var(--serif); font-style: italic; font-weight: 400; color: var(--primary-dark); }
    .hq-head .meta {
        font-family: var(--mono); font-size: 11.5px; color: var(--mute);
        text-transform: uppercase; letter-spacing: 0.1em;
    }

    .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 22px; }
    @media (max-width: 1080px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 540px)  { .stat-grid { grid-template-columns: 1fr; } }

    .stat {
        background: var(--surface); border: 1px solid var(--line-soft);
        border-radius: 14px; padding: 18px 20px;
    }
    .stat .label {
        font-family: var(--mono); font-size: 10.5px; font-weight: 500;
        text-transform: uppercase; letter-spacing: 0.1em; color: var(--mute);
        margin-bottom: 8px;
    }
    .stat .value {
        font-family: var(--sans); font-size: 28px; font-weight: 500;
        letter-spacing: -0.02em; color: var(--ink); line-height: 1.05;
    }
    .stat .value .unit { font-size: 14px; color: var(--mute); margin-left: 4px; font-weight: 400; }
    .stat .sub {
        font-size: 12px; color: var(--ink-2); margin-top: 6px;
    }
    .stat.accent-primary { background: var(--primary-tint); border-color: var(--primary); }
    .stat.accent-primary .value { color: var(--primary-dark); }
    .stat.accent-warn { background: #FBF1DD; border-color: var(--warn); }
    .stat.accent-warn .value { color: var(--warn); }
    .stat.accent-danger { background: #FBE9E4; border-color: var(--danger); }
    .stat.accent-danger .value { color: var(--danger); }

    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; margin-bottom: 22px; }
    @media (max-width: 980px) { .grid-2 { grid-template-columns: 1fr; } }

    .card {
        background: var(--surface); border: 1px solid var(--line-soft);
        border-radius: 14px; padding: 22px 24px;
    }
    .card h3 {
        font-family: var(--sans); font-weight: 500; font-size: 16px;
        color: var(--ink); margin: 0 0 14px; letter-spacing: -0.01em;
    }
    .card h3 .count {
        color: var(--mute); font-weight: 400; font-size: 13px; margin-left: 6px;
    }

    .table { width: 100%; border-collapse: collapse; }
    .table th {
        text-align: left; padding: 8px 10px;
        font-family: var(--mono); font-size: 10.5px; font-weight: 500;
        text-transform: uppercase; letter-spacing: 0.1em; color: var(--mute);
        border-bottom: 1px solid var(--line-soft);
    }
    .table td {
        padding: 10px; border-bottom: 1px solid var(--line-soft);
        font-size: 13.5px; color: var(--ink);
    }
    .table tr:last-child td { border-bottom: 0; }
    .table tr a { color: var(--ink); text-decoration: none; }
    .table tr a:hover { color: var(--primary-dark); }
    .table .num { text-align: right; font-variant-numeric: tabular-nums; }
    .table .mute { color: var(--mute); font-size: 12px; }

    .badge {
        display: inline-block; padding: 2px 8px; border-radius: 999px;
        font-family: var(--sans); font-size: 10.5px; font-weight: 500;
        text-transform: capitalize;
    }
    .badge.plan-starter    { background: rgba(107,122,127,0.10); color: var(--ink-2); }
    .badge.plan-growth     { background: rgba(31,168,150,0.12); color: var(--primary-dark); }
    .badge.plan-scale      { background: rgba(17,118,106,0.18); color: var(--primary-dark); }
    .badge.plan-enterprise { background: var(--ink); color: var(--bg); }

    .util-bar {
        display: inline-block; vertical-align: middle;
        width: 80px; height: 6px; border-radius: 3px;
        background: var(--bg-warm); margin-right: 8px;
        overflow: hidden;
    }
    .util-bar .fill { display: block; height: 100%; background: var(--primary); border-radius: 3px; }
    .util-bar .fill.warn   { background: var(--warn); }
    .util-bar .fill.danger { background: var(--danger); }

    .privacy-note {
        background: var(--bg-warm); border: 1px solid var(--line-soft);
        border-radius: 10px; padding: 14px 18px; margin-top: 28px;
        font-size: 12.5px; color: var(--ink-2); line-height: 1.55;
    }
    .privacy-note strong { color: var(--ink); }

    .empty-meter {
        background: #FBF1DD; border: 1px solid var(--warn);
        border-radius: 10px; padding: 12px 16px; margin-bottom: 18px;
        font-size: 13px; color: var(--warn);
    }
</style>

<div class="hq-wrap">
    <div class="hq-head">
        <h1>EIAAW <em>HQ</em> overview</h1>
        <div class="meta">Fleet snapshot · {{ $today }}</div>
    </div>

    @if ($unmeteredTenants > 0)
        <div class="empty-meter">
            <i class="bi bi-exclamation-circle"></i>
            {{ $unmeteredTenants }} tenant(s) have not been metered yet.
            Run <code>php artisan meter:tenant-usage</code> to refresh, or wait for the daily 03:45 schedule.
        </div>
    @endif

    {{-- ── Top-line fleet stats ───────────────────────────────────────── --}}
    <div class="stat-grid">
        <div class="stat accent-primary">
            <div class="label">Active workspaces</div>
            <div class="value">{{ number_format($totals['active']) }}</div>
            <div class="sub">{{ number_format($totals['all_time']) }} all-time · {{ number_format($totals['signups_30d']) }} new in 30d</div>
        </div>
        <div class="stat">
            <div class="label">Estimated MRR</div>
            <div class="value">${{ number_format($mrr['total_known'], 0) }}<span class="unit">/mo</span></div>
            <div class="sub">
                @if ($mrr['enterprise_unknown'] > 0)
                    + {{ $mrr['enterprise_unknown'] }} enterprise on custom pricing
                @else
                    excludes trial &amp; suspended
                @endif
            </div>
        </div>
        <div class="stat {{ $totals['past_due'] > 0 ? 'accent-warn' : '' }}">
            <div class="label">Past due</div>
            <div class="value">{{ number_format($totals['past_due']) }}</div>
            <div class="sub">{{ number_format($totals['suspended']) }} suspended · {{ number_format($totals['canceled']) }} canceled</div>
        </div>
        <div class="stat {{ $totals['trial_ends_7d'] > 0 ? 'accent-primary' : '' }}">
            <div class="label">Trials ending in 7 days</div>
            <div class="value">{{ number_format($totals['trial_ends_7d']) }}</div>
            <div class="sub">{{ number_format($totals['in_trial']) }} on trial total</div>
        </div>
    </div>

    {{-- ── Fleet usage rollup ─────────────────────────────────────────── --}}
    <div class="stat-grid">
        <div class="stat">
            <div class="label">AI spend · last 30d (fleet)</div>
            <div class="value">${{ number_format($fleetAiCost30d, 2) }}</div>
            <div class="sub">{{ number_format($fleetAiTokens30d) }} tokens</div>
        </div>
        <div class="stat">
            <div class="label">Storage · fleet</div>
            <div class="value">{{ number_format($fleetStorageMb) }}<span class="unit">MB</span></div>
            <div class="sub">private + public buckets</div>
        </div>
        <div class="stat">
            <div class="label">Plans · live</div>
            <div class="value" style="font-size: 14px; line-height: 1.7;">
                @foreach (['starter','growth','scale','enterprise'] as $p)
                    <span class="badge plan-{{ $p }}">{{ ucfirst($p) }} {{ $byPlan[$p] ?? 0 }}</span>
                @endforeach
            </div>
        </div>
        <div class="stat">
            <div class="label">Capacity load</div>
            <div class="value">
                @php
                    $totalSeats   = \App\Models\Tenant::where('status', \App\Models\Tenant::STATUS_ACTIVE)->sum('plan_seats');
                    $totalUsed    = \App\Models\TenantUsageDaily::query()
                        ->whereIn('id', function ($q) { $q->select(\DB::raw('MAX(id)'))->from('tenant_usage_daily')->groupBy('tenant_id'); })
                        ->sum('users_count');
                    $loadPct = $totalSeats > 0 ? round(($totalUsed / $totalSeats) * 100, 1) : 0;
                @endphp
                {{ $loadPct }}<span class="unit">%</span>
            </div>
            <div class="sub">{{ number_format($totalUsed) }} / {{ number_format($totalSeats) }} seats provisioned</div>
        </div>
    </div>

    {{-- ── Top tables ─────────────────────────────────────────────────── --}}
    <div class="grid-2">
        <div class="card">
            <h3>Top 10 tenants by AI spend <span class="count">· last 30d</span></h3>
            @if ($topByAiSpend->isEmpty())
                <p class="mute" style="margin: 0;">No metered AI activity yet.</p>
            @else
                <table class="table">
                    <thead>
                        <tr><th>Workspace</th><th>Plan</th><th class="num">Tokens</th><th class="num">Cost (USD)</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($topByAiSpend as $row)
                            @if ($row->tenant)
                                <tr>
                                    <td><a href="{{ route('superadmin.tenants.show', $row->tenant) }}">{{ $row->tenant->name }}</a></td>
                                    <td><span class="badge plan-{{ $row->tenant->plan }}">{{ $row->tenant->plan }}</span></td>
                                    <td class="num">{{ number_format($row->ai_tokens_30d) }}</td>
                                    <td class="num">${{ number_format($row->ai_cost_usd_30d, 2) }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="card">
            <h3>Top 10 by seat utilisation <span class="count">· closest to plan limit</span></h3>
            @if ($topByUtilisation->isEmpty())
                <p class="mute" style="margin: 0;">No metered tenants yet.</p>
            @else
                <table class="table">
                    <thead>
                        <tr><th>Workspace</th><th>Plan</th><th>Seats</th><th class="num">Utilisation</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($topByUtilisation as $row)
                            @if ($row->tenant)
                                @php
                                    $pct = round(($row->utilisation ?? 0) * 100);
                                    $cls = $pct >= 100 ? 'danger' : ($pct >= 80 ? 'warn' : '');
                                @endphp
                                <tr>
                                    <td><a href="{{ route('superadmin.tenants.show', $row->tenant) }}">{{ $row->tenant->name }}</a></td>
                                    <td><span class="badge plan-{{ $row->tenant->plan }}">{{ $row->tenant->plan }}</span></td>
                                    <td>{{ $row->users_count }} / {{ $row->tenant->plan_seats }}</td>
                                    <td class="num">
                                        <span class="util-bar"><span class="fill {{ $cls }}" style="width: {{ min(100, $pct) }}%"></span></span>
                                        {{ $pct }}%
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- ── Privacy contract reminder (compliance / legal) ─────────────── --}}
    <div class="privacy-note">
        <strong>Privacy contract.</strong>
        This dashboard surfaces aggregates and metadata only — never tenant business data
        (employee records, payroll, leave reasons, AI prompts, files, accounting).
        See <code>app/Support/PlatformAdminVisibility.php</code> for the allowlist.
        HQ does not impersonate tenant users; in-app support sessions require explicit
        tenant consent and are written to the tenant's own audit log.
    </div>
</div>
@endsection
