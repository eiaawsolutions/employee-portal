{{-- ── ASSET OVERVIEW ──────────────────────────────────────────────────── --}}
@include('partials.dashboard-widgets-style')

@php
    $typeLabels = [
        'laptop'      => 'Laptop',
        'monitor'     => 'Monitor',
        'converter'   => 'Converter',
        'phone'       => 'Company Phone',
        'sim_card'    => 'SIM Card',
        'access_card' => 'Access Card',
        'petty_cash'  => 'Petty Cash',
        'accessories' => 'Accessories',
        'furniture'   => 'Furniture',
        'equipment'   => 'Equipment',
        'other'       => 'Other',
    ];
    $formatType = fn(string $t) => $typeLabels[$t] ?? ucfirst(str_replace('_', ' ', $t));
@endphp

<div class="section-header">
    <div class="section-icon"><i class="bi bi-laptop"></i></div>
    <h6>Asset Overview</h6>
</div>
<div class="row g-3 mb-4">

    {{-- ═══ Card 1: Overall Assets — by type, filterable by company ═══ --}}
    <div class="col-md-4">
        <div class="card dash-widget h-100">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-laptop-fill"></i></div>
                    <div>
                        <div class="widget-number" id="card1Total">{{ $overviewAllTotal }}</div>
                        <div class="widget-label">Overall Assets</div>
                    </div>
                    <div class="ms-auto widget-filter">
                        <select id="card1Filter">
                            <option value="">All Companies</option>
                            @foreach($overviewAllByCompany as $co => $types)
                            <option value="{{ $co }}">{{ $co }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="widget-body flex-fill" id="card1Body" style="max-height:360px;overflow-y:auto;">
                {{-- Default: all types --}}
                @forelse($overviewAllByType as $type => $count)
                <div class="breakdown-row">
                    <span>{{ $formatType($type) }}</span>
                    <span class="breakdown-badge">{{ $count }}</span>
                </div>
                @empty
                <div class="text-center py-4">
                    <div style="width:44px;height:44px;background:var(--primary-tint);border:1px solid rgba(17,118,106,0.14);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                        <i class="bi bi-laptop" style="font-size:18px;color:var(--primary-dark);"></i>
                    </div>
                    <div style="font-family:var(--sans);font-size:13px;color:var(--mute);">No assets</div>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ═══ Card 2: Company Owned — by type, filterable by company ═══ --}}
    <div class="col-md-4">
        <div class="card dash-widget h-100">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-building-fill"></i></div>
                    <div>
                        <div class="widget-number" id="card2Total">{{ $overviewCompanyTotal }}</div>
                        <div class="widget-label">Company Owned</div>
                    </div>
                    <div class="ms-auto widget-filter">
                        <select id="card2Filter">
                            <option value="">All Companies</option>
                            @foreach($overviewCompanyByCompany as $co => $types)
                            <option value="{{ $co }}">{{ $co }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="widget-body flex-fill" id="card2Body" style="max-height:360px;overflow-y:auto;">
                @forelse($overviewCompanyByType as $type => $count)
                <div class="breakdown-row">
                    <span>{{ $formatType($type) }}</span>
                    <span class="breakdown-badge">{{ $count }}</span>
                </div>
                @empty
                <div class="text-center py-4">
                    <div style="width:44px;height:44px;background:var(--primary-tint);border:1px solid rgba(17,118,106,0.14);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                        <i class="bi bi-building" style="font-size:18px;color:var(--primary-dark);"></i>
                    </div>
                    <div style="font-family:var(--sans);font-size:13px;color:var(--mute);">No company-owned assets</div>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ═══ Card 3: Rental / Leased — by company → type → brand ═══ --}}
    <div class="col-md-4">
        <div class="card dash-widget h-100">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-truck-front-fill"></i></div>
                    <div>
                        <div class="widget-number">{{ $overviewRentalTotal }}</div>
                        <div class="widget-label">Rental / Leased</div>
                    </div>
                </div>
            </div>
            <div class="widget-body flex-fill" style="max-height:420px;overflow-y:auto;">
                @forelse($overviewRentalByCompany as $company => $data)
                <div class="breakdown-title {{ !$loop->first ? 'mt-3' : '' }}">
                    <i class="bi bi-building me-1"></i>{{ $company }}
                    <span class="float-end fw-bold" style="color:var(--primary-dark);">{{ $data['total'] }}</span>
                </div>
                @foreach($data['types'] as $type => $typeData)
                <div class="breakdown-row" style="font-weight:600;">
                    <span>{{ $formatType($type) }}</span>
                    <span class="breakdown-badge">{{ $typeData['total'] }}</span>
                </div>
                @foreach($typeData['brands'] as $brand => $brandCount)
                <div class="breakdown-row" style="padding-left:18px;font-size:11.5px;color:var(--mute);">
                    <span><i class="bi bi-dot"></i>{{ $brand }}</span>
                    <span class="breakdown-badge" style="font-size:10px;padding:2px 8px;background:var(--bg-warm);color:var(--ink-2);border-color:var(--line);">{{ $brandCount }}</span>
                </div>
                @endforeach
                @endforeach
                @empty
                <div class="text-center py-4">
                    <div style="width:44px;height:44px;background:var(--primary-tint);border:1px solid rgba(17,118,106,0.14);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                        <i class="bi bi-truck-front" style="font-size:18px;color:var(--primary-dark);"></i>
                    </div>
                    <div style="font-family:var(--sans);font-size:13px;color:var(--mute);">No rental assets</div>
                </div>
                @endforelse
            </div>
        </div>
    </div>

