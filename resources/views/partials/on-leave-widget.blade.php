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
    <div class="section-icon" style="background:#ede9fe;">
        <i class="bi bi-calendar2-week-fill" style="font-size:16px;color:#8b5cf6;"></i>
    </div>
    <h6>On Leave This Week</h6>
</div>

<div class="row g-3 mb-4">
<div class="col-12">
<div class="card dash-widget" style="min-height:auto;">
    <div class="widget-header" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);padding:16px 22px 12px;">
        <div class="d-flex align-items-center gap-3">
            <div class="widget-icon"><i class="bi bi-calendar2-week-fill"></i></div>
            <div class="flex-grow-1">
                <div class="widget-label" style="font-size:13px;font-weight:600;">On Leave This Week</div>
                <small style="color:rgba(255,255,255,.65);font-size:11px;">{{ $weekStart->format('d/m/Y') }} — {{ $weekEnd->format('d/m/Y') }}</small>
            </div>
            @if($isAdmin)
            <span style="font-size:10px;font-weight:600;padding:4px 12px;border-radius:20px;background:rgba(255,255,255,.18);color:#fff;">
                <i class="bi bi-globe me-1"></i>All Companies
            </span>
            @elseif($companyFilter)
            <span style="font-size:10px;font-weight:600;padding:4px 12px;border-radius:20px;background:rgba(255,255,255,.18);color:#fff;">
                <i class="bi bi-building me-1"></i>{{ $companyFilter }}
            </span>
            @endif
        </div>
    </div>
    <div class="widget-body" style="padding:0;">
        @if(empty($onLeaveData))
        <div class="text-center py-4 text-muted">
            <i class="bi bi-emoji-smile" style="font-size:28px;color:#8b5cf6;"></i>
            <div class="mt-2 small">No one is on leave this week</div>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr style="background:#faf5ff;">
                        <th style="width:130px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#7c3aed;padding:10px 16px;">Day</th>
                        <th style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#7c3aed;padding:10px 16px;">Who's On Leave</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($onLeaveData as $day)
                    <tr @if($day['is_today']) style="background:#f5f3ff;" @endif>
                        <td style="padding:10px 16px;">
                            <div class="fw-semibold {{ $day['is_today'] ? 'text-primary' : '' }}" style="font-size:12.5px;">
                                {{ $day['day_name'] }}
                                @if($day['is_today'])
                                <span class="badge" style="background:#8b5cf6;font-size:9px;vertical-align:middle;">TODAY</span>
                                @endif
                            </div>
                            <small class="text-muted" style="font-size:11px;">{{ $day['date_formatted'] }}</small>
                        </td>
                        <td style="padding:10px 16px;">
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($day['leaves'] as $leave)
                                <span class="d-inline-flex align-items-center gap-1 px-2 py-1 rounded-pill" style="font-size:11px;background:#f5f3ff;border:1px solid #e9d5ff;">
                                    <i class="bi bi-person-fill" style="font-size:10px;color:#8b5cf6;"></i>
                                    <strong>{{ $leave['employee_name'] }}</strong>
                                    <span class="text-muted">· {{ $leave['leave_type'] }}</span>
                                    @if($leave['is_half_day'])
                                    <span style="color:#7c3aed;">({{ ucfirst($leave['half_day_period']) }})</span>
                                    @endif
                                    @if($isAdmin && $leave['company'])
                                    <span class="text-secondary">[{{ $leave['company'] }}]</span>
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
