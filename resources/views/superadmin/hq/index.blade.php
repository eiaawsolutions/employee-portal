@extends('layouts.app')

@section('title', 'HQ Overview · EIAAW Workforce')
@section('page-title', 'HQ Overview')

@section('content')
@include('partials.dashboard-widgets-style')
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

    .hq-table { width: 100%; border-collapse: collapse; }
    .hq-table th {
        text-align: left; padding: 8px 10px;
        font-family: var(--mono); font-size: 10.5px; font-weight: 500;
        text-transform: uppercase; letter-spacing: 0.12em; color: var(--mute);
        border-bottom: 1px solid var(--line-soft);
    }
    .hq-table td {
        padding: 10px; border-bottom: 1px solid var(--line-soft);
        font-size: 13.5px; color: var(--ink);
    }
    .hq-table tr:last-child td { border-bottom: 0; }
    .hq-table tr a { color: var(--ink); text-decoration: none; }
    .hq-table tr a:hover { color: var(--primary-dark); }
    .hq-table .num { text-align: right; font-variant-numeric: tabular-nums; }
    .hq-table .mute { color: var(--mute); font-size: 12px; }

    .hq-badge {
        display: inline-block; padding: 2px 8px; border-radius: 999px;
        font-family: var(--sans); font-size: 10.5px; font-weight: 500;
        text-transform: capitalize;
    }
    .hq-badge.plan-starter    { background: rgba(107,122,127,0.10); color: var(--ink-2); }
    .hq-badge.plan-growth     { background: rgba(31,168,150,0.12); color: var(--primary-dark); }
    .hq-badge.plan-scale      { background: rgba(17,118,106,0.18); color: var(--primary-dark); }
    .hq-badge.plan-enterprise { background: var(--ink); color: var(--bg); }

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
        border-radius: 14px; padding: 14px 18px; margin-top: 28px;
        font-size: 12.5px; color: var(--ink-2); line-height: 1.55;
    }
    .privacy-note strong { color: var(--ink); }

    .empty-meter {
        background: var(--primary-tint); border: 1px solid rgba(17,118,106,0.22);
        border-radius: 14px; padding: 12px 16px; margin-bottom: 18px;
        font-size: 13px; color: var(--primary-dark);
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
    <div class="section-header"><h6>Fleet Snapshot</h6></div>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card dash-widget h-100" style="min-height:auto;">
                <div class="widget-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="widget-icon"><i class="bi bi-buildings"></i></div>
                        <div>
                            <div class="widget-number">{{ number_format($totals['active']) }}</div>
                            <div class="widget-label">Active Workspaces</div>
                        </div>
                    </div>
                </div>
                <div class="widget-body" style="padding:14px 24px 16px;">
                    <div style="font-family:var(--sans);font-size:12px;color:var(--ink-2);">
                        {{ number_format($totals['all_time']) }} all-time · {{ number_format($totals['signups_30d']) }} new in 30d
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card dash-widget h-100" style="min-height:auto;">
                <div class="widget-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="widget-icon"><i class="bi bi-cash-coin"></i></div>
                        <div>
                            <div class="widget-number">${{ number_format($mrr['total_known'], 0) }}</div>
                            <div class="widget-label">Estimated MRR</div>
                        </div>
                    </div>
                </div>
                <div class="widget-body" style="padding:14px 24px 16px;">
                    <div style="font-family:var(--sans);font-size:12px;color:var(--ink-2);">
                        @if ($mrr['enterprise_unknown'] > 0)
                            + {{ $mrr['enterprise_unknown'] }} enterprise on custom pricing
                        @else
                            excludes trial &amp; suspended
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card dash-widget h-100" style="min-height:auto;">
                <div class="widget-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="widget-icon"><i class="bi bi-exclamation-triangle"></i></div>
                        <div>
                            <div class="widget-number" style="color:{{ $totals['past_due'] > 0 ? 'var(--danger)' : 'var(--ink)' }};">{{ number_format($totals['past_due']) }}</div>
                            <div class="widget-label">Past Due</div>
                        </div>
                    </div>
                </div>
                <div class="widget-body" style="padding:14px 24px 16px;">
                    <div style="font-family:var(--sans);font-size:12px;color:var(--ink-2);">
                        {{ number_format($totals['suspended']) }} suspended · {{ number_format($totals['canceled']) }} canceled
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card dash-widget h-100" style="min-height:auto;">
                <div class="widget-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="widget-icon"><i class="bi bi-hourglass-split"></i></div>
                        <div>
                            <div class="widget-number" style="color:{{ $totals['trial_ends_7d'] > 0 ? 'var(--primary-dark)' : 'var(--ink)' }};">{{ number_format($totals['trial_ends_7d']) }}</div>
                            <div class="widget-label">Trials Ending in 7 Days</div>
                        </div>
                    </div>
                </div>
                <div class="widget-body" style="padding:14px 24px 16px;">
                    <div style="font-family:var(--sans);font-size:12px;color:var(--ink-2);">
                        {{ number_format($totals['in_trial']) }} on trial total
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Fleet usage rollup ─────────────────────────────────────────── --}}
    <div class="section-header"><h6>Usage Rollup</h6></div>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card dash-widget h-100" style="min-height:auto;">
                <div class="widget-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="widget-icon"><i class="bi bi-cpu"></i></div>
                        <div>
                            <div class="widget-number" style="font-size:24px;">${{ number_format($fleetAiCost30d, 2) }}</div>
                            <div class="widget-label">AI Spend · Last 30d</div>
                        </div>
                    </div>
                </div>
                <div class="widget-body" style="padding:14px 24px 16px;">
                    <div style="font-family:var(--sans);font-size:12px;color:var(--ink-2);">
                        {{ number_format($fleetAiTokens30d) }} tokens
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card dash-widget h-100" style="min-height:auto;">
                <div class="widget-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="widget-icon"><i class="bi bi-hdd-stack"></i></div>
                        <div>
                            <div class="widget-number">{{ number_format($fleetStorageMb) }}<span style="font-size:14px;color:var(--mute);font-weight:400;margin-left:4px;">MB</span></div>
                            <div class="widget-label">Storage · Fleet</div>
                        </div>
                    </div>
                </div>
                <div class="widget-body" style="padding:14px 24px 16px;">
                    <div style="font-family:var(--sans);font-size:12px;color:var(--ink-2);">
                        private + public buckets
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card dash-widget h-100" style="min-height:auto;">
                <div class="widget-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="widget-icon"><i class="bi bi-stack"></i></div>
                        <div>
                            <div class="widget-number" style="font-size:14px;line-height:1.5;">
                                @foreach (['starter','growth','scale','enterprise'] as $p)
                                    <span class="hq-badge plan-{{ $p }}">{{ ucfirst($p) }} {{ $byPlan[$p] ?? 0 }}</span>
                                @endforeach
                            </div>
                            <div class="widget-label">Plans · Live</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            @php
                $totalSeats   = \App\Models\Tenant::where('status', \App\Models\Tenant::STATUS_ACTIVE)->sum('plan_seats');
                $totalUsed    = \App\Models\TenantUsageDaily::query()
                    ->whereIn('id', function ($q) { $q->select(\DB::raw('MAX(id)'))->from('tenant_usage_daily')->groupBy('tenant_id'); })
                    ->sum('users_count');
                $loadPct = $totalSeats > 0 ? round(($totalUsed / $totalSeats) * 100, 1) : 0;
            @endphp
            <div class="card dash-widget h-100" style="min-height:auto;">
                <div class="widget-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="widget-icon"><i class="bi bi-speedometer2"></i></div>
                        <div>
                            <div class="widget-number">{{ $loadPct }}<span style="font-size:14px;color:var(--mute);font-weight:400;margin-left:4px;">%</span></div>
                            <div class="widget-label">Capacity Load</div>
                        </div>
                    </div>
                </div>
                <div class="widget-body" style="padding:14px 24px 16px;">
                    <div style="font-family:var(--sans);font-size:12px;color:var(--ink-2);">
                        {{ number_format($totalUsed) }} / {{ number_format($totalSeats) }} seats provisioned
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Top tables ─────────────────────────────────────────────────── --}}
    <div class="section-header"><h6>Top Tenants</h6></div>
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card dash-widget h-100" style="min-height:auto;">
                <div class="widget-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="widget-icon"><i class="bi bi-cpu-fill"></i></div>
                        <div>
                            <div class="widget-number">{{ $topByAiSpend->count() }}</div>
                            <div class="widget-label">Top by AI Spend · Last 30d</div>
                        </div>
                    </div>
                </div>
                <div class="widget-body">
                    @if ($topByAiSpend->isEmpty())
                        <div class="text-center py-4">
                            <div style="width:44px;height:44px;background:var(--primary-tint);border:1px solid rgba(17,118,106,0.14);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                                <i class="bi bi-cpu" style="font-size:18px;color:var(--primary-dark);"></i>
                            </div>
                            <div style="font-family:var(--sans);font-size:13px;color:var(--mute);">No metered AI activity yet.</div>
                        </div>
                    @else
                        <table class="hq-table">
                            <thead>
                                <tr><th>Workspace</th><th>Plan</th><th class="num">Tokens</th><th class="num">Cost (USD)</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($topByAiSpend as $row)
                                    @if ($row->tenant)
                                        <tr>
                                            <td><a href="{{ route('superadmin.tenants.show', $row->tenant) }}">{{ $row->tenant->name }}</a></td>
                                            <td><span class="hq-badge plan-{{ $row->tenant->plan }}">{{ $row->tenant->plan }}</span></td>
                                            <td class="num">{{ number_format($row->ai_tokens_30d) }}</td>
                                            <td class="num">${{ number_format($row->ai_cost_usd_30d, 2) }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card dash-widget h-100" style="min-height:auto;">
                <div class="widget-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="widget-icon"><i class="bi bi-people-fill"></i></div>
                        <div>
                            <div class="widget-number">{{ $topByUtilisation->count() }}</div>
                            <div class="widget-label">Top by Seat Utilisation · Closest to Limit</div>
                        </div>
                    </div>
                </div>
                <div class="widget-body">
                    @if ($topByUtilisation->isEmpty())
                        <div class="text-center py-4">
                            <div style="width:44px;height:44px;background:var(--primary-tint);border:1px solid rgba(17,118,106,0.14);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                                <i class="bi bi-people" style="font-size:18px;color:var(--primary-dark);"></i>
                            </div>
                            <div style="font-family:var(--sans);font-size:13px;color:var(--mute);">No metered tenants yet.</div>
                        </div>
                    @else
                        <table class="hq-table">
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
                                            <td><span class="hq-badge plan-{{ $row->tenant->plan }}">{{ $row->tenant->plan }}</span></td>
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
