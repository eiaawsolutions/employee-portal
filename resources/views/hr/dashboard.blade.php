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

{{-- ── Dashboard Widget Styles ──────────────────────────────────────────── --}}
<style>
    .dash-widget {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        transition: transform .2s ease, box-shadow .2s ease;
        box-shadow: 0 2px 12px rgba(0,0,0,.06);
        min-height: 220px;
    }
    .dash-widget:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,.1);
    }
    .dash-widget .widget-header {
        padding: 20px 22px 14px;
        position: relative;
        overflow: hidden;
    }
    .dash-widget .widget-header::before {
        content: '';
        position: absolute;
        top: -30px;
        right: -30px;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: rgba(255,255,255,.12);
    }
    .dash-widget .widget-header::after {
        content: '';
        position: absolute;
        bottom: -20px;
        right: 40px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: rgba(255,255,255,.08);
    }
    .dash-widget .widget-icon {
        width: 50px;
        height: 50px;
        border-radius: 14px;
        background: rgba(255,255,255,.22);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        backdrop-filter: blur(4px);
    }
    .dash-widget .widget-icon i {
        font-size: 22px;
        color: #fff;
    }
    .dash-widget .widget-number {
        font-size: 32px;
        font-weight: 800;
        color: #fff;
        line-height: 1;
        letter-spacing: -0.5px;
    }
    .dash-widget .widget-label {
        font-size: 12px;
        color: rgba(255,255,255,.85);
        font-weight: 500;
    }
    .dash-widget .widget-body {
        padding: 14px 22px 18px;
        background: #fff;
    }
    .dash-widget .breakdown-title {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #94a3b8;
        margin-bottom: 8px;
    }
    .dash-widget .breakdown-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: 12.5px;
        color: #334155;
    }
    .dash-widget .breakdown-row:last-child {
        border-bottom: none;
    }
    .dash-widget .breakdown-badge {
        font-size: 11px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 20px;
        color: #fff;
    }
    .section-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
    }
    .section-header .section-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .section-header h6 {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #475569;
        margin: 0;
    }
    .dash-widget .widget-filter {
        position: relative;
        z-index: 1;
    }
    .dash-widget .widget-filter select {
        background: rgba(255,255,255,.2);
        border: 1px solid rgba(255,255,255,.3);
        color: #fff;
        font-size: 11px;
        border-radius: 8px;
        padding: 4px 24px 4px 10px;
        backdrop-filter: blur(4px);
        cursor: pointer;
    }
    .dash-widget .widget-filter select option {
        color: #334155;
        background: #fff;
    }
    .dash-widget .status-pills {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }
    .dash-widget .status-pill {
        font-size: 11px;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 20px;
        backdrop-filter: blur(4px);
    }
</style>

{{-- ── ONBOARDING OVERVIEW ──────────────────────────────────────────────── --}}
<div class="section-header">
    <div class="section-icon" style="background:#eff6ff;">
        <i class="bi bi-person-plus" style="font-size:16px;color:#2563eb;"></i>
    </div>
    <h6>Onboarding Overview</h6>
