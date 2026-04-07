@extends('layouts.app')
@section('title', 'My Leave')
@section('page-title', 'My Leave')

@section('content')

{{-- ── Balance Cards ──────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <h6 class="mb-0 text-muted"><i class="bi bi-pie-chart me-1"></i>Leave Balance — {{ now()->year }}</h6>
    <div class="d-flex gap-2" style="font-size:11px;">
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#0d6efd;"></span> Taken</span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#ffc107;"></span> Pending</span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#e9ecef;"></span> Remaining</span>
    </div>
</div>

@if($balances->isEmpty())
<div class="card mb-4">
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-info-circle" style="font-size:2rem"></i>
        <p class="mt-2 mb-0">No leave balances have been initialised for {{ now()->year }}.<br>Please contact HR to set up your entitlements.</p>
    </div>
</div>
@else
<div class="row g-3 mb-4">
    @foreach($balances as $bal)
    @php
        $pending  = $pendingByType[$bal->leave_type_id] ?? 0;
        $total    = (float) $bal->entitled + (float) $bal->carry_forward + (float) $bal->adjustment;
        $taken    = (float) $bal->taken;
        $avail    = (float) $bal->available;
        $usedPct  = $total > 0 ? min(round($taken / $total * 100), 100) : 0;
        $pendPct  = $total > 0 ? min(round($pending / $total * 100), 100 - $usedPct) : 0;
        $availPct = max(0, 100 - $usedPct - $pendPct);

        // Ring colour based on usage
        if ($total == 0)       $ringColor = '#dee2e6';
        elseif ($usedPct >= 90) $ringColor = '#dc3545';
        elseif ($usedPct >= 60) $ringColor = '#fd7e14';
        else                    $ringColor = '#0d6efd';

        $availColor = $avail > 0 ? '#198754' : '#dc3545';
    @endphp
    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
        <div class="card h-100 border-0 shadow-sm text-center p-3" style="border-radius:14px;">
            {{-- Type label --}}
            <div class="d-flex align-items-center justify-content-center gap-1 mb-2">
                <span class="fw-semibold" style="font-size:13px;">{{ $bal->leaveType->name ?? '' }}</span>
                <span class="badge rounded-pill text-bg-light" style="font-size:10px;">{{ $bal->leaveType->code ?? '' }}</span>
            </div>

            {{-- Donut ring --}}
            <div class="mx-auto mb-2" style="position:relative;width:80px;height:80px;">
                <svg viewBox="0 0 36 36" style="transform:rotate(-90deg);width:80px;height:80px;">
                    {{-- Background --}}
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="#e9ecef" stroke-width="3.5"/>
                    {{-- Taken --}}
                    @if($usedPct > 0)
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="{{ $ringColor }}" stroke-width="3.5"
                        stroke-dasharray="{{ $usedPct }} {{ 100 - $usedPct }}" stroke-dashoffset="0"/>
                    @endif
                    {{-- Pending --}}
                    @if($pendPct > 0)
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="#ffc107" stroke-width="3.5"
                        stroke-dasharray="{{ $pendPct }} {{ 100 - $pendPct }}"
                        stroke-dashoffset="{{ -(100 - (100 - $usedPct)) }}"/>
                    @endif
                </svg>
                {{-- Centre text --}}
                <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);line-height:1;">
                    <div class="fw-bold" style="font-size:18px;color:{{ $availColor }};">{{ (int)$avail }}</div>
                    <div style="font-size:9px;color:#6c757d;">left</div>
                </div>
            </div>

            {{-- Mini stats --}}
            <div class="d-flex justify-content-around" style="font-size:11px;color:#6c757d;">
                <div>
                    <div class="fw-semibold text-dark">{{ $total > 0 ? $total : '—' }}</div>
                    <div>Total</div>
                </div>
                <div>
                    <div class="fw-semibold {{ $taken > 0 ? 'text-primary' : 'text-dark' }}">{{ $taken > 0 ? $taken : '—' }}</div>
                    <div>Taken</div>
                </div>
                @if($pending > 0)
                <div>
                    <div class="fw-semibold text-warning">{{ $pending }}</div>
                    <div>Pending</div>
                </div>
                @endif
            </div>

            {{-- Carry fwd / adjustment footnote --}}
            @if($bal->carry_forward > 0 || $bal->adjustment != 0)
            <div class="mt-2" style="font-size:10px;color:#6c757d;">
                @if($bal->carry_forward > 0)<span class="me-1">+{{ $bal->carry_forward }} c/f</span>@endif
                @if($bal->adjustment != 0)<span>{{ $bal->adjustment > 0 ? '+' : '' }}{{ $bal->adjustment }} adj</span>@endif
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- ── Two column layout: Apply + Upcoming ───────────────────────── --}}
<div class="row g-4 mb-4">
    {{-- Apply for Leave --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
            <div class="card-header bg-transparent border-0 pt-3 pb-0 px-4">
                <h6 class="mb-0"><i class="bi bi-calendar-plus me-2 text-primary"></i>Apply for Leave</h6>
            </div>
            <div class="card-body px-4">
                <form method="POST" action="{{ route('user.leave.apply') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Leave Type</label>
                            <select name="leave_type_id" class="form-select" required>
                                <option value="">— Select —</option>
                                @foreach($leaveTypes as $lt)
                                <option value="{{ $lt->id }}" {{ old('leave_type_id') == $lt->id ? 'selected' : '' }}>{{ $lt->name }} ({{ $lt->code }})</option>
                                @endforeach
                            </select>
                            @error('leave_type_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Start Date</label>
                            <input type="date" name="start_date" class="form-control" required value="{{ old('start_date') }}">
                            @error('start_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">End Date</label>
                            <input type="date" name="end_date" class="form-control" required value="{{ old('end_date') }}">
                            @error('end_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3 d-flex align-items-center gap-2 pt-1">
                            <div class="form-check mb-0">
                                <input type="checkbox" name="is_half_day" value="1" class="form-check-input" id="halfDay" {{ old('is_half_day') ? 'checked' : '' }}>
                                <label class="form-check-label small fw-semibold" for="halfDay">Half Day</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Period</label>
                            <select name="half_day_period" class="form-select">
                                <option value="">N/A</option>
                                <option value="morning" {{ old('half_day_period') == 'morning' ? 'selected' : '' }}>Morning</option>
                                <option value="afternoon" {{ old('half_day_period') == 'afternoon' ? 'selected' : '' }}>Afternoon</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Attachment</label>
                            <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Reason</label>
                            <textarea name="reason" class="form-control" rows="2" placeholder="Optional reason…">{{ old('reason') }}</textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-send me-1"></i>Submit Application</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Upcoming Leave --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
            <div class="card-header bg-transparent border-0 pt-3 pb-0 px-4">
                <h6 class="mb-0"><i class="bi bi-calendar-event me-2 text-success"></i>Upcoming Leave</h6>
                <div class="text-muted" style="font-size:11px;">Next 30 days</div>
            </div>
            <div class="card-body px-3 py-2">
                @if($upcomingLeave->isEmpty())
                <div class="text-center text-muted py-4" style="font-size:13px;">
                    <i class="bi bi-sun" style="font-size:1.5rem;opacity:.4;"></i>
                    <p class="mt-2 mb-0">No upcoming leave.</p>
                </div>
                @else
                <div class="list-group list-group-flush">
                    @foreach($upcomingLeave as $upcoming)
                    <div class="list-group-item px-1 py-2 border-0 d-flex align-items-center gap-2">
                        <span class="badge text-bg-success" style="min-width:34px;">{{ $upcoming->leaveType->code ?? '' }}</span>
                        <div class="flex-grow-1" style="font-size:12px;">
                            <div class="fw-semibold">{{ $upcoming->leaveType->name ?? '' }}</div>
                            <div class="text-muted">
                                {{ $upcoming->start_date->format('d M') }}{{ $upcoming->start_date->ne($upcoming->end_date) ? ' – ' . $upcoming->end_date->format('d M') : '' }}
                            </div>
                        </div>
                        <span class="badge bg-light text-dark border">{{ $upcoming->total_days }}d{{ $upcoming->is_half_day ? ' ½' : '' }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── My Applications ─────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm" style="border-radius:14px;">
    <div class="card-header bg-transparent border-0 pt-3 pb-0 px-4">
        <h6 class="mb-0"><i class="bi bi-list-ul me-2 text-secondary"></i>My Applications</h6>
    </div>
    <div class="card-body p-0 pt-2">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:13px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Type</th>
                        <th>Start</th>
                        <th>End</th>
                        <th class="text-center">Days</th>
                        <th class="text-center">Status</th>
                        <th class="pe-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $app)
                    <tr>
                        <td class="ps-4">
                            <span class="badge text-bg-light border me-1">{{ $app->leaveType->code ?? '' }}</span>
                            {{ $app->leaveType->name ?? '—' }}
                        </td>
                        <td>{{ \Carbon\Carbon::parse($app->start_date)->format('d M Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($app->end_date)->format('d M Y') }}</td>
                        <td class="text-center">{{ $app->total_days }}{{ $app->is_half_day ? ' (½)' : '' }}</td>
                        <td class="text-center">{!! $app->statusBadge() !!}</td>
                        <td class="pe-4 text-end">
                            @if($app->status === 'pending')
                            <form method="POST" action="{{ route('user.leave.cancel', $app) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this application?')" title="Cancel">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">No leave applications yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">{{ $applications->links() }}</div>
    </div>
</div>

@endsection
