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
    <div class="section-icon" style="background:#f0fdf4;"><i class="bi bi-bar-chart-line-fill" style="font-size:16px;color:#16a34a;"></i></div>
    <h6>Financial Ratios &amp; KPIs</h6>
</div>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card dash-widget h-100" style="min-height:auto;"><div class="widget-header" style="background:linear-gradient(135deg,{{ ($ratios['currentRatio'] ?? 0) >= 1 ? '#22c55e,#15803d' : '#ef4444,#b91c1c' }});padding:16px 18px 12px;">
            <div class="d-flex align-items-center gap-3"><div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-speedometer" style="font-size:18px;"></i></div>
            <div><div class="widget-number" style="font-size:26px;">{{ number_format($ratios['currentRatio'] ?? 0, 2) }}</div><div class="widget-label">Current Ratio</div></div></div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card dash-widget h-100" style="min-height:auto;"><div class="widget-header" style="background:linear-gradient(135deg,{{ ($ratios['profitMargin'] ?? 0) >= 0 ? '#22c55e,#15803d' : '#ef4444,#b91c1c' }});padding:16px 18px 12px;">
            <div class="d-flex align-items-center gap-3"><div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-percent" style="font-size:18px;"></i></div>
            <div><div class="widget-number" style="font-size:26px;">{{ number_format(($ratios['profitMargin'] ?? 0) * 100, 1) }}%</div><div class="widget-label">Profit Margin</div></div></div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card dash-widget h-100" style="min-height:auto;"><div class="widget-header" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);padding:16px 18px 12px;">
            <div class="d-flex align-items-center gap-3"><div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-graph-up-arrow" style="font-size:18px;"></i></div>
            <div><div class="widget-number" style="font-size:18px;">RM {{ number_format($ytdRevenue ?? 0, 0) }}</div><div class="widget-label">YTD Revenue</div></div></div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card dash-widget h-100" style="min-height:auto;"><div class="widget-header" style="background:linear-gradient(135deg,#ef4444,#b91c1c);padding:16px 18px 12px;">
            <div class="d-flex align-items-center gap-3"><div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-graph-down-arrow" style="font-size:18px;"></i></div>
            <div><div class="widget-number" style="font-size:18px;">RM {{ number_format($ytdExpenses ?? 0, 0) }}</div><div class="widget-label">YTD Expenses</div></div></div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card dash-widget h-100" style="min-height:auto;"><div class="widget-header" style="background:linear-gradient(135deg,{{ ($ytdNetProfit ?? 0) >= 0 ? '#22c55e,#15803d' : '#ef4444,#b91c1c' }});padding:16px 18px 12px;">
            <div class="d-flex align-items-center gap-3"><div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-trophy-fill" style="font-size:18px;"></i></div>
            <div><div class="widget-number" style="font-size:18px;">RM {{ number_format($ytdNetProfit ?? 0, 0) }}</div><div class="widget-label">YTD Net Profit</div></div></div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card dash-widget h-100" style="min-height:auto;"><div class="widget-header" style="background:linear-gradient(135deg,#06b6d4,#0891b2);padding:16px 18px 12px;">
            <div class="d-flex align-items-center gap-3"><div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-bank2" style="font-size:18px;"></i></div>
            <div><div class="widget-number" style="font-size:18px;">RM {{ number_format($cashPosition ?? 0, 0) }}</div><div class="widget-label">Cash Position</div></div></div>
        </div></div>
    </div>
</div>

{{-- Charts Row --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white"><strong>Monthly Revenue vs Expenses (12 Months)</strong></div>
            <div class="card-body"><canvas id="monthlyTrend" height="100"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white"><strong>Expense Breakdown</strong></div>
            <div class="card-body"><canvas id="expenseBreakdown" height="200"></canvas></div>
        </div>
    </div>
</div>

{{-- Aged AR & AP --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white"><strong>Aged Receivables</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:13px;">
                    <thead><tr><th>Customer</th><th class="text-end">Current</th><th class="text-end">31-60</th><th class="text-end">61-90</th><th class="text-end">90+</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    @forelse($agedReceivables ?? [] as $ar)
                        <tr>
                            <td>{{ $ar['customer'] }}</td>
                            <td class="text-end">{{ number_format($ar['current'] ?? 0, 2) }}</td>
                            <td class="text-end">{{ number_format($ar['31_60'] ?? 0, 2) }}</td>
                            <td class="text-end">{{ number_format($ar['61_90'] ?? 0, 2) }}</td>
                            <td class="text-end text-danger">{{ number_format($ar['over_90'] ?? 0, 2) }}</td>
                            <td class="text-end fw-bold">{{ number_format($ar['total'] ?? 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">No outstanding receivables</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white"><strong>Aged Payables</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:13px;">
                    <thead><tr><th>Vendor</th><th class="text-end">Current</th><th class="text-end">31-60</th><th class="text-end">61-90</th><th class="text-end">90+</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    @forelse($agedPayables ?? [] as $ap)
                        <tr>
                            <td>{{ $ap['vendor'] }}</td>
                            <td class="text-end">{{ number_format($ap['current'] ?? 0, 2) }}</td>
                            <td class="text-end">{{ number_format($ap['31_60'] ?? 0, 2) }}</td>
                            <td class="text-end">{{ number_format($ap['61_90'] ?? 0, 2) }}</td>
                            <td class="text-end text-danger">{{ number_format($ap['over_90'] ?? 0, 2) }}</td>
                            <td class="text-end fw-bold">{{ number_format($ap['total'] ?? 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">No outstanding payables</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Balance Sheet Summary --}}
<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-white"><strong>Balance Sheet - Assets</strong></div>
            <div class="card-body">
                <div class="d-flex justify-content-between"><span>Total Assets</span><strong>RM {{ number_format($balanceSheet['totalAssets'] ?? 0, 2) }}</strong></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-white"><strong>Balance Sheet - Liabilities</strong></div>
            <div class="card-body">
                <div class="d-flex justify-content-between"><span>Total Liabilities</span><strong>RM {{ number_format($balanceSheet['totalLiabilities'] ?? 0, 2) }}</strong></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-white"><strong>Balance Sheet - Equity</strong></div>
            <div class="card-body">
                <div class="d-flex justify-content-between"><span>Total Equity</span><strong>RM {{ number_format($balanceSheet['totalEquity'] ?? 0, 2) }}</strong></div>
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
