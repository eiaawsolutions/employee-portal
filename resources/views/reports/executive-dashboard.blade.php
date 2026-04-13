@extends('layouts.app')
@section('title', 'Executive Dashboard')
@section('page-title', 'Executive Dashboard')

@push('styles')
<style>
    .chart-card { border: 1px solid #e2e8f0; border-radius: 12px; }
    .chart-card .card-header { background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-weight: 600; font-size: 13px; }
    .mini-table th { font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600; border-top: none; }
    .mini-table td { font-size: 13px; }
    .dash-widget .status-pills { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px; }
    .dash-widget .status-pill { font-size: 10px; font-weight: 600; padding: 3px 10px; border-radius: 20px; background: rgba(255,255,255,.2); color: #fff; }
</style>
@endpush

@section('content')
@include('reports.partials.report-header')
@include('partials.dashboard-widgets-style')

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- ROW 1: TOP-LINE KPIs --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="section-header">
    <div class="section-icon" style="background:#eff6ff;">
        <i class="bi bi-speedometer2" style="font-size:16px;color:#2563eb;"></i>
    </div>
    <h6>Key Performance Indicators &mdash; {{ $year }}</h6>
</div>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);padding:16px 18px 12px;">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-people-fill" style="font-size:18px;"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:26px;">{{ number_format($totalActive) }}</div>
                        <div class="widget-label">Active Employees</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header" style="background:linear-gradient(135deg,#22c55e,#15803d);padding:16px 18px 12px;">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-person-plus-fill" style="font-size:18px;"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:26px;">{{ number_format($totalNewHires) }}</div>
                        <div class="widget-label">New Hires YTD</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header" style="background:linear-gradient(135deg,#ef4444,#b91c1c);padding:16px 18px 12px;">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-person-dash-fill" style="font-size:18px;"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:26px;">{{ number_format($totalExits) }}</div>
                        <div class="widget-label">Exits YTD</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header" style="background:linear-gradient(135deg,#f59e0b,#d97706);padding:16px 18px 12px;">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-arrow-repeat" style="font-size:18px;"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:26px;">{{ $turnoverRate }}%</div>
                        <div class="widget-label">Turnover Rate</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);padding:16px 18px 12px;">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-cash-stack" style="font-size:18px;"></i></div>
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
            <div class="widget-header" style="background:linear-gradient(135deg,#06b6d4,#0891b2);padding:16px 18px 12px;">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-wallet2" style="font-size:18px;"></i></div>
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
            <div class="widget-header" style="background:linear-gradient(135deg,#14b8a6,#0f766e);padding:16px 18px 12px;">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-calendar-check-fill" style="font-size:18px;"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:26px;">{{ $attendanceRate }}%</div>
                        <div class="widget-label">Attendance Rate</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header" style="background:linear-gradient(135deg,#ec4899,#be185d);padding:16px 18px 12px;">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-calendar2-week-fill" style="font-size:18px;"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:26px;">{{ number_format($leaveStats->total_days_taken ?? 0, 0) }}</div>
                        <div class="widget-label">Leave Days YTD</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header" style="background:linear-gradient(135deg,#f97316,#c2410c);padding:16px 18px 12px;">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-laptop-fill" style="font-size:18px;"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:26px;">{{ number_format($assetStats['total']) }}</div>
                        <div class="widget-label">Total Assets</div>
                        <div class="status-pills">
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
            <div class="widget-header" style="background:linear-gradient(135deg,#a855f7,#7c3aed);padding:16px 18px 12px;">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-receipt-cutoff" style="font-size:18px;"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:20px;">RM {{ number_format($claimsStats->approved_amount ?? 0, 0) }}</div>
                        <div class="widget-label">Claims Approved YTD</div>
                        @if(($claimsStats->pending_amount ?? 0) > 0)
                        <div class="status-pills"><span class="status-pill">RM {{ number_format($claimsStats->pending_amount, 0) }} pending</span></div>
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

<div class="row g-3 mb-4">
    {{-- Headcount Trend (Hires vs Exits) --}}
    <div class="col-lg-8">
        <div class="card chart-card h-100">
            <div class="card-header py-2"><i class="bi bi-graph-up me-1"></i>Headcount Movement — {{ $year }}</div>
            <div class="card-body"><canvas id="headcountChart" height="260"></canvas></div>
        </div>
    </div>
    {{-- Employment Type --}}
    <div class="col-lg-4">
        <div class="card chart-card h-100">
            <div class="card-header py-2"><i class="bi bi-pie-chart me-1"></i>Employment Type</div>
            <div class="card-body d-flex align-items-center justify-content-center"><canvas id="empTypeChart" height="220"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    {{-- Payroll Trend --}}
    <div class="col-lg-8">
        <div class="card chart-card h-100">
            <div class="card-header py-2"><i class="bi bi-bar-chart me-1"></i>Monthly Gross Payroll — {{ $year }}</div>
            <div class="card-body"><canvas id="payrollChart" height="260"></canvas></div>
        </div>
    </div>
    {{-- Gender Distribution --}}
    <div class="col-lg-4">
        <div class="card chart-card h-100">
            <div class="card-header py-2"><i class="bi bi-gender-ambiguous me-1"></i>Gender Distribution</div>
            <div class="card-body d-flex align-items-center justify-content-center"><canvas id="genderChart" height="220"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    {{-- Department Distribution --}}
    <div class="col-lg-6">
        <div class="card chart-card h-100">
            <div class="card-header py-2"><i class="bi bi-building me-1"></i>Headcount by Department</div>
            <div class="card-body"><canvas id="deptChart" height="280"></canvas></div>
        </div>
    </div>
    {{-- Tenure Distribution --}}
    <div class="col-lg-6">
        <div class="card chart-card h-100">
            <div class="card-header py-2"><i class="bi bi-hourglass-split me-1"></i>Tenure Distribution</div>
            <div class="card-body"><canvas id="tenureChart" height="280"></canvas></div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- ROW 4: Company Distribution + Leave by Type + Claims by Category --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-4">
    {{-- Company Distribution --}}
    <div class="col-lg-4">
        <div class="card chart-card h-100">
            <div class="card-header py-2"><i class="bi bi-buildings me-1"></i>Headcount by Company</div>
            <div class="card-body">
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
        <div class="card chart-card h-100">
            <div class="card-header py-2"><i class="bi bi-calendar-x me-1"></i>Leave Taken by Type</div>
            <div class="card-body">
                <table class="table table-sm mini-table mb-0">
                    <thead><tr><th>Type</th><th class="text-end">Days</th><th class="text-end">Count</th></tr></thead>
                    <tbody>
                    @forelse($leaveByType as $row)
                    <tr>
                        <td>{{ $row->type_name }}</td>
                        <td class="text-end fw-semibold">{{ number_format($row->total_days, 1) }}</td>
                        <td class="text-end">{{ $row->count }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-muted text-center">No data</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    {{-- Claims by Category --}}
    <div class="col-lg-4">
        <div class="card chart-card h-100">
            <div class="card-header py-2"><i class="bi bi-receipt me-1"></i>Claims by Category</div>
            <div class="card-body">
                <table class="table table-sm mini-table mb-0">
                    <thead><tr><th>Category</th><th class="text-end">Amount (RM)</th></tr></thead>
                    <tbody>
                    @forelse($claimsByCategory as $row)
                    <tr>
                        <td>{{ $row->category }}</td>
                        <td class="text-end fw-semibold">{{ number_format($row->total, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="text-muted text-center">No data</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- ROW 5: Statutory + Onboarding Pipeline + Asset Summary --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-4 align-items-start">
    {{-- Statutory Contributions --}}
    <div class="col-lg-4">
        <div class="card chart-card">
            <div class="card-header py-2"><i class="bi bi-bank me-1"></i>Statutory Contributions YTD</div>
            <div class="card-body">
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
        <div class="card chart-card">
            <div class="card-header py-2"><i class="bi bi-funnel me-1"></i>Onboarding Pipeline</div>
            <div class="card-body">
                <div class="d-flex flex-column gap-3">
                    <div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-semibold">Pending</span>
                            <span class="badge bg-warning text-dark">{{ $pipelineStats['pending'] }}</span>
                        </div>
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar bg-warning" style="width:{{ max(($pipelineStats['pending']/max(array_sum($pipelineStats),1))*100,2) }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-semibold">Active (In Progress)</span>
                            <span class="badge bg-primary">{{ $pipelineStats['active'] }}</span>
                        </div>
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar bg-primary" style="width:{{ max(($pipelineStats['active']/max(array_sum($pipelineStats),1))*100,2) }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-semibold">Completed ({{ $year }})</span>
                            <span class="badge bg-success">{{ $pipelineStats['completed'] }}</span>
                        </div>
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar bg-success" style="width:{{ max(($pipelineStats['completed']/max(array_sum($pipelineStats),1))*100,2) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Asset Summary --}}
    <div class="col-lg-4">
        <div class="card chart-card">
            <div class="card-header py-2"><i class="bi bi-laptop me-1"></i>Asset Portfolio</div>
            <div class="card-body">
                <div style="position:relative;height:220px;">
                    <canvas id="assetStatusChart"></canvas>
                </div>
                <div class="d-flex justify-content-between mt-3 small">
                    <div><span class="text-muted">Total Value:</span> <strong>RM {{ number_format($assetCostTotal, 0) }}</strong></div>
                    <div><span class="text-muted">Monthly Rental:</span> <strong>RM {{ number_format($rentalCostMonthly, 0) }}</strong></div>
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
