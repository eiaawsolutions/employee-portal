@php
    $canManage = Auth::user()->canManagePayroll();
@endphp
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('hr.payroll.pay-runs.index') || request()->routeIs('hr.payroll.pay-runs.create') || request()->routeIs('hr.payroll.pay-runs.show') ? 'active' : '' }}"
           href="{{ route('hr.payroll.pay-runs.index') }}">
            <i class="bi bi-cash-stack me-1"></i>Pay Runs
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('hr.payroll.salaries') || request()->routeIs('hr.payroll.adjustments') ? 'active' : '' }}"
           href="{{ route('hr.payroll.salaries') }}">
            <i class="bi bi-wallet2 me-1"></i>Salaries
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('hr.payroll.items') ? 'active' : '' }}"
           href="{{ route('hr.payroll.items') }}">
            <i class="bi bi-list-check me-1"></i>Earnings & Deductions
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('hr.payroll.ea-forms.*') ? 'active' : '' }}"
           href="{{ route('hr.payroll.ea-forms.index') }}">
            <i class="bi bi-file-earmark-text me-1"></i>EA Forms
        </a>
    </li>
    @if($canManage)
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('hr.payroll.config') || request()->routeIs('hr.payroll.config.update') ? 'active' : '' }}"
           href="{{ route('hr.payroll.config') }}">
            <i class="bi bi-gear me-1"></i>Configuration
        </a>
    </li>
    @endif
</ul>
