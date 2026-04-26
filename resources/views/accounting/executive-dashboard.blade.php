@extends('layouts.app')
@section('title', 'Executive Financial Dashboard')
@section('page-title', 'Executive Financial Dashboard')

@section('content')
@include('accounting.partials.nav')

<div class="d-flex justify-content-end mb-3">
    <form class="d-flex gap-2">
        <select name="company" class="form-select form-select-sm" style="width:200px;" onchange="this.form.submit()">
            <option value="">All Companies</option>
            @foreach($companies ?? [] as $key => $name)
                <option value="{{ $key }}" {{ request('company') == $key ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
    </form>
</div>

{{-- Financial Ratios --}}
@include('partials.dashboard-widgets-style')
<div class="section-header">
    <h6>Financial Ratios &amp; KPIs</h6>
</div>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-speedometer"></i></div>
                    <div>
                        <div class="widget-number" style="color:{{ ($ratios['currentRatio'] ?? 0) >= 1 ? 'var(--success)' : 'var(--danger)' }};">{{ number_format($ratios['currentRatio'] ?? 0, 2) }}</div>
                        <div class="widget-label">Current Ratio</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-percent"></i></div>
                    <div>
                        <div class="widget-number" style="color:{{ ($ratios['profitMargin'] ?? 0) >= 0 ? 'var(--success)' : 'var(--danger)' }};">{{ number_format(($ratios['profitMargin'] ?? 0) * 100, 1) }}%</div>
                        <div class="widget-label">Profit Margin</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-graph-up-arrow"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:20px;">RM {{ number_format($ytdRevenue ?? 0, 0) }}</div>
                        <div class="widget-label">YTD Revenue</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-graph-down-arrow"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:20px;">RM {{ number_format($ytdExpenses ?? 0, 0) }}</div>
                        <div class="widget-label">YTD Expenses</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-trophy-fill"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:20px;color:{{ ($ytdNetProfit ?? 0) >= 0 ? 'var(--success)' : 'var(--danger)' }};">RM {{ number_format($ytdNetProfit ?? 0, 0) }}</div>
                        <div class="widget-label">YTD Net Profit</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-bank2"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:20px;">RM {{ number_format($cashPosition ?? 0, 0) }}</div>
                        <div class="widget-label">Cash Position</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Charts Row --}}
<div class="section-header"><h6>Trend Analysis</h6></div>
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card dash-widget" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-bar-chart-line-fill"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:18px;">Monthly Revenue vs Expenses</div>
                        <div class="widget-label">12 Months</div>
                    </div>
                </div>
            </div>
            <div class="widget-body"><canvas id="monthlyTrend" height="100"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-pie-chart-fill"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:18px;">Expense Breakdown</div>
                        <div class="widget-label">By Category</div>
                    </div>
                </div>
            </div>
            <div class="widget-body"><canvas id="expenseBreakdown" height="200"></canvas></div>
        </div>
    </div>
</div>

