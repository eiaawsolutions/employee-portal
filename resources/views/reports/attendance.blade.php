@extends('layouts.app')
@section('title', 'Attendance Report')
@section('page-title', 'Attendance Analytics')

@push('styles')
<style>
    .chart-card .card-header { background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-weight: 600; font-size: 13px; }
    .mini-table th { font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600; border-top: none; }
    .mini-table td { font-size: 13px; }
    .rate-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
</style>
@endpush

@section('content')
@include('reports.partials.report-header')
@include('partials.dashboard-widgets-style')

{{-- Section Header --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#14b8a6,#0f766e);display:flex;align-items:center;justify-content:center;">
        <i class="bi bi-calendar-check-fill text-white" style="font-size:15px;"></i>
    </div>
    <div style="font-size:15px;font-weight:700;color:#1e293b;">Attendance Overview</div>
</div>

{{-- KPI Row --}}
@php
    $avgRate = collect($attendanceTrend)->where('rate', '>', 0)->avg('rate');
    $totalOt = collect($attendanceTrend)->sum('ot_hours');
    $totalAbsent = collect($attendanceTrend)->sum('absent');
    $avgLateRate = collect($attendanceTrend)->where('late_rate', '>', 0)->avg('late_rate');
@endphp
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;"><div class="widget-header" style="background:linear-gradient(135deg,#14b8a6,#0f766e);padding:16px 18px 12px;">
            <div class="d-flex align-items-center gap-3"><div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-calendar-check-fill" style="font-size:18px;"></i></div>
            <div><div class="widget-number" style="font-size:26px;">{{ $avgRate ? number_format($avgRate, 1) : '—' }}%</div><div class="widget-label">Avg Attendance Rate</div></div></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;"><div class="widget-header" style="background:linear-gradient(135deg,#ef4444,#b91c1c);padding:16px 18px 12px;">
            <div class="d-flex align-items-center gap-3"><div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-calendar-x-fill" style="font-size:18px;"></i></div>
            <div><div class="widget-number" style="font-size:26px;">{{ number_format($totalAbsent) }}</div><div class="widget-label">Absent Days YTD</div></div></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;"><div class="widget-header" style="background:linear-gradient(135deg,#f59e0b,#d97706);padding:16px 18px 12px;">
            <div class="d-flex align-items-center gap-3"><div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-clock-fill" style="font-size:18px;"></i></div>
            <div><div class="widget-number" style="font-size:26px;">{{ number_format($totalOt, 1) }}</div><div class="widget-label">Overtime Hours YTD</div></div></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card dash-widget h-100" style="min-height:auto;"><div class="widget-header" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);padding:16px 18px 12px;">
            <div class="d-flex align-items-center gap-3"><div class="widget-icon" style="width:40px;height:40px;border-radius:10px;"><i class="bi bi-alarm-fill" style="font-size:18px;"></i></div>
            <div><div class="widget-number" style="font-size:26px;">{{ $avgLateRate ? number_format($avgLateRate, 1) : '—' }}%</div><div class="widget-label">Avg Late Rate</div></div></div>
        </div></div>
    </div>
</div>

{{-- Attendance Rate Trend --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card chart-card h-100">
            <div class="card-header py-2"><i class="bi bi-graph-up me-1"></i>Monthly Attendance Rate — {{ $year }}</div>
            <div class="card-body"><canvas id="attendanceRateChart" height="280"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card chart-card h-100">
            <div class="card-header py-2"><i class="bi bi-clock me-1"></i>Overtime Trend — {{ $year }}</div>
            <div class="card-body"><canvas id="overtimeTrendChart" height="280"></canvas></div>
        </div>
    </div>
</div>

{{-- By Department --}}
<div class="row g-3 mb-4">
    <div class="col-lg-12">
        <div class="card chart-card">
            <div class="card-header py-2"><i class="bi bi-building me-1"></i>Attendance by Department — {{ $year }}</div>
            <div class="card-body">
                <table class="table table-sm mini-table mb-0">
                    <thead>
                        <tr><th>Department</th><th class="text-end">Total Records</th><th class="text-end">Present</th><th class="text-end">Late</th><th class="text-end">Absent</th><th class="text-end">Rate</th></tr>
                    </thead>
                    <tbody>
                    @forelse($byDepartment as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row->dept }}</td>
                        <td class="text-end">{{ number_format($row->total) }}</td>
                        <td class="text-end">{{ number_format($row->present) }}</td>
                        <td class="text-end">{{ number_format($row->late) }}</td>
                        <td class="text-end">{{ number_format($row->absent) }}</td>
                        <td class="text-end">
                            <span class="rate-badge" style="background:{{ $row->rate >= 95 ? '#d1fae5' : ($row->rate >= 85 ? '#fef3c7' : '#fee2e2') }};color:{{ $row->rate >= 95 ? '#059669' : ($row->rate >= 85 ? '#d97706' : '#dc2626') }};">
                                {{ $row->rate }}%
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-muted text-center">No attendance data for {{ $year }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Top Overtime Employees --}}
<div class="row g-3 mb-4">
    <div class="col-lg-12">
        <div class="card chart-card">
            <div class="card-header py-2"><i class="bi bi-moon me-1"></i>Top 10 Overtime Employees — {{ $year }}</div>
            <div class="card-body">
                <table class="table table-sm mini-table mb-0">
                    <thead><tr><th>#</th><th>Employee</th><th>Department</th><th>Company</th><th class="text-end">OT Hours</th><th class="text-end">Requests</th></tr></thead>
                    <tbody>
                    @forelse($topOvertimeEmployees as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $row->full_name }}</td>
                        <td>{{ $row->department }}</td>
                        <td>{{ $row->company }}</td>
                        <td class="text-end fw-semibold">{{ number_format($row->total_hours, 1) }}</td>
                        <td class="text-end">{{ $row->count }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-muted text-center">No overtime data</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
    Chart.defaults.font.size = 11;

    // Attendance Rate
    const arData = @json($attendanceTrend);
    new Chart(document.getElementById('attendanceRateChart'), {
        type: 'line',
        data: {
            labels: arData.map(d => d.month),
            datasets: [
                { label: 'Attendance Rate %', data: arData.map(d => d.rate), borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.3, yAxisID: 'y' },
                { label: 'Late Rate %', data: arData.map(d => d.late_rate), borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.1)', fill: true, tension: 0.3, yAxisID: 'y' },
                { label: 'Absences', data: arData.map(d => d.absent), borderColor: '#ef4444', type: 'bar', backgroundColor: 'rgba(239,68,68,0.7)', borderRadius: 4, yAxisID: 'y1' }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, max: 100, position: 'left', ticks: { callback: v => v + '%' } },
                y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { stepSize: 1 } }
            }
        }
    });

    // Overtime Trend
    const otData = @json($overtimeTrend);
    new Chart(document.getElementById('overtimeTrendChart'), {
        type: 'bar',
        data: { labels: otData.map(d => d.month), datasets: [{ label: 'OT Hours', data: otData.map(d => d.hours), backgroundColor: '#f59e0b', borderRadius: 4 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
});
</script>
@endsection
