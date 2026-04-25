{{-- ── BIRTHDAY BABIES OF THE MONTH ───────────────────────────────────── --}}
@include('partials.dashboard-widgets-style')

@php
    $currentMonth = \Carbon\Carbon::now()->format('F');
    $bdayCount = $birthdayBabies->count();
@endphp

<div class="section-header">
    <h6>Birthday Babies &mdash; {{ $currentMonth }}</h6>
</div>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card dash-widget" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-balloon-heart-fill"></i></div>
                    <div>
                        <div class="widget-number">{{ $bdayCount }}</div>
                        <div class="widget-label">{{ $bdayCount === 1 ? 'Birthday' : 'Birthdays' }} this month</div>
                    </div>
                </div>
            </div>
            <div class="widget-body">
                @if($birthdayBabies->isEmpty())
                    <div class="text-center py-4">
                        <div style="width:44px;height:44px;background:var(--primary-tint);border:1px solid rgba(17,118,106,0.14);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                            <i class="bi bi-balloon-heart" style="font-size:18px;color:var(--primary-dark);"></i>
                        </div>
                        <div style="font-family:var(--sans);font-size:13px;color:var(--mute);">No birthdays this month</div>
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
                            <div class="d-flex align-items-center gap-3 p-2 rounded-3 bday-row"
                                 style="background:{{ $isToday ? 'var(--primary-tint)' : 'var(--bg)' }};border:1px solid {{ $isToday ? 'rgba(17,118,106,0.22)' : 'var(--line-soft)' }};transition:all .35s var(--ease);">
                                <div style="width:40px;height:40px;background:{{ $isToday ? 'var(--gradient)' : 'var(--surface)' }};border:1px solid {{ $isToday ? 'transparent' : 'var(--line-soft)' }};border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    @if($isToday)
                                        <i class="bi bi-balloon-heart-fill" style="font-size:16px;color:#fff;"></i>
                                    @else
                                        <span style="font-family:var(--sans);font-size:14px;font-weight:700;color:var(--primary-dark);letter-spacing:-0.02em;">{{ $day }}</span>
                                    @endif
                                </div>
                                <div style="min-width:0;">
                                    <div class="text-truncate" style="font-family:var(--sans);font-weight:600;font-size:13px;color:var(--ink);letter-spacing:-0.005em;">
                                        {{ $displayName }}
                                        @if($isToday)
                                            <span class="ms-1" style="font-family:var(--mono);background:var(--primary-dark);color:#fff;font-size:9px;font-weight:500;vertical-align:middle;padding:2px 8px;border-radius:999px;text-transform:uppercase;letter-spacing:.1em;">Today</span>
                                        @endif
                                    </div>
                                    <div class="text-truncate" style="font-family:var(--sans);font-size:11.5px;color:var(--mute);">
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

<style>
    .bday-row:hover {
        border-color: rgba(17,118,106,0.28) !important;
        background: var(--primary-tint) !important;
        transform: translateY(-1px);
    }
</style>