{{-- Aged AR & AP --}}
<div class="section-header"><h6>Aged AR &amp; AP</h6></div>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card dash-widget" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-arrow-down-left-circle-fill"></i></div>
                    <div>
                        <div class="widget-number">{{ count($agedReceivables ?? []) }}</div>
                        <div class="widget-label">Aged Receivables</div>
                    </div>
                </div>
            </div>
            <div class="widget-body" style="padding:0;">
                @if(empty($agedReceivables))
                    <div class="text-center py-4">
                        <div style="width:44px;height:44px;background:var(--primary-tint);border:1px solid rgba(17,118,106,0.14);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                            <i class="bi bi-arrow-down-left-circle-fill" style="font-size:18px;color:var(--primary-dark);"></i>
                        </div>
                        <div style="font-family:var(--sans);font-size:13px;color:var(--mute);">No outstanding receivables</div>
                    </div>
                @else
                    <table class="table table-sm mb-0" style="font-size:13px;">
                        <thead><tr><th>Customer</th><th class="text-end">Current</th><th class="text-end">31-60</th><th class="text-end">61-90</th><th class="text-end">90+</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                        @foreach($agedReceivables as $ar)
                            <tr>
                                <td>{{ $ar['customer'] }}</td>
                                <td class="text-end">{{ number_format($ar['current'] ?? 0, 2) }}</td>
                                <td class="text-end">{{ number_format($ar['31_60'] ?? 0, 2) }}</td>
                                <td class="text-end">{{ number_format($ar['61_90'] ?? 0, 2) }}</td>
                                <td class="text-end" style="color:var(--danger);">{{ number_format($ar['over_90'] ?? 0, 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format($ar['total'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card dash-widget" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-arrow-up-right-circle-fill"></i></div>
                    <div>
                        <div class="widget-number">{{ count($agedPayables ?? []) }}</div>
                        <div class="widget-label">Aged Payables</div>
                    </div>
                </div>
            </div>
            <div class="widget-body" style="padding:0;">
                @if(empty($agedPayables))
                    <div class="text-center py-4">
                        <div style="width:44px;height:44px;background:var(--primary-tint);border:1px solid rgba(17,118,106,0.14);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                            <i class="bi bi-arrow-up-right-circle-fill" style="font-size:18px;color:var(--primary-dark);"></i>
                        </div>
                        <div style="font-family:var(--sans);font-size:13px;color:var(--mute);">No outstanding payables</div>
                    </div>
                @else
                    <table class="table table-sm mb-0" style="font-size:13px;">
                        <thead><tr><th>Vendor</th><th class="text-end">Current</th><th class="text-end">31-60</th><th class="text-end">61-90</th><th class="text-end">90+</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                        @foreach($agedPayables as $ap)
                            <tr>
                                <td>{{ $ap['vendor'] }}</td>
                                <td class="text-end">{{ number_format($ap['current'] ?? 0, 2) }}</td>
                                <td class="text-end">{{ number_format($ap['31_60'] ?? 0, 2) }}</td>
                                <td class="text-end">{{ number_format($ap['61_90'] ?? 0, 2) }}</td>
                                <td class="text-end" style="color:var(--danger);">{{ number_format($ap['over_90'] ?? 0, 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format($ap['total'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Balance Sheet Summary --}}
<div class="section-header"><h6>Balance Sheet</h6></div>
<div class="row g-3">
    <div class="col-md-4">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-buildings"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:20px;">RM {{ number_format($balanceSheet['totalAssets'] ?? 0, 2) }}</div>
                        <div class="widget-label">Total Assets</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-clipboard-data"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:20px;">RM {{ number_format($balanceSheet['totalLiabilities'] ?? 0, 2) }}</div>
                        <div class="widget-label">Total Liabilities</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-piggy-bank-fill"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:20px;">RM {{ number_format($balanceSheet['totalEquity'] ?? 0, 2) }}</div>
                        <div class="widget-label">Total Equity</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    const trend = @json($monthlyTrend ?? []);
    new Chart(document.getElementById('monthlyTrend'), {
        type: 'line',
        data: {
            labels: trend.map(t => t.month),
            datasets: [
                { label: 'Revenue', data: trend.map(t => t.revenue), borderColor: '#22c55e', fill: false, tension: 0.3 },
                { label: 'Expenses', data: trend.map(t => t.expenses), borderColor: '#ef4444', fill: false, tension: 0.3 },
                { label: 'Net Profit', data: trend.map(t => t.revenue - t.expenses), borderColor: '#3b82f6', borderDash: [5,5], fill: false, tension: 0.3 }
            ]
        },
        options: { responsive: true, scales: { y: { ticks: { callback: v => 'RM ' + v.toLocaleString() } } } }
    });

    const expData = @json($expenseBreakdown ?? []);
    if (expData.length) {
        new Chart(document.getElementById('expenseBreakdown'), {
            type: 'doughnut',
            data: {
                labels: expData.map(e => e.name),
                datasets: [{ data: expData.map(e => e.amount), backgroundColor: ['#3b82f6','#ef4444','#f59e0b','#22c55e','#8b5cf6','#ec4899','#14b8a6','#f97316'] }]
            },
            options: { responsive: true, plugins: { legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } } } }
        });
    }
});
</script>
@endpush
