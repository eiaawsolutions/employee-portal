@extends('layouts.app')

@section('title', 'HQ Ticketing · EIAAW Workforce')
@section('page-title', 'HQ Ticketing')

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

    .hq-tier {
        display: inline-block; padding: 2px 10px; border-radius: 999px;
        font-family: var(--mono); font-size: 10.5px; font-weight: 500;
        text-transform: uppercase; letter-spacing: 0.06em;
    }
    .hq-tier.good   { background: rgba(34,197,94,0.15);  color: #166534; }
    .hq-tier.amber  { background: rgba(245,158,11,0.18); color: #92400e; }
    .hq-tier.poor   { background: rgba(239,68,68,0.16);  color: #b91c1c; }
    .hq-tier.nodata { background: var(--bg-warm);        color: var(--mute); }

    .privacy-note {
        background: var(--bg-warm); border: 1px solid var(--line-soft);
        border-radius: 14px; padding: 14px 18px; margin-top: 28px;
        font-size: 12.5px; color: var(--ink-2); line-height: 1.55;
    }
    .privacy-note strong { color: var(--ink); }
</style>

<div class="hq-wrap">
    <div class="hq-head">
        <h1>EIAAW <em>HQ</em> ticketing</h1>
        <div class="meta">Fleet-wide ticket health · aggregates only · {{ now()->format('d M Y') }}</div>
    </div>

    {{-- ── Top-line ticket stats ──────────────────────────────────────── --}}
    <div class="section-header"><h6>Fleet Snapshot</h6></div>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card dash-widget h-100" style="min-height:auto;">
                <div class="widget-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="widget-icon"><i class="bi bi-ticket-detailed"></i></div>
                        <div>
                            <div class="widget-number">{{ number_format($totals['active']) }}</div>
                            <div class="widget-label">Active Tickets</div>
                        </div>
                    </div>
                </div>
                <div class="widget-body" style="padding:14px 24px 16px;">
                    <div style="font-family:var(--sans);font-size:12px;color:var(--ink-2);">
                        {{ number_format($totals['all_time']) }} all-time · {{ number_format($totals['created_30d']) }} created in 30d
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
                            <div class="widget-number" style="color:{{ $totals['pending'] > 0 ? 'var(--primary-dark)' : 'var(--ink)' }};">{{ number_format($totals['pending']) }}</div>
                            <div class="widget-label">Pending (No PIC 24h+)</div>
                        </div>
                    </div>
                </div>
                <div class="widget-body" style="padding:14px 24px 16px;">
                    <div style="font-family:var(--sans);font-size:12px;color:var(--ink-2);">
                        Auto-set by tickets:remind-stale cron
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
                            <div class="widget-number" style="color:{{ $totals['idle_7d_plus'] > 0 ? 'var(--danger)' : 'var(--ink)' }};">{{ number_format($totals['idle_7d_plus']) }}</div>
                            <div class="widget-label">Idle 7 Days+</div>
                        </div>
                    </div>
                </div>
                <div class="widget-body" style="padding:14px 24px 16px;">
                    <div style="font-family:var(--sans);font-size:12px;color:var(--ink-2);">
                        Escalation risk — active tickets with no activity
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card dash-widget h-100" style="min-height:auto;">
                <div class="widget-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="widget-icon"><i class="bi bi-check2-circle"></i></div>
                        <div>
                            <div class="widget-number">{{ number_format($totals['resolved_30d']) }}</div>
                            <div class="widget-label">Resolved · Last 30d</div>
                        </div>
                    </div>
                </div>
                <div class="widget-body" style="padding:14px 24px 16px;">
                    <div style="font-family:var(--sans);font-size:12px;color:var(--ink-2);">
                        {{ number_format($totals['closed']) }} closed all-time
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── By priority + by department ────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        <div class="col-md-5">
            <div class="card" style="border-radius:14px;border:1px solid var(--line-soft);">
                <div class="card-body">
                    <h6 style="font-family:var(--mono);font-size:11px;text-transform:uppercase;letter-spacing:0.12em;color:var(--mute);margin-bottom:14px;">
                        Active Tickets by Priority
                    </h6>
                    <table class="hq-table">
                        <thead>
                            <tr><th>Priority</th><th class="num">Count</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($byPriorityOrdered as $priority => $count)
                                <tr>
                                    <td>{{ $priority }}</td>
                                    <td class="num">{{ number_format($count) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card" style="border-radius:14px;border:1px solid var(--line-soft);">
                <div class="card-body">
                    <h6 style="font-family:var(--mono);font-size:11px;text-transform:uppercase;letter-spacing:0.12em;color:var(--mute);margin-bottom:14px;">
                        Active Tickets by Department
                    </h6>
                    @if (empty($byDepartment))
                        <div class="mute">No active tickets across any department.</div>
                    @else
                        <table class="hq-table">
                            <thead>
                                <tr><th>Department</th><th class="num">Count</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($byDepartment as $dept => $count)
                                    <tr>
                                        <td>{{ $dept }}</td>
                                        <td class="num">{{ number_format($count) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Per-tenant ticket health ───────────────────────────────────── --}}
    <div class="section-header"><h6>Per-Tenant Ticket Health</h6></div>
    <div class="card" style="border-radius:14px;border:1px solid var(--line-soft);overflow:hidden;">
        <div class="card-body p-0">
            @if ($perTenant->isEmpty())
                <div class="p-4 text-center mute">No tenants found.</div>
            @else
                <div class="table-responsive">
                    <table class="hq-table" style="min-width:920px;">
                        <thead>
                            <tr>
                                <th>Workspace</th>
                                <th>Plan</th>
                                <th class="num">All-Time</th>
                                <th class="num">Active</th>
                                <th class="num">Pending</th>
                                <th class="num">Idle 7d+</th>
                                <th class="num">Created 30d</th>
                                <th class="num">Resolved 30d</th>
                                <th class="num">Avg Resolution 30d</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($perTenant as $row)
                                <tr>
                                    <td>
                                        <a href="{{ route('superadmin.tenants.show', $row['tenant_id']) }}">
                                            <strong>{{ $row['tenant_name'] }}</strong>
                                        </a>
                                        <div class="mute">{{ $row['tenant_slug'] }}</div>
                                    </td>
                                    <td>
                                        <span class="hq-badge plan-{{ $row['tenant_plan'] }}">{{ $row['tenant_plan'] }}</span>
                                    </td>
                                    <td class="num">{{ number_format($row['total_all_time']) }}</td>
                                    <td class="num">{{ number_format($row['total_active']) }}</td>
                                    <td class="num" style="color:{{ $row['total_pending'] > 0 ? 'var(--primary-dark)' : 'inherit' }};">
                                        {{ number_format($row['total_pending']) }}
                                    </td>
                                    <td class="num" style="color:{{ $row['total_idle_7d'] > 0 ? 'var(--danger)' : 'inherit' }};">
                                        {{ number_format($row['total_idle_7d']) }}
                                    </td>
                                    <td class="num">{{ number_format($row['created_30d']) }}</td>
                                    <td class="num">{{ number_format($row['resolved_30d']) }}</td>
                                    <td class="num">
                                        @if ($row['avg_resolution_formatted'] !== null)
                                            <span class="hq-tier {{ $row['avg_resolution_tier'] }}">
                                                {{ $row['avg_resolution_formatted'] }}
                                            </span>
                                        @else
                                            <span class="hq-tier nodata">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Privacy disclosure ─────────────────────────────────────────── --}}
    <div class="privacy-note">
        <strong>Privacy contract:</strong> this page shows ticket <em>counts</em> only. EIAAW HQ
        does not read ticket subjects, descriptions, chat messages, attachments, or names of
        creators/PICs. Tenant business data stays inside the tenant. See
        <code>app/Support/PlatformAdminVisibility.php</code> for the full allowlist.
    </div>
</div>
@endsection
