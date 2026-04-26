{{-- ── ONBOARDING OVERVIEW ──────────────────────────────────────────────── --}}
@include('partials.dashboard-widgets-style')

<div class="section-header">
    <div class="section-icon"><i class="bi bi-person-plus"></i></div>
    <h6>Onboarding Overview</h6>
</div>
<div class="row g-3 mb-4">

    @php
        $obYtdCompanyNames = collect($onboardingsByCompany)->pluck('company')->filter()->unique()->sort()->values();
    @endphp

    {{-- 1. Total Onboard Year to Date --}}
    <div class="col-md-6">
        <div class="card dash-widget h-100" id="card-ob-ytd">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-person-plus-fill"></i></div>
                    <div class="flex-grow-1">
                        <div class="widget-number" data-total="{{ $onboardStats['total_onboardings_ytd'] }}">{{ $onboardStats['total_onboardings_ytd'] }}</div>
                        <div class="widget-label">Onboarded YTD</div>
                    </div>
                    <div class="widget-filter">
                        <select class="form-select form-select-sm ob-card-filter" data-card="ob-ytd">
                            <option value="">All</option>
                            @foreach($obYtdCompanyNames as $name)
                            <option value="{{ $name }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="widget-body flex-fill">
                <div class="breakdown-title">By Company</div>
                @forelse($onboardingsByCompany as $row)
                <div class="breakdown-row ob-filter-row" data-company="{{ $row->company }}">
                    <span>{{ $row->company }}</span>
                    <span class="breakdown-badge">{{ $row->total }}</span>
                </div>
                @empty
                <div class="text-center py-4">
                    <div style="width:44px;height:44px;background:var(--primary-tint);border:1px solid rgba(17,118,106,0.14);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                        <i class="bi bi-person-plus" style="font-size:18px;color:var(--primary-dark);"></i>
                    </div>
                    <div style="font-family:var(--sans);font-size:13px;color:var(--mute);">No data yet</div>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    @php
        $obAllCompanyNames = collect($newJoinersByCompany)->pluck('company')->filter()->unique()->sort()->values();
    @endphp

    {{-- 2. New Joiners This Month --}}
    <div class="col-md-6">
        <div class="card dash-widget h-100" id="card-ob-joiners">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-calendar-plus-fill"></i></div>
                    <div class="flex-grow-1">
                        <div class="widget-number" data-total="{{ $onboardStats['new_joiners_this_month'] }}">{{ $onboardStats['new_joiners_this_month'] }}</div>
                        <div class="widget-label">New Joiners This Month</div>
                    </div>
                    <div class="widget-filter">
                        <select class="form-select form-select-sm ob-card-filter" data-card="ob-joiners">
                            <option value="">All</option>
                            @foreach($obAllCompanyNames as $name)
                            <option value="{{ $name }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="widget-body flex-fill">
                <div class="breakdown-title">By Company</div>
                @forelse($newJoinersByCompany as $row)
                <div class="breakdown-row ob-filter-row" data-company="{{ $row->company }}">
                    <span>{{ $row->company }}</span>
                    <span class="breakdown-badge">{{ $row->total }}</span>
                </div>
                @empty
                <div class="text-center py-4 ob-empty">
                    <div style="width:44px;height:44px;background:var(--primary-tint);border:1px solid rgba(17,118,106,0.14);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                        <i class="bi bi-calendar-plus" style="font-size:18px;color:var(--primary-dark);"></i>
                    </div>
                    <div style="font-family:var(--sans);font-size:13px;color:var(--mute);">No new joiners this month</div>
                </div>
                @endforelse
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function filterObCard(cardKey, company) {
    var card = document.getElementById('card-' + cardKey);
    if (!card) return;
    var numberEl = card.querySelector('.widget-number');
    var rows = card.querySelectorAll('.ob-filter-row');
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

document.querySelectorAll('.ob-card-filter').forEach(function(sel) {
    sel.addEventListener('change', function() {
        filterObCard(this.dataset.card, this.value);
    });
});
</script>
@endpush
