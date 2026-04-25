{{-- On Leave This Week Widget --}}
@php
    $user = Auth::user();
    $isAdmin = $user->isHr() || $user->isSuperadmin() || $user->isSystemAdmin() || $user->isIt();
    $companyFilter = $isAdmin ? null : $user->employee?->company;
    $onLeaveData = \App\Http\Controllers\LeaveController::getOnLeaveThisWeek($companyFilter);
    $weekStart = now()->startOfWeek();
    $weekEnd = now()->endOfWeek();
    $totalOnLeave = collect($onLeaveData)->sum(fn($d) => count($d['leaves']));
@endphp

<div class="section-header">
    <h6>On Leave This Week</h6>
</div>

<div class="row g-3 mb-4">
<div class="col-12">
<div class="card dash-widget" style="min-height:auto;">
    <div class="widget-header">
        <div class="d-flex align-items-center gap-3">
            <div class="widget-icon"><i class="bi bi-calendar2-week-fill"></i></div>
            <div class="flex-grow-1">
                <div class="widget-number" style="font-size:18px;font-weight:600;">On Leave This Week</div>
                <div class="widget-label">{{ $weekStart->format('d/m/Y') }} — {{ $weekEnd->format('d/m/Y') }}</div>
            </div>
            @if($isAdmin)
            <span class="widget-context">
                <i class="bi bi-globe"></i>All Companies
            </span>
            @elseif($companyFilter)
            <span class="widget-context">
                <i class="bi bi-building"></i>{{ $companyFilter }}
            </span>
            @endif
        </div>
    </div>
    <div class="widget-body" style="padding:0;">
        @if(empty($onLeaveData))
        <div class="text-center py-4">
            <div style="width:44px;height:44px;background:var(--primary-tint);border:1px solid rgba(17,118,106,0.14);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                <i class="bi bi-emoji-smile" style="font-size:18px;color:var(--primary-dark);"></i>
            </div>
            <div style="font-family:var(--sans);font-size:13px;color:var(--mute);">No one is on leave this week</div>
        </div>
        @else
        <div class="table-responsive">
            <table class="table align-middle mb-0" style="border-color:var(--line-soft);">
                <thead>
                    <tr style="background:var(--bg);">
                        <th style="width:140px;font-family:var(--mono);font-size:10.5px;font-weight:500;text-transform:uppercase;letter-spacing:.12em;color:var(--primary-dark);padding:12px 22px;border-bottom:1px solid var(--line-soft);">Day</th>
                        <th style="font-family:var(--mono);font-size:10.5px;font-weight:500;text-transform:uppercase;letter-spacing:.12em;color:var(--primary-dark);padding:12px 22px;border-bottom:1px solid var(--line-soft);">Who's On Leave</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($onLeaveData as $day)
                    <tr @if($day['is_today']) style="background:var(--primary-tint);" @endif>
                        <td style="padding:12px 22px;border-color:var(--line-soft);">
                            <div style="font-family:var(--sans);font-weight:600;font-size:13px;color:{{ $day['is_today'] ? 'var(--primary-dark)' : 'var(--ink)' }};letter-spacing:-0.005em;">
                                {{ $day['day_name'] }}
                                @if($day['is_today'])
                                <span class="ms-1" style="font-family:var(--mono);background:var(--primary-dark);color:#fff;font-size:9px;font-weight:500;vertical-align:middle;padding:2px 8px;border-radius:999px;text-transform:uppercase;letter-spacing:.1em;">Today</span>
                                @endif
                            </div>
                            <div style="font-family:var(--mono);font-size:10px;color:var(--mute);text-transform:uppercase;letter-spacing:.1em;margin-top:2px;">{{ $day['date_formatted'] }}</div>
                        </td>
                        <td style="padding:12px 22px;border-color:var(--line-soft);">
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($day['leaves'] as $leave)
                                <span class="d-inline-flex align-items-center gap-1 px-2 py-1" style="font-family:var(--sans);font-size:11.5px;background:var(--surface);border:1px solid var(--line-soft);border-radius:999px;color:var(--ink-2);">
                                    <i class="bi bi-person-fill" style="font-size:10px;color:var(--primary-dark);"></i>
                                    <strong style="font-weight:600;color:var(--ink);">{{ $leave['employee_name'] }}</strong>
                                    <span style="color:var(--mute);">· {{ $leave['leave_type'] }}</span>
                                    @if($leave['is_half_day'])
                                    <span style="color:var(--primary-dark);">({{ ucfirst($leave['half_day_period']) }})</span>
                                    @endif
                                    @if($isAdmin && $leave['company'])
                                    <span style="color:var(--mute);">[{{ $leave['company'] }}]</span>
                                    @endif
                                </span>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
</div>
</div>
