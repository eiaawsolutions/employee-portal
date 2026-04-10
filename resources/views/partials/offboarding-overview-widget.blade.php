{{-- ── OFFBOARDING OVERVIEW ─────────────────────────────────────────────── --}}
@include('partials.dashboard-widgets-style')

<div class="section-header">
    <div class="section-icon" style="background:#fef2f2;">
        <i class="bi bi-box-arrow-right" style="font-size:16px;color:#dc2626;"></i>
    </div>
    <h6>Offboarding Overview</h6>
</div>
<div class="row g-3 mb-4">

    @php
        $offbAllCompanyNames = collect($exitingByCompany)->pluck('company')
            ->merge(collect($offboardedYtdByCompany)->pluck('company'))
            ->filter()->unique()->sort()->values();
    @endphp

    {{-- 1. Offboarded Year to Date --}}
    <div class="col-md-6">
        <div class="card dash-widget h-100">
            <div class="widget-header" style="background:linear-gradient(135deg,#6366f1,#4338ca);">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-person-dash-fill"></i></div>
                    <div>
                        <div class="widget-number">{{ $offboardStats['offboarded_ytd'] }}</div>
                        <div class="widget-label">Offboarded YTD</div>
                    </div>
                </div>
            </div>
            <div class="widget-body flex-fill">
                <div class="breakdown-title">By Company</div>
                @forelse($offboardedYtdByCompany as $row)
                <div class="breakdown-row">
                    <span>{{ $row->company }}</span>
                    <span class="breakdown-badge" style="background:#6366f1;">{{ $row->total }}</span>
                </div>
                @empty
                <div class="text-muted small text-center py-2">No data yet</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- 2. Exiting This Month --}}
    <div class="col-md-6">
        <div class="card dash-widget h-100" id="card-offb-exiting">
            <div class="widget-header" style="background:linear-gradient(135deg,#ef4444,#b91c1c);">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-calendar-x-fill"></i></div>
                    <div class="flex-grow-1">
                        <div class="widget-number" data-total="{{ $offboardStats['exiting_this_month'] }}">{{ $offboardStats['exiting_this_month'] }}</div>
                        <div class="widget-label">Exiting This Month</div>
                    </div>
                    <div class="widget-filter">
                        <select class="form-select form-select-sm offb-card-filter" data-card="offb-exiting">
                            <option value="">All</option>
                            @foreach($offbAllCompanyNames as $name)
                            <option value="{{ $name }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="widget-body flex-fill">
                <div class="breakdown-title">By Company</div>
                @forelse($exitingByCompany as $row)
                <div class="breakdown-row offb-filter-row" data-company="{{ $row->company ?? 'Unknown' }}">
                    <span>{{ $row->company ?? 'Unknown' }}</span>
                    <span class="breakdown-badge" style="background:#ef4444;">{{ $row->total }}</span>
                </div>
                @empty
                <div class="text-muted small text-center py-2">No exits this month</div>
                @endforelse
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function filterOffbCard(cardKey, company) {
    var card = document.getElementById('card-' + cardKey);
    if (!card) return;
    var numberEl = card.querySelector('.widget-number');
    var rows = card.querySelectorAll('.offb-filter-row');
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

document.querySelectorAll('.offb-card-filter').forEach(function(sel) {
    sel.addEventListener('change', function() {
        filterOffbCard(this.dataset.card, this.value);
    });
});
</script>
@endpush