</div>

{{-- ── Card 1 & 2 filter JS (inside @push so it gets the CSP nonce) ── --}}
@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    var typeLabels = @json($typeLabels);
    function formatType(t) {
        return typeLabels[t] || t.replace(/_/g, ' ').replace(/\b\w/g, function(c){ return c.toUpperCase(); });
    }

    // Card 1 data
    var card1All  = @json($overviewAllByType);
    var card1Map  = @json($overviewAllByCompany);
    var card1Grand = {{ $overviewAllTotal }};

    // Card 2 data
    var card2All  = @json($overviewCompanyByType);
    var card2Map  = @json($overviewCompanyByCompany);
    var card2Grand = {{ $overviewCompanyTotal }};

    function renderTypeList(container, data, _badgeColor, total) {
        var totalEl = container.closest('.dash-widget').querySelector('.widget-number');
        if (totalEl) totalEl.textContent = total;

        var keys = Object.keys(data);
        if (!keys.length) {
            container.innerHTML = '<div class="text-center py-4">'
                + '<div style="width:44px;height:44px;background:var(--primary-tint);border:1px solid rgba(17,118,106,0.14);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">'
                + '<i class="bi bi-laptop" style="font-size:18px;color:var(--primary-dark);"></i></div>'
                + '<div style="font-family:var(--sans);font-size:13px;color:var(--mute);">No assets</div></div>';
            return;
        }
        // Sort by count desc
        keys.sort(function(a, b) { return data[b] - data[a]; });
        var html = '';
        keys.forEach(function(type) {
            html += '<div class="breakdown-row">'
                  + '<span>' + formatType(type) + '</span>'
                  + '<span class="breakdown-badge">' + data[type] + '</span>'
                  + '</div>';
        });
        container.innerHTML = html;
    }

    // Card 1 filter
    var card1Filter = document.getElementById('card1Filter');
    var card1Body   = document.getElementById('card1Body');
    if (card1Filter && card1Body) {
        card1Filter.addEventListener('change', function () {
            var co = this.value;
            if (!co) {
                renderTypeList(card1Body, card1All, null, card1Grand);
            } else {
                var types = card1Map[co] || {};
                var coTotal = 0;
                for (var t in types) coTotal += types[t];
                renderTypeList(card1Body, types, null, coTotal);
            }
        });
    }

    // Card 2 filter
    var card2Filter = document.getElementById('card2Filter');
    var card2Body   = document.getElementById('card2Body');
    if (card2Filter && card2Body) {
        card2Filter.addEventListener('change', function () {
            var co = this.value;
            if (!co) {
                renderTypeList(card2Body, card2All, null, card2Grand);
            } else {
                var types = card2Map[co] || {};
                var coTotal = 0;
                for (var t in types) coTotal += types[t];
                renderTypeList(card2Body, types, null, coTotal);
            }
        });
    }
})();
</script>
@endpush
