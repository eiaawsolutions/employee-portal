@extends('layouts.app')
@section('title', 'Executive Dashboard')
@section('page-title', 'Executive Dashboard')

@push('styles')
<style>
    .mini-table th { font-family: var(--mono); font-size: 10.5px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.12em; color: var(--mute); border-top: none; }
    .mini-table td { font-size: 13px; color: var(--ink-2); }
</style>
@endpush

@section('content')
@include('reports.partials.report-header')
@include('partials.dashboard-widgets-style')

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- ROW 1: TOP-LINE KPIs --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="section-header">
    <h6>Key Performance Indicators &mdash; {{ $year }}</h6>
</div>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <div class="widget-number">{{ number_format($totalActive) }}</div>
                        <div class="widget-label">Active Employees</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-person-plus-fill"></i></div>
                    <div>
                        <div class="widget-number">{{ number_format($totalNewHires) }}</div>
                        <div class="widget-label">New Hires YTD</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-person-dash-fill"></i></div>
                    <div>
                        <div class="widget-number">{{ number_format($totalExits) }}</div>
                        <div class="widget-label">Exits YTD</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-arrow-repeat"></i></div>
                    <div>
                        <div class="widget-number">{{ $turnoverRate }}%</div>
                        <div class="widget-label">Turnover Rate</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-cash-stack"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:20px;">RM {{ number_format($payrollStats->gross ?? 0, 0) }}</div>
                        <div class="widget-label">YTD Gross Payroll</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-wallet2"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:20px;">RM {{ number_format($avgSalary ?? 0, 0) }}</div>
                        <div class="widget-label">Average Salary</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- ROW 2: Secondary KPIs --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-calendar-check-fill"></i></div>
                    <div>
                        <div class="widget-number">{{ $attendanceRate }}%</div>
                        <div class="widget-label">Attendance Rate</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-calendar2-week-fill"></i></div>
                    <div>
                        <div class="widget-number">{{ number_format($leaveStats->total_days_taken ?? 0, 0) }}</div>
                        <div class="widget-label">Leave Days YTD</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-laptop-fill"></i></div>
                    <div>
                        <div class="widget-number">{{ number_format($assetStats['total']) }}</div>
                        <div class="widget-label">Total Assets</div>
                        <div class="status-pills" style="margin-top:6px;">
                            <span class="status-pill">{{ $assetStats['available'] }} avail</span>
                            <span class="status-pill">{{ $assetStats['assigned'] }} in use</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-receipt-cutoff"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:20px;">RM {{ number_format($claimsStats->approved_amount ?? 0, 0) }}</div>
                        <div class="widget-label">Claims Approved YTD</div>
                        @if(($claimsStats->pending_amount ?? 0) > 0)
                            <div class="status-pills" style="margin-top:6px;"><span class="status-pill">RM {{ number_format($claimsStats->pending_amount, 0) }} pending</span></div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- ROW 3: CHARTS --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}

<div class="section-header"><h6>Headcount &amp; Employment</h6></div>
<div class="row g-3 mb-4">
    {{-- Headcount Trend (Hires vs Exits) --}}
    <div class="col-lg-8">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-graph-up"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:18px;">Headcount Movement</div>
                        <div class="widget-label">{{ $year }}</div>
                    </div>
                </div>
            </div>
            <div class="widget-body"><canvas id="headcountChart" height="260"></canvas></div>
        </div>
    </div>
    {{-- Employment Type --}}
    <div class="col-lg-4">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-pie-chart"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:18px;">Employment Type</div>
                        <div class="widget-label">Distribution</div>
                    </div>
                </div>
            </div>
            <div class="widget-body d-flex align-items-center justify-content-center"><canvas id="empTypeChart" height="220"></canvas></div>
        </div>
    </div>
</div>

