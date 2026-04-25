@extends('layouts.app')
@section('title', 'Accounting Dashboard')
@section('page-title', 'Accounting Dashboard')

@section('content')
@include('accounting.partials.nav')

{{-- Company Filter --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div></div>
    <form class="d-flex gap-2">
        <select name="company" class="form-select form-select-sm" style="width:200px;" onchange="this.form.submit()">
            <option value="">All Companies</option>
            @foreach($companies ?? [] as $key => $name)
                <option value="{{ $key }}" {{ request('company') == $key ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
    </form>
</div>

{{-- KPI Cards --}}
@include('partials.dashboard-widgets-style')
<div class="section-header">
    <div class="section-icon" style="background:var(--primary-tint);"><i class="bi bi-calculator" style="font-size:16px;color:var(--primary-dark);"></i></div>
    <h6>Accounting Overview</h6>
</div>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;"><div class="widget-header" style="background:linear-gradient(135deg,#22c55e,#15803d);padding:16px 18px 12px;">
            <div class="d-flex align-items-center gap-3"><div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-graph-up-arrow" style="font-size:18px;"></i></div>
            <div><div class="widget-number" style="font-size:20px;">RM {{ number_format($monthlyRevenue ?? 0, 2) }}</div><div class="widget-label">Monthly Revenue</div></div></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;"><div class="widget-header" style="background:linear-gradient(135deg,#ef4444,#b91c1c);padding:16px 18px 12px;">
            <div class="d-flex align-items-center gap-3"><div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-graph-down-arrow" style="font-size:18px;"></i></div>
            <div><div class="widget-number" style="font-size:20px;">RM {{ number_format($monthlyExpenses ?? 0, 2) }}</div><div class="widget-label">Monthly Expenses</div></div></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;"><div class="widget-header" style="background:linear-gradient(135deg,#1FA896,#11766A);padding:16px 18px 12px;">
            <div class="d-flex align-items-center gap-3"><div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-arrow-down-left-circle-fill" style="font-size:18px;"></i></div>
            <div><div class="widget-number" style="font-size:20px;">RM {{ number_format($totalReceivable ?? 0, 2) }}</div><div class="widget-label">Receivable Outstanding</div>
            @if(($overdueInvoices ?? 0) > 0)<div style="margin-top:4px;"><span style="font-size:10px;font-weight:600;padding:3px 10px;border-radius:20px;background:rgba(255,255,255,.2);color:#fff;">{{ $overdueInvoices }} overdue</span></div>@endif
            </div></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;"><div class="widget-header" style="background:linear-gradient(135deg,#f59e0b,#d97706);padding:16px 18px 12px;">
            <div class="d-flex align-items-center gap-3"><div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-arrow-up-right-circle-fill" style="font-size:18px;"></i></div>
            <div><div class="widget-number" style="font-size:20px;">RM {{ number_format($totalPayable ?? 0, 2) }}</div><div class="widget-label">Payable Outstanding</div>
            @if(($overdueBills ?? 0) > 0)<div style="margin-top:4px;"><span style="font-size:10px;font-weight:600;padding:3px 10px;border-radius:20px;background:rgba(255,255,255,.2);color:#fff;">{{ $overdueBills }} overdue</span></div>@endif
            </div></div>
        </div></div>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;"><div class="widget-header" style="background:linear-gradient(135deg,{{ ($netProfit ?? 0) >= 0 ? '#22c55e,#15803d' : '#ef4444,#b91c1c' }});padding:16px 18px 12px;">
            <div class="d-flex align-items-center gap-3"><div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-trophy-fill" style="font-size:18px;"></i></div>
            <div><div class="widget-number" style="font-size:20px;">RM {{ number_format($netProfit ?? 0, 2) }}</div><div class="widget-label">Net Profit (YTD)</div></div></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;"><div class="widget-header" style="background:linear-gradient(135deg,#22B8A5,#0A4D47);padding:16px 18px 12px;">
            <div class="d-flex align-items-center gap-3"><div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-bank2" style="font-size:18px;"></i></div>
            <div><div class="widget-number" style="font-size:20px;">RM {{ number_format($totalBankBalance ?? 0, 2) }}</div><div class="widget-label">Bank Balances</div></div></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;"><div class="widget-header" style="background:linear-gradient(135deg,#0F1A1D,#2A3438);padding:16px 18px 12px;">
            <div class="d-flex align-items-center gap-3"><div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-file-earmark-text-fill" style="font-size:18px;"></i></div>
            <div><div class="widget-number" style="font-size:26px;">{{ $pendingInvoices ?? 0 }}</div><div class="widget-label">Pending Invoices</div></div></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;"><div class="widget-header" style="background:linear-gradient(135deg,#f97316,#c2410c);padding:16px 18px 12px;">
            <div class="d-flex align-items-center gap-3"><div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-file-earmark-minus-fill" style="font-size:18px;"></i></div>
            <div><div class="widget-number" style="font-size:26px;">{{ $pendingBills ?? 0 }}</div><div class="widget-label">Pending Bills</div></div></div>
        </div></div>
    </div>
</div>

{{-- Revenue Trend Chart --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white"><strong>12-Month Revenue vs Expenses</strong></div>
            <div class="card-body">
                <canvas id="revenueTrendChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white"><strong>Recent Invoices</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0" style="font-size:13px;">
                    <thead><tr><th>Invoice</th><th>Customer</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                    @forelse($recentInvoices ?? [] as $inv)
                        <tr>
                            <td>{{ $inv->invoice_number }}</td>
                            <td>{{ $inv->customer->name ?? '-' }}</td>
                            <td class="text-end">{{ number_format($inv->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted text-center py-3">No invoices yet</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white"><strong>Recent Bills</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0" style="font-size:13px;">
                    <thead><tr><th>Bill #</th><th>Vendor</th><th>Due</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                    @forelse($recentBills ?? [] as $bill)
                        <tr>
                            <td>{{ $bill->bill_number }}</td>
                            <td>{{ $bill->vendor->name ?? '-' }}</td>
                            <td>{{ \Carbon\Carbon::parse($bill->due_date)->format('d M') }}</td>
                            <td class="text-end">{{ number_format($bill->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted text-center py-3">No bills yet</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white"><strong>Bank Accounts</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0" style="font-size:13px;">
                    <thead><tr><th>Bank</th><th>Account</th><th class="text-end">Balance</th></tr></thead>
                    <tbody>
                    @forelse($bankAccounts ?? [] as $ba)
                        <tr>
                            <td>{{ $ba->bank_name }}</td>
                            <td>{{ $ba->account_name }}</td>
                            <td class="text-end fw-semibold">{{ number_format($ba->current_balance, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted text-center py-3">No bank accounts</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    const trend = @json($revenueTrend ?? []);
    const labels = trend.map(t => t.month);
    const rev = trend.map(t => t.revenue);
    const exp = trend.map(t => t.expenses);

    new Chart(document.getElementById('revenueTrendChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Revenue', data: rev, backgroundColor: 'rgba(34,197,94,.6)' },
                { label: 'Expenses', data: exp, backgroundColor: 'rgba(239,68,68,.5)' }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => 'RM ' + v.toLocaleString() } } }
        }
    });
});
</script>
@endpush
