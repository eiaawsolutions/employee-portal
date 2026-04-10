{{-- ── ONBOARDING OVERVIEW ──────────────────────────────────────────────── --}}
@include('partials.dashboard-widgets-style')

<div class="section-header">
    <div class="section-icon" style="background:#eff6ff;">
        <i class="bi bi-person-plus" style="font-size:16px;color:#2563eb;"></i>
    </div>
    <h6>Onboarding Overview</h6>
</div>
<div class="row g-3 mb-4">

    {{-- 1. Total Onboard Year to Date --}}
    <div class="col-md-4">
        <div class="card dash-widget h-100">
            <div class="widget-header" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-person-plus-fill"></i></div>
                    <div>
                        <div class="widget-number">{{ $onboardStats['total_onboardings_ytd'] }}</div>
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

    @php
        $obAllCompanyNames = collect($newJoinersByCompany)->pluck('company')
            ->merge(collect($exitingByCompany)->pluck('company'))
            ->filter()->unique()->sort()->values();
    @endphp

    {{-- 2. New Joiners This Month --}}
    <div class="col-md-4">
        <div class="card dash-widget h-100" id="card-ob-joiners">
            <div class="widget-header" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
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
                    <span class="breakdown-badge" style="background:#f59e0b;">{{ $row->total }}</span>
                </div>
                @empty
                <div class="text-muted small text-center py-2 ob-empty">No new joiners this month</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- 3. Exiting This Month --}}
    <div class="col-md-4">
        <div class="card dash-widget h-100" id="card-ob-exiting">
            <div class="widget-header" style="background:linear-gradient(135deg,#ef4444,#b91c1c);">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-calendar-x-fill"></i></div>
                    <div class="flex-grow-1">
                        <div class="widget-number" data-total="{{ $onboardStats['exiting_this_month'] }}">{{ $onboardStats['exiting_this_month'] }}</div>
                        <div class="widget-label">Exiting This Month</div>
                    </div>
                    <div class="widget-filter">
                        <select class="form-select form-select-sm ob-card-filter" data-card="ob-exiting">
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
                @forelse($exitingByCompany as $row)
                <div class="breakdown-row ob-filter-row" data-company="{{ $row->company ?? 'Unknown' }}">
                    <span>{{ $row->company ?? 'Unknown' }}</span>
                    <span class="breakdown-badge" style="background:#ef4444;">{{ $row->total }}</span>
                </div>
                @empty
                <div class="text-muted small text-center py-2 ob-empty">No exits this month</div>
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
