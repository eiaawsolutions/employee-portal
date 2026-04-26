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
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-graph-up-arrow"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:22px;">RM {{ number_format($monthlyRevenue ?? 0, 2) }}</div>
                        <div class="widget-label">Monthly Revenue</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-graph-down-arrow"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:22px;">RM {{ number_format($monthlyExpenses ?? 0, 2) }}</div>
                        <div class="widget-label">Monthly Expenses</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-arrow-down-left-circle-fill"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:22px;">RM {{ number_format($totalReceivable ?? 0, 2) }}</div>
                        <div class="widget-label">Receivable Outstanding</div>
                        @if(($overdueInvoices ?? 0) > 0)
                            <div class="status-pills" style="margin-top:6px;"><span class="status-pill">{{ $overdueInvoices }} overdue</span></div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-arrow-up-right-circle-fill"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:22px;">RM {{ number_format($totalPayable ?? 0, 2) }}</div>
                        <div class="widget-label">Payable Outstanding</div>
                        @if(($overdueBills ?? 0) > 0)
                            <div class="status-pills" style="margin-top:6px;"><span class="status-pill">{{ $overdueBills }} overdue</span></div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-trophy-fill"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:22px;color:{{ ($netProfit ?? 0) >= 0 ? 'var(--success)' : 'var(--danger)' }};">RM {{ number_format($netProfit ?? 0, 2) }}</div>
                        <div class="widget-label">Net Profit (YTD)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-bank2"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:22px;">RM {{ number_format($totalBankBalance ?? 0, 2) }}</div>
                        <div class="widget-label">Bank Balances</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
                    <div>
                        <div class="widget-number">{{ $pendingInvoices ?? 0 }}</div>
                        <div class="widget-label">Pending Invoices</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-file-earmark-minus-fill"></i></div>
                    <div>
                        <div class="widget-number">{{ $pendingBills ?? 0 }}</div>
                        <div class="widget-label">Pending Bills</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Revenue Trend Chart --}}
<div class="section-header"><h6>Revenue Trend</h6></div>
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card dash-widget" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-bar-chart-line-fill"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:18px;">12-Month Revenue vs Expenses</div>
                        <div class="widget-label">Trailing 12 months</div>
                    </div>
                </div>
            </div>
            <div class="widget-body">
                <canvas id="revenueTrendChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card dash-widget h-100" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-receipt"></i></div>
                    <div>
                        <div class="widget-number">{{ ($recentInvoices ?? collect())->count() }}</div>
                        <div class="widget-label">Recent Invoices</div>
                    </div>
                </div>
            </div>
            <div class="widget-body" style="padding:0;">
                @if(($recentInvoices ?? collect())->isEmpty())
                    <div class="text-center py-4">
                        <div style="width:44px;height:44px;background:var(--primary-tint);border:1px solid rgba(17,118,106,0.14);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                            <i class="bi bi-receipt" style="font-size:18px;color:var(--primary-dark);"></i>
                        </div>
                        <div style="font-family:var(--sans);font-size:13px;color:var(--mute);">No invoices yet</div>
                    </div>
                @else
                    <table class="table table-sm table-hover mb-0" style="font-size:13px;">
                        <thead><tr><th>Invoice</th><th>Customer</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                        @foreach($recentInvoices as $inv)
                            <tr>
                                <td>{{ $inv->invoice_number }}</td>
                                <td>{{ $inv->customer->name ?? '-' }}</td>
                                <td class="text-end">{{ number_format($inv->total, 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="section-header"><h6>Recent Activity</h6></div>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card dash-widget" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-file-earmark-minus"></i></div>
                    <div>
                        <div class="widget-number">{{ ($recentBills ?? collect())->count() }}</div>
                        <div class="widget-label">Recent Bills</div>
                    </div>
                </div>
            </div>
            <div class="widget-body" style="padding:0;">
                @if(($recentBills ?? collect())->isEmpty())
                    <div class="text-center py-4">
                        <div style="width:44px;height:44px;background:var(--primary-tint);border:1px solid rgba(17,118,106,0.14);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                            <i class="bi bi-file-earmark-minus" style="font-size:18px;color:var(--primary-dark);"></i>
                        </div>
                        <div style="font-family:var(--sans);font-size:13px;color:var(--mute);">No bills yet</div>
                    </div>
                @else
                    <table class="table table-sm table-hover mb-0" style="font-size:13px;">
                        <thead><tr><th>Bill #</th><th>Vendor</th><th>Due</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                        @foreach($recentBills as $bill)
                            <tr>
                                <td>{{ $bill->bill_number }}</td>
                                <td>{{ $bill->vendor->name ?? '-' }}</td>
                                <td>{{ \Carbon\Carbon::parse($bill->due_date)->format('d M') }}</td>
                                <td class="text-end">{{ number_format($bill->total, 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card dash-widget" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-bank2"></i></div>
                    <div>
                        <div class="widget-number">{{ ($bankAccounts ?? collect())->count() }}</div>
                        <div class="widget-label">Bank Accounts</div>
                    </div>
                </div>
            </div>
            <div class="widget-body" style="padding:0;">
                @if(($bankAccounts ?? collect())->isEmpty())
                    <div class="text-center py-4">
                        <div style="width:44px;height:44px;background:var(--primary-tint);border:1px solid rgba(17,118,106,0.14);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                            <i class="bi bi-bank2" style="font-size:18px;color:var(--primary-dark);"></i>
                        </div>
                        <div style="font-family:var(--sans);font-size:13px;color:var(--mute);">No bank accounts</div>
                    </div>
                @else
                    <table class="table table-sm table-hover mb-0" style="font-size:13px;">
                        <thead><tr><th>Bank</th><th>Account</th><th class="text-end">Balance</th></tr></thead>
                        <tbody>
                        @foreach($bankAccounts as $ba)
                            <tr>
                                <td>{{ $ba->bank_name }}</td>
                                <td>{{ $ba->account_name }}</td>
                                <td class="text-end fw-semibold">{{ number_format($ba->current_balance, 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
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
