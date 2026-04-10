{{-- ── BIRTHDAY BABIES OF THE MONTH ───────────────────────────────────── --}}
@include('partials.dashboard-widgets-style')

@php
    $currentMonth = \Carbon\Carbon::now()->format('F');
    $bdayCount = $birthdayBabies->count();
@endphp

<div class="section-header">
    <div class="section-icon" style="background:#fce7f3;">
        <i class="bi bi-balloon-heart-fill" style="font-size:16px;color:#ec4899;"></i>
    </div>
    <h6>Birthday Babies &mdash; {{ $currentMonth }}</h6>
</div>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card dash-widget" style="min-height:auto;">
            <div class="widget-header" style="background:linear-gradient(135deg,#ec4899,#be185d);padding:16px 22px 12px;">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-balloon-heart-fill"></i></div>
                    <div>
                        <div class="widget-number" style="font-size:28px;">{{ $bdayCount }}</div>
                        <div class="widget-label">{{ $bdayCount === 1 ? 'Birthday' : 'Birthdays' }} this month</div>
                    </div>
                </div>
            </div>
            <div class="widget-body" style="padding:16px 22px;">
                @if($birthdayBabies->isEmpty())
                    <div class="text-center py-3">
                        <div class="text-muted small">No birthdays this month</div>
                    </div>
                @else
                    <div class="row g-2">
                        @foreach($birthdayBabies as $baby)
                        @php
                            $day = \Carbon\Carbon::parse($baby->date_of_birth)->day;
                            $displayName = $baby->preferred_name ?: $baby->full_name;
                            $isToday = \Carbon\Carbon::parse($baby->date_of_birth)->day === \Carbon\Carbon::now()->day
                                    && \Carbon\Carbon::parse($baby->date_of_birth)->month === \Carbon\Carbon::now()->month;
                        @endphp
                        <div class="col-md-6 col-lg-4">
                            <div class="d-flex align-items-center gap-3 p-2 rounded-3"
                                 style="background:{{ $isToday ? '#fdf2f8' : '#f8fafc' }};border:1px solid {{ $isToday ? '#f9a8d4' : '#f1f5f9' }};transition:all .15s ease;"
                                 onmouseenter="this.style.background='#fdf2f8';this.style.borderColor='#f9a8d4'"
                                 onmouseleave="this.style.background='{{ $isToday ? '#fdf2f8' : '#f8fafc' }}';this.style.borderColor='{{ $isToday ? '#f9a8d4' : '#f1f5f9' }}'">
                                <div style="width:40px;height:40px;background:{{ $isToday ? 'linear-gradient(135deg,#ec4899,#be185d)' : '#fce7f3' }};border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    @if($isToday)
                                        <i class="bi bi-balloon-heart-fill" style="font-size:18px;color:#fff;"></i>
                                    @else
                                        <span style="font-size:14px;font-weight:800;color:#ec4899;">{{ $day }}</span>
                                    @endif
                                </div>
                                <div style="min-width:0;">
                                    <div class="fw-semibold text-truncate" style="font-size:13px;color:#1e293b;">
                                        {{ $displayName }}
                                        @if($isToday)
                                            <span class="badge ms-1" style="background:linear-gradient(135deg,#ec4899,#be185d);font-size:9px;vertical-align:middle;padding:3px 8px;border-radius:10px;">Today!</span>
                                        @endif
                                    </div>
                                    <div class="text-truncate" style="font-size:11px;color:#64748b;">
                                        {{ $baby->designation }}{{ $baby->company ? ' · '.$baby->company : '' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
