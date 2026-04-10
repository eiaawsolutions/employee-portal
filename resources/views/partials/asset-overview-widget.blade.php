{{-- ── ASSET OVERVIEW ──────────────────────────────────────────────────── --}}
@include('partials.dashboard-widgets-style')

<div class="section-header">
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
                        <div class="widget-number">{{ $assetOverview['total_assets'] }}</div>
                        <div class="widget-label">Overall Assets</div>
                    </div>
                    <div class="ms-auto status-pills">
                        <span class="status-pill" style="background:rgba(255,255,255,.2);color:#fff;">{{ $assetOverview['available'] }} Available</span>
                        <span class="status-pill" style="background:rgba(255,255,255,.2);color:#fff;">{{ $assetOverview['assigned'] }} Assigned</span>
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

                <div class="breakdown-title mt-3">By Company Supplied To</div>
                @forelse($rentalBySuppliedTo as $row)
                <div class="breakdown-row">
                    <span>{{ $row->company }}</span>
                    <span class="breakdown-badge" style="background:#ea580c;">{{ $row->total }}</span>
                </div>
                @empty
                <div class="text-muted small text-center py-2">No data</div>
                @endforelse
            </div>
        </div>
    </div>

</div>
