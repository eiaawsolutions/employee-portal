@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- Welcome Banner --}}
@php
    $dashUser = Auth::user();
    $dashName = $dashUser->employee?->full_name ?? $dashUser->name;
    $dashDesig = $dashUser->employee?->designation ?? ucwords(str_replace('_',' ',$dashUser->role));
    $dashCompany = $dashUser->employee?->company;
@endphp
<div class="card mb-4" style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 50%,#3b82f6 100%);border:none;border-radius:16px;overflow:hidden;position:relative;">
    <div style="position:absolute;top:-40px;right:-20px;width:150px;height:150px;border-radius:50%;background:rgba(255,255,255,.07);"></div>
    <div style="position:absolute;bottom:-30px;right:80px;width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,.05);"></div>
    <div class="card-body d-flex align-items-center gap-3 py-3" style="position:relative;z-index:1;">
        <div style="width:52px;height:52px;background:rgba(255,255,255,0.2);border-radius:14px;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
            <i class="bi bi-person-fill" style="font-size:26px;color:#fff;"></i>
        </div>
        <div>
            <h5 class="text-white mb-0 fw-bold">Welcome, {{ $dashName }}</h5>
            <small style="color:rgba(255,255,255,0.8);">{{ $dashDesig }}{{ $dashCompany ? ' · '.$dashCompany : '' }}</small>
        </div>
        <div class="ms-auto text-end d-none d-md-block">
            <small style="color:rgba(255,255,255,.7);font-size:12px;">{{ now()->format('l, d/m/Y') }}</small>
        </div>
    </div>
</div>

@include('partials.birthday-babies-widget')

@include('partials.announcements-widget')

@include('partials.on-leave-widget')

@include('partials.dashboard-widgets-style')

{{-- ── WORKFORCE OVERVIEW ─────────────────────────────────────────────── --}}
@php
    $allCompanyNames = collect($activeByCompany)->pluck('company')->filter()->unique()->sort()->values();
@endphp
<div class="section-header">
    <div class="section-icon" style="background:#f0fdf4;">
        <i class="bi bi-people-fill" style="font-size:16px;color:#16a34a;"></i>
    </div>
    <h6>Workforce Overview</h6>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card dash-widget h-100" id="card-active">
            <div class="widget-header" style="background:linear-gradient(135deg,#22c55e,#15803d);">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-people-fill"></i></div>
                    <div class="flex-grow-1">
                        <div class="widget-number" data-total="{{ $stats['active_employees'] }}">{{ $stats['active_employees'] }}</div>
                        <div class="widget-label">Active Employees</div>
                    </div>
                    <div class="widget-filter">
                        <select class="form-select form-select-sm dash-card-filter" data-card="active">
                            <option value="">All</option>
                            @foreach($allCompanyNames as $name)
                            <option value="{{ $name }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="widget-body flex-fill">
                <div class="breakdown-title">By Company</div>
                @forelse($activeByCompany as $row)
                <div class="breakdown-row dash-filter-row" data-company="{{ $row->company }}">
                    <span>{{ $row->company }}</span>
                    <span class="breakdown-badge" style="background:#22c55e;">{{ $row->total }}</span>
                </div>
                @empty
                <div class="text-muted small text-center py-2 dash-empty">No active employees</div>
                @endforelse
            </div>
        </div>
    </div>
</div>


@include('partials.claim-modal')
@include('partials.leave-modal')

<script nonce="{{ $cspNonce ?? '' }}">
function filterDashCard(cardKey, company) {
    var card = document.getElementById('card-' + cardKey);
    if (!card) return;
    var numberEl = card.querySelector('.widget-number');
    var rows = card.querySelectorAll('.dash-filter-row');
    var selected = company ? company.trim() : '';
    if (!selected) {
        numberEl.textContent = numberEl.dataset.total;
        rows.forEach(function(r) { r.style.display = ''; });
        return;
    }
    var filteredTotal = 0;
    rows.forEach(function(row) {
        if (row.dataset.company === selected) {
            row.style.display = '';
            filteredTotal += parseInt(row.querySelector('.breakdown-badge').textContent, 10) || 0;
        } else {
            row.style.display = 'none';
        }
    });
    numberEl.textContent = filteredTotal;
}

document.querySelectorAll('.dash-card-filter').forEach(function(sel) {
    sel.addEventListener('change', function() {
        filterDashCard(this.dataset.card, this.value);
    });
});
</script>
@endsection