</div>
<div class="row g-3 mb-4">

    {{-- 1. Total Onboard Year to Date --}}
    <div class="col-md-3">
        <div class="card dash-widget h-100">
            <div class="widget-header" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-person-plus-fill"></i></div>
                    <div>
                        <div class="widget-number">{{ $stats['total_onboardings_ytd'] }}</div>
                        <div class="widget-label">Onboarded YTD</div>
                    </div>
                </div>
            </div>
            <div class="widget-body flex-fill">
                <div class="breakdown-title">By Company</div>
                @forelse($onboardingsByCompany as $row)
                <div class="breakdown-row">
                    <span>{{ $row->company }}</span>
                    <span class="breakdown-badge" style="background:#3b82f6;">{{ $row->total }}</span>
                </div>
                @empty
                <div class="text-muted small text-center py-2">No data yet</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Collect all unique company names from the actual data for filter options --}}
    @php
        $allCompanyNames = collect($newJoinersByCompany)->pluck('company')
            ->merge(collect($exitingByCompany)->pluck('company'))
            ->merge(collect($activeByCompany)->pluck('company'))
            ->filter()->unique()->sort()->values();
    @endphp

    {{-- 2. New Joiners This Month --}}
    <div class="col-md-3">
        <div class="card dash-widget h-100" id="card-joiners">
            <div class="widget-header" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-calendar-plus-fill"></i></div>
                    <div class="flex-grow-1">
                        <div class="widget-number" data-total="{{ $stats['new_joiners_this_month'] }}">{{ $stats['new_joiners_this_month'] }}</div>
                        <div class="widget-label">New Joiners This Month</div>
                    </div>
                    <div class="widget-filter">
                        <select class="form-select form-select-sm" onchange="filterDashCard('joiners', this.value)">
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
                @forelse($newJoinersByCompany as $row)
                <div class="breakdown-row dash-filter-row" data-company="{{ $row->company }}">
                    <span>{{ $row->company }}</span>
                    <span class="breakdown-badge" style="background:#f59e0b;">{{ $row->total }}</span>
                </div>
                @empty
                <div class="text-muted small text-center py-2 dash-empty">No new joiners this month</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- 3. Exiting This Month --}}
    <div class="col-md-3">
        <div class="card dash-widget h-100" id="card-exiting">
            <div class="widget-header" style="background:linear-gradient(135deg,#ef4444,#b91c1c);">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-calendar-x-fill"></i></div>
                    <div class="flex-grow-1">
                        <div class="widget-number" data-total="{{ $stats['exiting_this_month'] }}">{{ $stats['exiting_this_month'] }}</div>
                        <div class="widget-label">Exiting This Month</div>
                    </div>
                    <div class="widget-filter">
                        <select class="form-select form-select-sm" onchange="filterDashCard('exiting', this.value)">
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
                @forelse($exitingByCompany as $row)
                <div class="breakdown-row dash-filter-row" data-company="{{ $row->company ?? 'Unknown' }}">
                    <span>{{ $row->company ?? 'Unknown' }}</span>
                    <span class="breakdown-badge" style="background:#ef4444;">{{ $row->total }}</span>
                </div>
                @empty
                <div class="text-muted small text-center py-2 dash-empty">No exits this month</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- 4. Active Employees --}}
    <div class="col-md-3">
        <div class="card dash-widget h-100" id="card-active">
            <div class="widget-header" style="background:linear-gradient(135deg,#22c55e,#15803d);">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-people-fill"></i></div>
                    <div class="flex-grow-1">
                        <div class="widget-number" data-total="{{ $stats['active_employees'] }}">{{ $stats['active_employees'] }}</div>
                        <div class="widget-label">Active Employees</div>
                    </div>
                    <div class="widget-filter">
                        <select class="form-select form-select-sm" onchange="filterDashCard('active', this.value)">
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

{{-- ── ASSET OVERVIEW (superadmin only) ─────────────────────────────────── --}}
@if(Auth::user()->isSuperadmin())
<div class="section-header mt-2">
    <div class="section-icon" style="background:#f0fdf4;">
        <i class="bi bi-laptop" style="font-size:16px;color:#16a34a;"></i>
    </div>
    <h6>Asset Overview</h6>