<div class="section-header"><h6>Payroll &amp; Demographics</h6></div>
<div class="row g-3 mb-4">
    {{-- Payroll Trend --}}
    <div class="col-lg-8">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-bar-chart"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:18px;">Monthly Gross Payroll</div>
                        <div class="widget-label">{{ $year }}</div>
                    </div>
                </div>
            </div>
            <div class="widget-body"><canvas id="payrollChart" height="260"></canvas></div>
        </div>
    </div>
    {{-- Gender Distribution --}}
    <div class="col-lg-4">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-gender-ambiguous"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:18px;">Gender Distribution</div>
                        <div class="widget-label">Workforce</div>
                    </div>
                </div>
            </div>
            <div class="widget-body d-flex align-items-center justify-content-center"><canvas id="genderChart" height="220"></canvas></div>
        </div>
    </div>
</div>

<div class="section-header"><h6>Department &amp; Tenure</h6></div>
<div class="row g-3 mb-4">
    {{-- Department Distribution --}}
    <div class="col-lg-6">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-building"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:18px;">Headcount by Department</div>
                        <div class="widget-label">Active employees</div>
                    </div>
                </div>
            </div>
            <div class="widget-body"><canvas id="deptChart" height="280"></canvas></div>
        </div>
    </div>
    {{-- Tenure Distribution --}}
    <div class="col-lg-6">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:18px;">Tenure Distribution</div>
                        <div class="widget-label">Years of service</div>
                    </div>
                </div>
            </div>
            <div class="widget-body"><canvas id="tenureChart" height="280"></canvas></div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- ROW 4: Company Distribution + Leave by Type + Claims by Category --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="section-header"><h6>Company &amp; Activity Breakdown</h6></div>
