@extends('layouts.app')
@section('title', 'Leave Balances')
@section('page-title', 'Leave Balances')

@section('content')
@include('hr.leave.partials.nav-tabs')

{{-- ── Toolbar ─────────────────────────────────────────────────────── --}}
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div class="d-flex align-items-center gap-2">
        <h6 class="mb-0 text-muted"><i class="bi bi-pie-chart me-1"></i>Leave Balances — {{ $year }}</h6>
        <span class="badge bg-secondary">{{ $employees->count() }} employees</span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        {{-- Search --}}
        <input type="text" id="empSearch" class="form-control form-control-sm" placeholder="Search employee…" style="width:180px">
        {{-- Year --}}
        <form method="GET" class="d-flex">
            <select name="year" class="form-select form-select-sm" style="width:100px" onchange="this.form.submit()">
                @for($y = now()->year - 1; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </form>
        {{-- Initialize --}}
        <form method="POST" action="{{ route('hr.leave.balances.initialize') }}">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">
            <button class="btn btn-sm btn-success"
                    onclick="return confirm('Initialize leave balances for all employees for {{ $year }}? Existing balances will not be overwritten.')">
                <i class="bi bi-plus-circle me-1"></i>Initialize Balances
            </button>
        </form>
    </div>
</div>

{{-- ── Legend ──────────────────────────────────────────────────────── --}}
<div class="d-flex gap-3 mb-2" style="font-size:11px;">
    <span><span class="badge bg-success">&nbsp;</span> Available &gt; 0</span>
    <span><span class="badge bg-warning text-dark">&nbsp;</span> Low (&lt;= 3)</span>
    <span><span class="badge bg-danger">&nbsp;</span> Exhausted</span>
    <span class="text-muted">Number = available &nbsp;·&nbsp; taken/entitled below</span>
</div>

{{-- ── Matrix Table ─────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;">
    <div class="table-responsive" style="max-height:75vh;overflow-y:auto;">
        <table class="table table-hover table-bordered align-middle mb-0" id="balanceTable" style="font-size:12px;min-width:900px;">
            <thead style="position:sticky;top:0;z-index:10;background:#f8f9fa;">
                <tr>
                    <th class="ps-3 py-2" style="min-width:180px;background:#f8f9fa;">Employee</th>
                    @foreach($leaveTypes as $lt)
                    <th class="text-center py-2" style="min-width:70px;background:#f8f9fa;" title="{{ $lt->name }}">
                        <span class="badge rounded-pill text-bg-light border" style="font-size:11px;">{{ $lt->code }}</span>
                        <div class="text-muted fw-normal" style="font-size:9px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:65px;margin:0 auto;">{{ Str::limit($lt->name, 10) }}</div>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $emp)
                @php $empBalances = $balances->get($emp->id, collect()); @endphp
                <tr class="emp-row">
                    <td class="ps-3 fw-semibold emp-name" style="white-space:nowrap;">
                        {{ $emp->full_name }}
                        @if($emp->company)
                        <div class="text-muted fw-normal" style="font-size:10px;">{{ $emp->company }}</div>
                        @endif
                    </td>
                    @foreach($leaveTypes as $lt)
                    @php
                        $bal     = $empBalances->firstWhere('leave_type_id', $lt->id);
                        $avail   = $bal ? (float) $bal->available : null;
                        $taken   = $bal ? (float) $bal->taken    : 0;
                        $entitled= $bal ? (float) $bal->entitled : 0;
                        $total   = $bal ? (float) $bal->entitled + (float) $bal->carry_forward + (float) $bal->adjustment : 0;
                        $pct     = $total > 0 ? min(round($taken / $total * 100), 100) : 0;

                        if (!$bal)            $badgeClass = 'bg-light text-muted border';
                        elseif ($avail <= 0)  $badgeClass = 'bg-danger text-white';
                        elseif ($avail <= 3)  $badgeClass = 'bg-warning text-dark';
                        else                  $badgeClass = 'bg-success text-white';
                    @endphp
                    <td class="text-center px-1 py-2">
                        @if($bal)
                            <span class="badge {{ $badgeClass }}" style="font-size:12px;min-width:28px;">{{ (int)$avail }}</span>
                            <div class="text-muted mt-1" style="font-size:9px;">{{ $taken }}/{{ $entitled }}</div>
                            @if($total > 0)
                            <div class="progress mt-1 mx-auto" style="height:3px;width:44px;border-radius:2px;">
                                <div class="progress-bar {{ $pct >= 90 ? 'bg-danger' : ($pct >= 60 ? 'bg-warning' : 'bg-primary') }}"
                                     style="width:{{ $pct }}%"></div>
                            </div>
                            @endif
                        @else
                            <span class="text-muted" style="font-size:14px;">—</span>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $leaveTypes->count() + 1 }}" class="text-center text-muted py-5">
                        No employees found. <a href="{{ route('hr.leave.balances.initialize') }}">Initialize balances</a> first.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('empSearch').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#balanceTable .emp-row').forEach(row => {
        const name = row.querySelector('.emp-name').textContent.toLowerCase();
        row.style.display = name.includes(q) ? '' : 'none';
    });
});
</script>
@endpush

@endsection