</div>
<div class="row g-3 mb-4">

    {{-- Card 1: Overall Assets --}}
    <div class="col-md-4">
        <div class="card dash-widget h-100">
            <div class="widget-header" style="background:linear-gradient(135deg,#6366f1,#4338ca);">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-laptop-fill"></i></div>
                    <div>
                        <div class="widget-number">{{ $assetStats['total_assets'] }}</div>
                        <div class="widget-label">Overall Assets</div>
                    </div>
                    <div class="ms-auto status-pills">
                        <span class="status-pill" style="background:rgba(255,255,255,.2);color:#fff;">{{ $assetStats['available'] }} Available</span>
                        <span class="status-pill" style="background:rgba(255,255,255,.2);color:#fff;">{{ $assetStats['assigned'] }} Assigned</span>
                    </div>
                </div>
            </div>
            <div class="widget-body flex-fill">
                <div class="breakdown-title">By Type</div>
                @forelse($assetsByType as $row)
                <div class="breakdown-row">
                    <span>{{ ucfirst(str_replace('_',' ', $row->asset_type)) }}</span>
                    <span class="breakdown-badge" style="background:#6366f1;">{{ $row->total }}</span>
                </div>
                @empty
                <div class="text-muted small text-center py-2">No assets</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Card 2: Company Owned --}}
    <div class="col-md-4">
        <div class="card dash-widget h-100">
            <div class="widget-header" style="background:linear-gradient(135deg,#14b8a6,#0f766e);">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-building-fill"></i></div>
                    <div>
                        <div class="widget-number">{{ $companyOwnedTotal }}</div>
                        <div class="widget-label">Company Owned</div>
                    </div>
                </div>
            </div>
            <div class="widget-body flex-fill">
                <div class="breakdown-title">By Company</div>
                @forelse($companyOwnedByCompany as $row)
                <div class="breakdown-row">
                    <span>{{ $row->company }}</span>
                    <span class="breakdown-badge" style="background:#14b8a6;">{{ $row->total }}</span>
                </div>
                @empty
                <div class="text-muted small text-center py-2">No company-owned assets</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Card 3: Rental --}}
    <div class="col-md-4">
        <div class="card dash-widget h-100">
            <div class="widget-header" style="background:linear-gradient(135deg,#f97316,#c2410c);">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-truck-front-fill"></i></div>
                    <div>
                        <div class="widget-number">{{ $rentalTotal }}</div>
                        <div class="widget-label">Rental / Leased</div>
                    </div>
                </div>
            </div>
            <div class="widget-body flex-fill">
                <div class="breakdown-title">By Vendor</div>
                @forelse($rentalByVendor as $row)
                <div class="breakdown-row">
                    <span>{{ $row->vendor }}</span>
                    <span class="breakdown-badge" style="background:#f97316;">{{ $row->total }}</span>
                </div>
                @empty
                <div class="text-muted small text-center py-2">No rental assets</div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endif

{{-- ── CLAIM & LEAVE WIDGETS (hidden — do not remove, re-enable later) ──── --}}
{{--
<div class="row g-3">
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width:48px;height:48px;background:#dbeafe;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-receipt" style="font-size:24px;color:#2563eb;"></i>
                </div>
                <div><h6 class="mb-0 fw-bold">Claim Calculator</h6><small class="text-muted">Submit and track expense claims</small></div>
            </div>
            <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#claimModal">
                <i class="bi bi-plus-circle me-2"></i>Claim Calculator
            </button>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width:48px;height:48px;background:#dcfce7;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-calendar-check" style="font-size:24px;color:#16a34a;"></i>
                </div>
                <div><h6 class="mb-0 fw-bold">Leave Calculator</h6><small class="text-muted">Check balance & plan leave</small></div>
            </div>
            <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#leaveModal">
                <i class="bi bi-calendar3 me-2"></i>Leave Calculator
            </button>
        </div></div>
    </div>
</div>
--}}

@include('partials.claim-modal')
@include('partials.leave-modal')

<script>
function filterDashCard(cardKey, company) {
    const card = document.getElementById('card-' + cardKey);
    if (!card) return;
    const numberEl = card.querySelector('.widget-number');
    const rows = card.querySelectorAll('.dash-filter-row');
    const selected = company ? company.trim() : '';

    if (!selected) {
        // Show all — restore total
        numberEl.textContent = numberEl.dataset.total;
        rows.forEach(r => r.style.display = '');
        return;
    }

    let filteredTotal = 0;
    rows.forEach(row => {
        if (row.dataset.company === selected) {
            row.style.display = '';
            filteredTotal += parseInt(row.querySelector('.breakdown-badge').textContent, 10) || 0;
        } else {
            row.style.display = 'none';
        }
    });
    numberEl.textContent = filteredTotal;
}
</script>
@endsection