<div class="row g-3 mb-4">
    {{-- Company Distribution --}}
    <div class="col-lg-4">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-buildings"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:18px;">Headcount by Company</div>
                        <div class="widget-label">Active distribution</div>
                    </div>
                </div>
            </div>
            <div class="widget-body">
                <table class="table table-sm mini-table mb-0">
                    <thead><tr><th>Company</th><th class="text-end">Count</th><th class="text-end">%</th></tr></thead>
                    <tbody>
                    @foreach($companyDistribution as $row)
                    <tr>
                        <td>{{ $row->label }}</td>
                        <td class="text-end fw-semibold">{{ $row->total }}</td>
                        <td class="text-end">{{ $totalActive > 0 ? round($row->total / $totalActive * 100, 1) : 0 }}%</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    {{-- Leave by Type --}}
    <div class="col-lg-4">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-calendar-x"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:18px;">Leave Taken by Type</div>
                        <div class="widget-label">{{ $year }}</div>
                    </div>
                </div>
            </div>
            <div class="widget-body">
                @if(($leaveByType ?? collect())->isEmpty())
                    <div class="text-center py-4">
                        <div style="width:44px;height:44px;background:var(--primary-tint);border:1px solid rgba(17,118,106,0.14);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                            <i class="bi bi-calendar-x" style="font-size:18px;color:var(--primary-dark);"></i>
                        </div>
                        <div style="font-family:var(--sans);font-size:13px;color:var(--mute);">No leave data</div>
                    </div>
                @else
                    <table class="table table-sm mini-table mb-0">
                        <thead><tr><th>Type</th><th class="text-end">Days</th><th class="text-end">Count</th></tr></thead>
                        <tbody>
                        @foreach($leaveByType as $row)
                        <tr>
                            <td>{{ $row->type_name }}</td>
                            <td class="text-end fw-semibold">{{ number_format($row->total_days, 1) }}</td>
                            <td class="text-end">{{ $row->count }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
    {{-- Claims by Category --}}
    <div class="col-lg-4">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-receipt"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:18px;">Claims by Category</div>
                        <div class="widget-label">Approved YTD</div>
                    </div>
                </div>
            </div>
            <div class="widget-body">
                @if(($claimsByCategory ?? collect())->isEmpty())
                    <div class="text-center py-4">
                        <div style="width:44px;height:44px;background:var(--primary-tint);border:1px solid rgba(17,118,106,0.14);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                            <i class="bi bi-receipt" style="font-size:18px;color:var(--primary-dark);"></i>
                        </div>
                        <div style="font-family:var(--sans);font-size:13px;color:var(--mute);">No claim data</div>
                    </div>
                @else
                    <table class="table table-sm mini-table mb-0">
                        <thead><tr><th>Category</th><th class="text-end">Amount (RM)</th></tr></thead>
                        <tbody>
                        @foreach($claimsByCategory as $row)
                        <tr>
                            <td>{{ $row->category }}</td>
                            <td class="text-end fw-semibold">{{ number_format($row->total, 2) }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- ROW 5: Statutory + Onboarding Pipeline + Asset Summary --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="section-header"><h6>Statutory, Pipeline &amp; Assets</h6></div>
<div class="row g-3 mb-4 align-items-start">
    {{-- Statutory Contributions --}}
    <div class="col-lg-4">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-bank"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:18px;">Statutory Contributions</div>
                        <div class="widget-label">YTD</div>
                    </div>
                </div>
            </div>
            <div class="widget-body">
                @php $st = $statutoryTotals; @endphp
                <table class="table table-sm mini-table mb-0">
                    <thead><tr><th>Contribution</th><th class="text-end">Employee (RM)</th><th class="text-end">Employer (RM)</th></tr></thead>
                    <tbody>
                    <tr><td>EPF</td><td class="text-end">{{ number_format($st->epf_ee ?? 0, 2) }}</td><td class="text-end">{{ number_format($st->epf_er ?? 0, 2) }}</td></tr>
                    <tr><td>SOCSO</td><td class="text-end">{{ number_format($st->socso_ee ?? 0, 2) }}</td><td class="text-end">{{ number_format($st->socso_er ?? 0, 2) }}</td></tr>
                    <tr><td>EIS</td><td class="text-end">{{ number_format($st->eis_ee ?? 0, 2) }}</td><td class="text-end">{{ number_format($st->eis_er ?? 0, 2) }}</td></tr>
                    <tr><td>PCB (Tax)</td><td class="text-end">{{ number_format($st->pcb ?? 0, 2) }}</td><td class="text-end">—</td></tr>
                    <tr><td>HRDF</td><td class="text-end">—</td><td class="text-end">{{ number_format($st->hrdf ?? 0, 2) }}</td></tr>
                    </tbody>
                    <tfoot>
                    <tr class="fw-bold">
                        <td>Total</td>
                        <td class="text-end">{{ number_format(($st->epf_ee ?? 0)+($st->socso_ee ?? 0)+($st->eis_ee ?? 0)+($st->pcb ?? 0), 2) }}</td>
                        <td class="text-end">{{ number_format(($st->epf_er ?? 0)+($st->socso_er ?? 0)+($st->eis_er ?? 0)+($st->hrdf ?? 0), 2) }}</td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    {{-- Onboarding Pipeline --}}
    <div class="col-lg-4">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-funnel"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:18px;">Onboarding Pipeline</div>
                        <div class="widget-label">Current state</div>
                    </div>
                </div>
            </div>
            <div class="widget-body">
                <div class="d-flex flex-column gap-3">
                    <div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-semibold">Pending</span>
                            <span class="breakdown-badge">{{ $pipelineStats['pending'] }}</span>
                        </div>
                        <div class="progress" style="height:8px;background:var(--bg-warm);">
                            <div class="progress-bar" style="width:{{ max(($pipelineStats['pending']/max(array_sum($pipelineStats),1))*100,2) }}%;background:var(--warn);"></div>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-semibold">Active (In Progress)</span>
                            <span class="breakdown-badge">{{ $pipelineStats['active'] }}</span>
                        </div>
                        <div class="progress" style="height:8px;background:var(--bg-warm);">
                            <div class="progress-bar" style="width:{{ max(($pipelineStats['active']/max(array_sum($pipelineStats),1))*100,2) }}%;background:var(--primary);"></div>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-semibold">Completed ({{ $year }})</span>
                            <span class="breakdown-badge">{{ $pipelineStats['completed'] }}</span>
                        </div>
                        <div class="progress" style="height:8px;background:var(--bg-warm);">
                            <div class="progress-bar" style="width:{{ max(($pipelineStats['completed']/max(array_sum($pipelineStats),1))*100,2) }}%;background:var(--success);"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Asset Summary --}}
    <div class="col-lg-4">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-laptop"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:18px;">Asset Portfolio</div>
                        <div class="widget-label">By status</div>
                    </div>
                </div>
            </div>
            <div class="widget-body">
                <div style="position:relative;height:220px;">
                    <canvas id="assetStatusChart"></canvas>
                </div>
                <div class="d-flex justify-content-between mt-3 small" style="color:var(--ink-2);">
                    <div><span style="color:var(--mute);">Total Value:</span> <strong>RM {{ number_format($assetCostTotal, 0) }}</strong></div>
                    <div><span style="color:var(--mute);">Monthly Rental:</span> <strong>RM {{ number_format($rentalCostMonthly, 0) }}</strong></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    const COLORS = ['#2563eb','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#f97316','#14b8a6','#6366f1'];
    Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
    Chart.defaults.font.size = 11;

    // ── Headcount Trend ─────────────────────────────────
    const hcData = @json($headcountTrend);
    new Chart(document.getElementById('headcountChart'), {
        type: 'bar',
        data: {
            labels: hcData.map(d => d.month),
            datasets: [
                { label: 'New Hires', data: hcData.map(d => d.hires), backgroundColor: '#10b981', borderRadius: 4 },
                { label: 'Exits', data: hcData.map(d => d.exits), backgroundColor: '#ef4444', borderRadius: 4 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });

    // ── Employment Type Doughnut ────────────────────────
    const etData = @json($empTypeBreakdown);
    new Chart(document.getElementById('empTypeChart'), {
        type: 'doughnut',
        data: {
            labels: etData.map(d => d.etype.charAt(0).toUpperCase() + d.etype.slice(1)),
            datasets: [{ data: etData.map(d => d.total), backgroundColor: COLORS, borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '55%', plugins: { legend: { position: 'bottom', labels: { padding: 12 } } } }
    });

    // ── Payroll Trend ───────────────────────────────────
    const prData = @json($payrollTrend);
    new Chart(document.getElementById('payrollChart'), {
        type: 'bar',
        data: {
            labels: prData.map(d => d.month),
            datasets: [{
                label: 'Gross Payroll (RM)',
                data: prData.map(d => d.amount),
                backgroundColor: '#8b5cf6',
                borderRadius: 4
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => 'RM ' + v.toLocaleString() } } } }
    });

    // ── Gender Doughnut ─────────────────────────────────
    const gdData = @json($genderDistribution);
    new Chart(document.getElementById('genderChart'), {
        type: 'doughnut',
        data: {
            labels: gdData.map(d => d.gender.charAt(0).toUpperCase() + d.gender.slice(1)),
            datasets: [{ data: gdData.map(d => d.total), backgroundColor: ['#2563eb','#ec4899','#94a3b8'], borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '55%', plugins: { legend: { position: 'bottom', labels: { padding: 12 } } } }
    });

    // ── Department Bar ──────────────────────────────────
    const dpData = @json($deptDistribution);
    new Chart(document.getElementById('deptChart'), {
        type: 'bar',
        data: {
            labels: dpData.map(d => d.dept),
            datasets: [{ label: 'Headcount', data: dpData.map(d => d.total), backgroundColor: '#06b6d4', borderRadius: 4 }]
        },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });

    // ── Tenure Bar ──────────────────────────────────────
    const tnData = @json($tenureBuckets);
    new Chart(document.getElementById('tenureChart'), {
        type: 'bar',
        data: {
            labels: Object.keys(tnData),
            datasets: [{ label: 'Employees', data: Object.values(tnData), backgroundColor: ['#dbeafe','#93c5fd','#60a5fa','#3b82f6','#1d4ed8'], borderRadius: 4 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });

    // ── Asset Status Doughnut ───────────────────────────
    const asData = @json($assetStats);
    new Chart(document.getElementById('assetStatusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Available','Assigned','Maintenance','Disposed'],
            datasets: [{ data: [asData.available, asData.assigned, asData.maintenance, asData.disposed], backgroundColor: ['#10b981','#2563eb','#f59e0b','#94a3b8'], borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '50%', plugins: { legend: { position: 'bottom', labels: { padding: 8, font: { size: 10 } } } } }
    });
});
</script>
@endsection
