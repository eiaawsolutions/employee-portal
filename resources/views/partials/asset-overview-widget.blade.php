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
    <div class="section-icon" style="background:#f0fdf4;">
        <i class="bi bi-laptop" style="font-size:16px;color:#16a34a;"></i>
    </div>
    <h6>Asset Overview</h6>
</div>
<div class="row g-3 mb-4">

    {{-- Card 1: Overall Assets — all assets by company → type --}}
    <div class="col-md-4">
        <div class="card dash-widget h-100">
            <div class="widget-header" style="background:linear-gradient(135deg,#6366f1,#4338ca);">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-laptop-fill"></i></div>
                    <div>
                        <div class="widget-number">{{ $overviewAllTotal }}</div>
                        <div class="widget-label">Overall Assets</div>
                    </div>
                </div>
            </div>
            <div class="widget-body flex-fill" style="max-height:360px;overflow-y:auto;">
                @forelse($overviewAllByCompany as $company => $data)
                <div class="breakdown-title {{ !$loop->first ? 'mt-3' : '' }}">
                    <i class="bi bi-building me-1"></i>{{ $company }}
                    <span class="float-end fw-bold" style="color:#6366f1;">{{ $data['total'] }}</span>
                </div>
                @foreach($data['types'] as $type => $count)
                <div class="breakdown-row">
                    <span>{{ $formatType($type) }}</span>
                    <span class="breakdown-badge" style="background:#6366f1;">{{ $count }}</span>
                </div>
                @endforeach
                @empty
                <div class="text-muted small text-center py-2">No assets</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Card 2: Company Owned — company-owned assets by company → type --}}
    <div class="col-md-4">
        <div class="card dash-widget h-100">
            <div class="widget-header" style="background:linear-gradient(135deg,#14b8a6,#0f766e);">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-building-fill"></i></div>
                    <div>
                        <div class="widget-number">{{ $overviewCompanyTotal }}</div>
                        <div class="widget-label">Company Owned</div>
                    </div>
                </div>
            </div>
            <div class="widget-body flex-fill" style="max-height:360px;overflow-y:auto;">
                @forelse($overviewCompanyByCompany as $company => $data)
                <div class="breakdown-title {{ !$loop->first ? 'mt-3' : '' }}">
                    <i class="bi bi-building me-1"></i>{{ $company }}
                    <span class="float-end fw-bold" style="color:#14b8a6;">{{ $data['total'] }}</span>
                </div>
                @foreach($data['types'] as $type => $count)
                <div class="breakdown-row">
                    <span>{{ $formatType($type) }}</span>
                    <span class="breakdown-badge" style="background:#14b8a6;">{{ $count }}</span>
                </div>
                @endforeach
                @empty
                <div class="text-muted small text-center py-2">No company-owned assets</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Card 3: Rental / Leased — rental assets by company → type --}}
    <div class="col-md-4">
        <div class="card dash-widget h-100">
            <div class="widget-header" style="background:linear-gradient(135deg,#f97316,#c2410c);">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-truck-front-fill"></i></div>
                    <div>
                        <div class="widget-number">{{ $overviewRentalTotal }}</div>
                        <div class="widget-label">Rental / Leased</div>
                    </div>
                </div>
            </div>
            <div class="widget-body flex-fill" style="max-height:360px;overflow-y:auto;">
                @forelse($overviewRentalByCompany as $company => $data)
                <div class="breakdown-title {{ !$loop->first ? 'mt-3' : '' }}">
                    <i class="bi bi-building me-1"></i>{{ $company }}
                    <span class="float-end fw-bold" style="color:#f97316;">{{ $data['total'] }}</span>
                </div>
                @foreach($data['types'] as $type => $count)
                <div class="breakdown-row">
                    <span>{{ $formatType($type) }}</span>
                    <span class="breakdown-badge" style="background:#f97316;">{{ $count }}</span>
                </div>
                @endforeach
                @empty
                <div class="text-muted small text-center py-2">No rental assets</div>
                @endforelse
            </div>
        </div>
    </div>

</div>
