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
<div class="card mb-4" style="background:linear-gradient(180deg,var(--bg) 0%,var(--bg-warm) 100%);border:1px solid var(--line-soft);border-radius:18px;overflow:hidden;position:relative;box-shadow:0 1px 2px rgba(15,26,29,0.04),0 8px 24px -10px rgba(15,26,29,0.10);">
    <div class="card-body d-flex align-items-center gap-3 py-4" style="position:relative;z-index:1;padding-left:26px;padding-right:26px;">
        <div style="width:52px;height:52px;background:var(--primary-tint);border:1px solid rgba(17,118,106,0.14);border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi bi-person-fill" style="font-size:24px;color:var(--primary-dark);"></i>
        </div>
        <div style="min-width:0;">
            <div style="font-family:var(--mono);font-size:10.5px;font-weight:500;color:var(--primary-dark);text-transform:uppercase;letter-spacing:.14em;display:inline-flex;align-items:center;gap:10px;">
                <span style="width:24px;height:1px;background:currentColor;opacity:.45;"></span>
                Welcome back
            </div>
            <div style="font-family:var(--sans);font-size:22px;font-weight:600;color:var(--ink);letter-spacing:-0.02em;line-height:1.15;margin-top:2px;">
                <em style="font-family:var(--serif);font-style:italic;font-weight:400;color:var(--primary-dark);letter-spacing:-0.005em;">{{ $dashName }}</em>
            </div>
            <div style="font-family:var(--sans);font-size:12.5px;color:var(--mute);margin-top:2px;">
                {{ $dashDesig }}{{ $dashCompany ? ' · '.$dashCompany : '' }}
            </div>
        </div>
        <div class="ms-auto text-end d-none d-md-block">
            <div style="font-family:var(--mono);font-size:10px;color:var(--mute);text-transform:uppercase;letter-spacing:.14em;">{{ now()->format('l') }}</div>
            <div style="font-family:var(--sans);font-size:13px;color:var(--ink-2);font-weight:500;margin-top:2px;">{{ now()->format('d/m/Y') }}</div>
        </div>
    </div>
</div>

@include('partials.announcements-widget')

@include('partials.birthday-babies-widget')

@include('partials.on-leave-widget')

@include('partials.dashboard-widgets-style')


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

// Bind filter dropdowns via addEventListener (CSP nonce-compatible)
document.querySelectorAll('.dash-card-filter').forEach(function(sel) {
    sel.addEventListener('change', function() {
        filterDashCard(this.dataset.card, this.value);
    });
});
</script>
@endsection