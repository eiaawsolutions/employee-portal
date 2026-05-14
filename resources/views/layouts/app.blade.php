<!DOCTYPE html>
<html lang="en" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('eiaaw.product_name', 'EIAAW Workforce'))</title>
    <link rel="icon" type="image/png" href="{{ asset('brand/shield.png') }}">
    {{-- Apply saved theme before page renders to prevent flash --}}
    <script nonce="{{ $cspNonce ?? '' }}">
        (function() {
            var t = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', t);
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
    {{-- EIAAW typography stack ── Inter, Instrument Serif, JetBrains Mono --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('brand/eiaaw-tokens.css') }}" rel="stylesheet">
    <style>
        /* EIAAW Workforce app shell — minimalist-ui profile.
           Tokens come from brand/eiaaw-tokens.css; everything below composes from them. */
        body { background: var(--bg); font-family: var(--sans); color: var(--ink); }
        .modal { z-index: 1055; }
        .modal-backdrop { z-index: 1050; }
        .modal-dialog-scrollable { max-height: calc(100vh - 56px); }
        .modal-dialog-scrollable .modal-body { overflow-y: auto; max-height: calc(100vh - 200px); }
        /* Fix: when <form> wraps modal-body + modal-footer, footer must stay visible */
        .modal-dialog-scrollable .modal-content > form {
            display: flex; flex-direction: column; flex: 1 1 auto; overflow: hidden; min-height: 0;
        }
        /* ── Sidebar (warm cream, ink text — EIAAW minimalist-ui) ── */
        .sidebar {
            width: var(--sidebar-w); height: 100vh; position: fixed; top: 0; left: 0; z-index: 100;
            background: var(--bg-warm);
            border-right: 1px solid var(--line-soft);
            display: flex; flex-direction: column; overflow: hidden;
        }
        .sidebar-brand {
            padding: 18px 18px 16px;
            border-bottom: 1px solid var(--line-soft);
        }
        .sidebar-brand .eiaaw-lockup-text strong { font-size: 13px; color: var(--ink); }
        .sidebar-brand .eiaaw-lockup-text small { font-size: 9px; }
        .sidebar-section {
            padding: 16px 20px 4px; font-family: var(--mono); font-size: 10px;
            text-transform: uppercase; letter-spacing: 0.14em;
            color: var(--mute); font-weight: 500;
        }
        .sidebar-nav { padding: 4px 0; flex: 1; overflow-y: auto; }
        .sidebar-nav .nav-item { margin: 1px 12px; }
        .sidebar-nav .nav-link {
            color: var(--ink-2); border-radius: 8px; padding: 9px 12px;
            display: flex; align-items: center; gap: 10px;
            transition: background 0.18s var(--ease), color 0.18s var(--ease);
            font-size: 13.5px; font-weight: 500;
        }
        .sidebar-nav .nav-link:hover {
            background: rgba(31,168,150,0.08); color: var(--primary-dark);
        }
        .sidebar-nav .nav-link.active {
            background: var(--primary-tint); color: var(--primary-dark); font-weight: 600;
        }
        .sidebar-nav .nav-link i { font-size: 16px; width: 20px; flex-shrink: 0; opacity: 0.85; }
        .sidebar-footer { padding: 12px 12px 14px; border-top: 1px solid var(--line-soft); }
        .user-chip {
            background: var(--surface); border: 1px solid var(--line-soft);
            border-radius: 10px; padding: 10px 12px;
        }
        .user-avatar {
            width: 34px; height: 34px; background: var(--primary-tint); border-radius: 50%;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .role-badge {
            font-family: var(--mono); font-size: 9px; padding: 2px 7px; border-radius: 20px;
            background: var(--primary-tint); color: var(--primary-dark);
            display: inline-block; margin-top: 2px;
            text-transform: uppercase; letter-spacing: 0.08em;
        }
        .main-content { margin-left: var(--sidebar-w); min-height: 100vh; background: var(--bg); }
        .topbar {
            background: var(--surface); border-bottom: 1px solid var(--line-soft);
            padding: 12px 24px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
        }
        .topbar h4 {
            margin: 0; font-weight: 600; color: var(--ink); font-size: 17px;
            letter-spacing: -0.01em;
        }
        .content-area { padding: 22px 24px; }

        /* ── Mobile responsiveness ── */
        .sidebar-overlay {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45);
            z-index: 99;
        }
        .sidebar-overlay.active { display: block; }
        .hamburger-btn {
            display: none; background: none; border: none; padding: 4px 8px;
            font-size: 22px; color: var(--ink); cursor: pointer; line-height: 1;
        }
        @media (max-width: 767.98px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.25s ease;
                z-index: 200;
            }
            .sidebar.sidebar-open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .hamburger-btn { display: inline-flex; align-items: center; }
            .topbar { padding: 10px 16px; }
            .content-area { padding: 16px 14px; }
        }
        .card {
            border: 1px solid var(--line-soft); background: var(--surface);
            border-radius: 12px; box-shadow: 0 1px 2px rgba(15,26,29,0.04);
        }
        .section-header {
            background: var(--bg-warm); border-left: 3px solid var(--primary);
            padding: 9px 14px; border-radius: 0 8px 8px 0; margin-bottom: 18px;
        }
        .section-header h6 { margin: 0; font-weight: 600; color: var(--ink); letter-spacing: -0.005em; }
        /* ── Bootstrap accent overrides — keep BS structure, swap colors to EIAAW ── */
        .btn-primary {
            background-color: var(--ink); border-color: var(--ink); color: var(--bg);
            font-weight: 500; letter-spacing: -0.005em;
        }
        .btn-primary:hover, .btn-primary:focus, .btn-primary:active {
            background-color: var(--primary-dark) !important;
            border-color: var(--primary-dark) !important; color: var(--bg) !important;
        }
        .btn-outline-primary { color: var(--primary-dark); border-color: var(--primary-dark); }
        .btn-outline-primary:hover { background-color: var(--primary-dark); color: var(--bg); }
        a { color: var(--primary-dark); text-decoration: none; }
        a:hover { color: var(--primary); text-decoration: underline; }
        .text-primary { color: var(--primary-dark) !important; }
        .bg-primary { background-color: var(--primary-dark) !important; }
        .border-primary { border-color: var(--primary-dark) !important; }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary); box-shadow: 0 0 0 0.15rem rgba(31,168,150,0.15);
        }
        .badge.bg-primary { background-color: var(--primary-dark) !important; }
        :focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; }
        /* ── Dark Mode overrides ── */
        [data-theme="dark"] body { background: #0f172a; }
        [data-theme="dark"] .main-content { background: #0f172a; }
        [data-theme="dark"] .topbar { background: #1e293b; border-color: #334155; }
        [data-theme="dark"] .topbar h4 { color: #f1f5f9; }
        [data-theme="dark"] .topbar .text-muted { color: #94a3b8 !important; }
        [data-theme="dark"] .card { background: #1e293b; border-color: #334155; }
        [data-theme="dark"] .card-header { background: #1e293b !important; border-color: #334155; color: #f1f5f9; }
        [data-theme="dark"] .card-body { color: #e2e8f0; }
        [data-theme="dark"] .table { color: #e2e8f0; border-color: #334155; }
        [data-theme="dark"] .table thead th { background: #0f172a; color: #94a3b8; border-color: #334155; }
        [data-theme="dark"] .table tbody td { border-color: #334155; }
        [data-theme="dark"] .table-hover tbody tr:hover { background: #334155; }
        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select { background: #0f172a; border-color: #475569; color: #e2e8f0; }
        [data-theme="dark"] .form-control:focus,
        [data-theme="dark"] .form-select:focus { background: #0f172a; border-color: var(--primary); color: #e2e8f0; box-shadow: 0 0 0 3px rgba(31,168,150,0.18); }
        [data-theme="dark"] .form-control::placeholder { color: #64748b; }
        [data-theme="dark"] .input-group-text { background: #1e293b; border-color: #475569; color: #94a3b8; }
        [data-theme="dark"] .section-header { background: #1e293b; }
        [data-theme="dark"] .section-header h6 { color: #f1f5f9; }
        [data-theme="dark"] .text-muted { color: #94a3b8 !important; }
        [data-theme="dark"] .fw-semibold, [data-theme="dark"] .fw-bold { color: #f1f5f9; }
        [data-theme="dark"] label { color: #cbd5e1; }
        [data-theme="dark"] h1,[data-theme="dark"] h2,[data-theme="dark"] h3,
        [data-theme="dark"] h4,[data-theme="dark"] h5,[data-theme="dark"] h6 { color: #f1f5f9; }
        [data-theme="dark"] .modal-content { background: #1e293b; color: #e2e8f0; }
        [data-theme="dark"] .modal-body, [data-theme="dark"] .modal-footer { border-color: #334155; }
        [data-theme="dark"] .dropdown-menu { background: #1e293b; border-color: #334155; }
        [data-theme="dark"] .dropdown-item { color: #e2e8f0; }
        [data-theme="dark"] .dropdown-item:hover { background: #334155; }
        [data-theme="dark"] hr { border-color: #334155; }
        [data-theme="dark"] .alert { border-color: #334155; }
        [data-theme="dark"] .pagination .page-link { background: #1e293b; border-color: #334155; color: #94a3b8; }
        [data-theme="dark"] .pagination .page-item.active .page-link { background: var(--primary-dark); border-color: var(--primary-dark); color: var(--bg); }
    </style>
    @stack('styles')
</head>
<body>
@auth
<nav class="sidebar">
    <div class="sidebar-brand">
        <a href="{{ url('/') }}" class="eiaaw-lockup eiaaw-lockup--sidebar">
            <img src="{{ asset('brand/shield.png') }}" alt="EIAAW Workforce">
            <span class="eiaaw-lockup-text">
                <strong>EIAAW Workforce</strong>
                <small>AI &middot; Human Partnerships</small>
            </span>
        </a>
    </div>

    <div class="sidebar-nav">

        {{-- ══════════════════════════════════════════════════
             SUPERADMIN MENU — all modules organized by category
             system_admin (EIAAW staff) sees the same menu plus the
             Platform block (Integrations, Tenants) gated below.
             ══════════════════════════════════════════════════ --}}
        @if(Auth::user()->isSuperadmin() || Auth::user()->isSystemAdmin())

        {{-- ── HR ── --}}
        <div class="sidebar-section">HR</div>
        <div class="nav-item">
            <a href="{{ route('hr.dashboard') }}"
               class="nav-link {{ request()->routeIs('hr.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('onboarding.index') }}"
               class="nav-link {{ request()->routeIs('onboarding.*') ? 'active' : '' }}">
                <i class="bi bi-person-plus"></i> Onboarding
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('hr.offboarding.index') }}"
               class="nav-link {{ request()->routeIs('hr.offboarding.*') || request()->routeIs('offboarding.*') ? 'active' : '' }}">
                <i class="bi bi-box-arrow-right"></i> Offboarding
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('employees.index') }}"
               class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Employee Listing
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('announcements.index') }}"
               class="nav-link {{ request()->routeIs('announcements.*') ? 'active' : '' }}">
                <i class="bi bi-megaphone"></i> Announcements
            </a>
        </div>

        {{-- ── HRM Modules ── --}}
        <div class="sidebar-section">HRM Modules</div>
        <div class="nav-item">
            <a href="{{ route('hr.leave.index') }}"
               class="nav-link {{ request()->routeIs('hr.leave.*') ? 'active' : '' }}">
                <i class="bi bi-calendar2-week"></i> Leave Management
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('hr.payroll.pay-runs.index') }}"
               class="nav-link {{ request()->routeIs('hr.payroll.*') && !request()->routeIs('hr.payroll.ea-forms.*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i> Payroll
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('hr.payroll.ea-forms.index') }}"
               class="nav-link {{ request()->routeIs('hr.payroll.ea-forms.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text"></i> EA Forms
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('hr.attendance.index') }}"
               class="nav-link {{ request()->routeIs('hr.attendance.*') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> Attendance
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('hr.claims.index') }}"
               class="nav-link {{ request()->routeIs('hr.claims.*') ? 'active' : '' }}">
                <i class="bi bi-receipt-cutoff"></i> Claims
            </a>
        </div>

        {{-- ── IT ── --}}
        <div class="sidebar-section">IT</div>
        <div class="nav-item">
            <a href="{{ route('assets.index') }}"
               class="nav-link {{ request()->routeIs('assets.*') ? 'active' : '' }}">
                <i class="bi bi-laptop"></i> Asset Listing
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('it.tasks') }}"
               class="nav-link {{ request()->routeIs('it.tasks') ? 'active' : '' }}">
                <i class="bi bi-list-task"></i> Task Management
                @php $myTasks = \App\Models\ItTask::where('assigned_to', Auth::id())->where('status','!=','done')->count(); @endphp
                @if($myTasks > 0)
                    <span class="badge bg-warning text-dark ms-auto" style="font-size:10px;">{{ $myTasks }}</span>
                @endif
            </a>
        </div>

        {{-- ── Finance ── --}}
        <div class="sidebar-section">Finance</div>
        <div class="nav-item">
            <a href="{{ route('accounting.dashboard') }}"
               class="nav-link {{ request()->routeIs('accounting.dashboard') || request()->routeIs('accounting.executive-dashboard') ? 'active' : '' }}">
                <i class="bi bi-calculator"></i> Accounting
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('accounting.chart-of-accounts.index') }}"
               class="nav-link {{ request()->routeIs('accounting.chart-of-accounts.*') ? 'active' : '' }}">
                <i class="bi bi-diagram-3"></i> Chart of Accounts
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('accounting.journal-entries.index') }}"
               class="nav-link {{ request()->routeIs('accounting.journal-entries.*') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i> General Ledger
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('accounting.invoices.index') }}"
               class="nav-link {{ request()->routeIs('accounting.invoices.*', 'accounting.customers.*', 'accounting.customer-payments.*', 'accounting.credit-notes.*') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i> Receivables
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('accounting.bills.index') }}"
               class="nav-link {{ request()->routeIs('accounting.bills.*', 'accounting.vendors.*', 'accounting.vendor-payments.*', 'accounting.purchase-orders.*') ? 'active' : '' }}">
                <i class="bi bi-receipt-cutoff"></i> Payables
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('accounting.banking.index') }}"
               class="nav-link {{ request()->routeIs('accounting.banking.*', 'accounting.bank-transfers.*') ? 'active' : '' }}">
                <i class="bi bi-bank"></i> Banking
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('accounting.tax.index') }}"
               class="nav-link {{ request()->routeIs('accounting.tax.*', 'accounting.tax-returns.*') ? 'active' : '' }}">
                <i class="bi bi-percent"></i> Tax
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('accounting.fixed-assets.index') }}"
               class="nav-link {{ request()->routeIs('accounting.fixed-assets.*', 'accounting.asset-categories.*') ? 'active' : '' }}">
                <i class="bi bi-building"></i> Fixed Assets
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('accounting.budgets.index') }}"
               class="nav-link {{ request()->routeIs('accounting.budgets.*') ? 'active' : '' }}">
                <i class="bi bi-pie-chart"></i> Budgets
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('accounting.reports.trial-balance') }}"
               class="nav-link {{ request()->routeIs('accounting.reports.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-bar-graph"></i> Financial Reports
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('accounting.ai.invoice-scanner') }}"
               class="nav-link {{ request()->routeIs('accounting.ai.*') ? 'active' : '' }}">
                <i class="bi bi-robot"></i> AI Tools
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('accounting.settings') }}"
               class="nav-link {{ request()->routeIs('accounting.settings*') ? 'active' : '' }}">
                <i class="bi bi-sliders"></i> Accounting Settings
            </a>
        </div>

        {{-- ── C-Suite & Reports ── --}}
        <div class="sidebar-section">C-Suite & Reports</div>
        <div class="nav-item">
            <a href="{{ route('reports.executive') }}"
               class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow"></i> C-Suite Reports
            </a>
        </div>
        @if(Auth::user()->isPlatformAdmin())
        <div class="nav-item">
            <a href="{{ route('superadmin.system-overview') }}"
               class="nav-link {{ request()->routeIs('superadmin.system-overview') ? 'active' : '' }}">
                <i class="bi bi-diagram-3"></i> System Overview
            </a>
        </div>
        @endif

        {{-- ── Support (Ticketing) ── --}}
        <div class="sidebar-section">Support</div>
        <div class="nav-item">
            <a href="{{ route('tickets.manage') }}"
               class="nav-link {{ request()->routeIs('tickets.manage') || (request()->routeIs('tickets.show') && request()->query('from') === 'manage') ? 'active' : '' }}">
                <i class="bi bi-headset"></i> Ticket Management
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('tickets.index') }}"
               class="nav-link {{ request()->routeIs('tickets.index') || request()->routeIs('tickets.create') ? 'active' : '' }}">
                <i class="bi bi-ticket-perforated"></i> My Tickets
            </a>
        </div>

        {{-- ── Self-Service ── --}}
        <div class="sidebar-section">Self-Service</div>
        <div class="nav-item">
            <a href="{{ route('user.leave.index') }}"
               class="nav-link {{ request()->routeIs('user.leave.index') ? 'active' : '' }}">
                <i class="bi bi-calendar-plus"></i> My Leave
            </a>
        </div>
        @if(Auth::user()->employee && \App\Models\Employee::where('manager_id', Auth::user()->employee->id)->exists())
        <div class="nav-item">
            <a href="{{ route('user.leave.team') }}"
               class="nav-link {{ request()->routeIs('user.leave.team*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Team Leave
                @php $__pendingTeam = \App\Models\LeaveApplication::whereIn('employee_id', \App\Models\Employee::where('manager_id', Auth::user()->employee->id)->pluck('id'))->where('status', 'pending')->count(); @endphp
                @if($__pendingTeam > 0)
                <span class="badge bg-warning text-dark ms-auto" style="font-size:10px;">{{ $__pendingTeam }}</span>
                @endif
            </a>
        </div>
        @endif
        <div class="nav-item">
            <a href="{{ route('user.payroll.index') }}"
               class="nav-link {{ request()->routeIs('user.payroll.index') || request()->routeIs('user.payroll.payslip') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i> My Payslips
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('user.payroll.ea-form') }}"
               class="nav-link {{ request()->routeIs('user.payroll.ea-form') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text"></i> My EA Form
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('user.attendance.index') }}"
               class="nav-link {{ request()->routeIs('user.attendance.*') ? 'active' : '' }}">
                <i class="bi bi-stopwatch"></i> My Attendance
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('user.claims.index') }}"
               class="nav-link {{ request()->routeIs('user.claims.index') ? 'active' : '' }}">
                <i class="bi bi-receipt-cutoff"></i> My Claims
            </a>
        </div>
        @if(Auth::user()->employee && \App\Models\Employee::where('manager_id', Auth::user()->employee->id)->exists())
        <div class="nav-item">
            <a href="{{ route('user.claims.team') }}"
               class="nav-link {{ request()->routeIs('user.claims.team*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Team Claims
                @php $__pendingTeamClaims = \App\Models\ExpenseClaim::whereIn('employee_id', \App\Models\Employee::where('manager_id', Auth::user()->employee->id)->pluck('id'))->where('status', 'submitted')->count(); @endphp
                @if($__pendingTeamClaims > 0)
                <span class="badge bg-warning text-dark ms-auto" style="font-size:10px;">{{ $__pendingTeamClaims }}</span>
                @endif
            </a>
        </div>
        @endif

        {{-- ── Platform (EIAAW staff only) ── --}}
        @if(Auth::user()->isPlatformAdmin())
        <div class="sidebar-section">Platform</div>
        <div class="nav-item">
            <a href="{{ route('superadmin.hq.index') }}"
               class="nav-link {{ request()->routeIs('superadmin.hq.index') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> HQ Overview
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('superadmin.hq.tickets') }}"
               class="nav-link {{ request()->routeIs('superadmin.hq.tickets') ? 'active' : '' }}">
                <i class="bi bi-headset"></i> HQ Ticketing
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('superadmin.tenants.index') }}"
               class="nav-link {{ request()->routeIs('superadmin.tenants.*') ? 'active' : '' }}">
                <i class="bi bi-buildings"></i> Tenants
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('superadmin.integrations') }}"
               class="nav-link {{ request()->routeIs('superadmin.integrations*') ? 'active' : '' }}">
                <i class="bi bi-key"></i> Integrations
            </a>
        </div>
        @endif

        {{-- ── Administration ── --}}
        <div class="sidebar-section">Administration</div>
        <div class="nav-item">
            <a href="{{ route('superadmin.roles.index') }}"
               class="nav-link {{ request()->routeIs('superadmin.roles.*') ? 'active' : '' }}">
                <i class="bi bi-shield-lock"></i> Role Management
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('superadmin.accounts.index') }}"
               class="nav-link {{ request()->routeIs('superadmin.accounts.*') ? 'active' : '' }}">
                <i class="bi bi-person-lock"></i> Account Management
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('superadmin.companies.index') }}"
               class="nav-link {{ request()->routeIs('superadmin.companies.*') ? 'active' : '' }}">
                <i class="bi bi-building"></i> Company Registration
            </a>
        </div>
        @if(Auth::user()->isPlatformAdmin())
        <div class="nav-item">
            <a href="{{ route('superadmin.kb.gate') }}"
               class="nav-link {{ request()->routeIs('superadmin.kb.*') ? 'active' : '' }}">
                <i class="bi bi-book"></i> System Logic
            </a>
        </div>
        @endif

        {{-- ── Account ── --}}
        <div class="sidebar-section">Account</div>
        <div class="nav-item">
            <a href="{{ route('profile') }}"
               class="nav-link {{ request()->routeIs('profile') ? 'active' : '' }}">
                <i class="bi bi-person-circle"></i> Profile
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('account') }}"
               class="nav-link {{ request()->routeIs('account') ? 'active' : '' }}">
                <i class="bi bi-gear"></i> Account
            </a>
        </div>

        {{-- ══════════════════════════════════════════════════
             HR / SYSTEM ADMIN MENU
             ══════════════════════════════════════════════════ --}}
        @elseif(Auth::user()->isHr() || Auth::user()->isSystemAdmin())
        <div class="sidebar-section">
            @if(Auth::user()->isSystemAdmin()) System Admin Menu
            @else HR Menu
            @endif
        </div>

        <div class="nav-item">
            <a href="{{ route('hr.dashboard') }}"
               class="nav-link {{ request()->routeIs('hr.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </div>

        {{-- 2. Onboarding --}}
        <div class="nav-item">
            <a href="{{ route('onboarding.index') }}"
               class="nav-link {{ request()->routeIs('onboarding.*') ? 'active' : '' }}">
                <i class="bi bi-person-plus"></i> Onboarding
            </a>
        </div>

        {{-- 3. Offboarding --}}
        <div class="nav-item">
            <a href="{{ route('hr.offboarding.index') }}"
               class="nav-link {{ request()->routeIs('hr.offboarding.*') || request()->routeIs('offboarding.*') ? 'active' : '' }}">
                <i class="bi bi-box-arrow-right"></i> Offboarding
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('employees.index') }}"
               class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Employee Listing
            </a>
        </div>

        {{-- Asset Listing + Company Registration (HR Manager + HR Executive) --}}
        @if(Auth::user()->isHrManager() || Auth::user()->isHrExecutive())
        <div class="nav-item">
            <a href="{{ route('assets.index') }}"
               class="nav-link {{ request()->routeIs('assets.*') ? 'active' : '' }}">
                <i class="bi bi-laptop"></i> Asset Listing
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('superadmin.companies.index') }}"
               class="nav-link {{ request()->routeIs('superadmin.companies.*') ? 'active' : '' }}">
                <i class="bi bi-building"></i> Company Registration
            </a>
        </div>
        @endif

        {{-- Announcements — HR Manager + Superadmin + System Admin + IT Manager + Manager --}}
        @if(Auth::user()->isHrManager() || Auth::user()->isSuperadmin() || Auth::user()->isSystemAdmin() || Auth::user()->isItManager() || Auth::user()->employee?->work_role === 'manager')
        <div class="nav-item">
            <a href="{{ route('announcements.index') }}"
               class="nav-link {{ request()->routeIs('announcements.*') ? 'active' : '' }}">
                <i class="bi bi-megaphone"></i> Announcements
            </a>
        </div>
        @endif

        {{-- ── HRM Modules (HR Only) ── --}}
        <div class="sidebar-section">HRM Modules</div>
        @if(Auth::user()->isHrManager() || Auth::user()->isSystemAdmin())
        <div class="nav-item">
            <a href="{{ route('reports.executive') }}"
               class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow"></i> C-Suite Reports
            </a>
        </div>
        @endif
        <div class="nav-item">
            <a href="{{ route('hr.leave.index') }}"
               class="nav-link {{ request()->routeIs('hr.leave.*') ? 'active' : '' }}">
                <i class="bi bi-calendar2-week"></i> Leave Management
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('hr.payroll.pay-runs.index') }}"
               class="nav-link {{ request()->routeIs('hr.payroll.*') && !request()->routeIs('hr.payroll.ea-forms.*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i> Payroll
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('hr.payroll.ea-forms.index') }}"
               class="nav-link {{ request()->routeIs('hr.payroll.ea-forms.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text"></i> EA Forms
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('hr.attendance.index') }}"
               class="nav-link {{ request()->routeIs('hr.attendance.*') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> Attendance
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('hr.claims.index') }}"
               class="nav-link {{ request()->routeIs('hr.claims.*') ? 'active' : '' }}">
                <i class="bi bi-receipt-cutoff"></i> Claims
            </a>
        </div>

        {{-- ── Support (Ticketing) ── --}}
        <div class="sidebar-section">Support</div>
        <div class="nav-item">
            <a href="{{ route('tickets.manage') }}"
               class="nav-link {{ request()->routeIs('tickets.manage') || (request()->routeIs('tickets.show') && request()->query('from') === 'manage') ? 'active' : '' }}">
                <i class="bi bi-headset"></i> Ticket Management
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('tickets.index') }}"
               class="nav-link {{ request()->routeIs('tickets.index') || request()->routeIs('tickets.create') ? 'active' : '' }}">
                <i class="bi bi-ticket-perforated"></i> My Tickets
            </a>
        </div>

        {{-- ── Self-Service ── --}}
        <div class="sidebar-section">Self-Service</div>
        <div class="nav-item">
            <a href="{{ route('user.leave.index') }}"
               class="nav-link {{ request()->routeIs('user.leave.index') ? 'active' : '' }}">
                <i class="bi bi-calendar-plus"></i> My Leave
            </a>
        </div>
        @if(Auth::user()->employee && \App\Models\Employee::where('manager_id', Auth::user()->employee->id)->exists())
        <div class="nav-item">
            <a href="{{ route('user.leave.team') }}"
               class="nav-link {{ request()->routeIs('user.leave.team*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Team Leave
                @php $__pendingTeam = \App\Models\LeaveApplication::whereIn('employee_id', \App\Models\Employee::where('manager_id', Auth::user()->employee->id)->pluck('id'))->where('status', 'pending')->count(); @endphp
                @if($__pendingTeam > 0)
                <span class="badge bg-warning text-dark ms-auto" style="font-size:10px;">{{ $__pendingTeam }}</span>
                @endif
            </a>
        </div>
        @endif
        <div class="nav-item">
            <a href="{{ route('user.payroll.index') }}"
               class="nav-link {{ request()->routeIs('user.payroll.index') || request()->routeIs('user.payroll.payslip') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i> My Payslips
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('user.payroll.ea-form') }}"
               class="nav-link {{ request()->routeIs('user.payroll.ea-form') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text"></i> My EA Form
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('user.attendance.index') }}"
               class="nav-link {{ request()->routeIs('user.attendance.*') ? 'active' : '' }}">
                <i class="bi bi-stopwatch"></i> My Attendance
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('user.claims.index') }}"
               class="nav-link {{ request()->routeIs('user.claims.index') ? 'active' : '' }}">
                <i class="bi bi-receipt-cutoff"></i> My Claims
            </a>
        </div>
        @if(Auth::user()->employee && \App\Models\Employee::where('manager_id', Auth::user()->employee->id)->exists())
        <div class="nav-item">
            <a href="{{ route('user.claims.team') }}"
               class="nav-link {{ request()->routeIs('user.claims.team*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Team Claims
                @php $__pendingTeamClaims = \App\Models\ExpenseClaim::whereIn('employee_id', \App\Models\Employee::where('manager_id', Auth::user()->employee->id)->pluck('id'))->where('status', 'submitted')->count(); @endphp
                @if($__pendingTeamClaims > 0)
                <span class="badge bg-warning text-dark ms-auto" style="font-size:10px;">{{ $__pendingTeamClaims }}</span>
                @endif
            </a>
        </div>
        @endif

        {{-- 5. Profile --}}
        <div class="nav-item">
            <a href="{{ route('profile') }}"
               class="nav-link {{ request()->routeIs('profile') ? 'active' : '' }}">
                <i class="bi bi-person-circle"></i> Profile
            </a>
        </div>

        {{-- 6. Account --}}
        <div class="nav-item">
            <a href="{{ route('account') }}"
               class="nav-link {{ request()->routeIs('account') ? 'active' : '' }}">
                <i class="bi bi-gear"></i> Account
            </a>
        </div>

        {{-- ══════════════════════════════════════════════════
             IT MENU
             Order: Dashboard → Onboarding → Offboarding → Employee Listing → [extras] → Profile → Account
             ══════════════════════════════════════════════════ --}}
        @elseif(Auth::user()->isIt())
        <div class="sidebar-section">IT Menu</div>

        {{-- 1. Dashboard --}}
        <div class="nav-item">
            <a href="{{ route('it.dashboard') }}"
               class="nav-link {{ request()->routeIs('it.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </div>

        {{-- 2. Onboarding --}}
        <div class="nav-item">
            <a href="{{ route('it.onboarding') }}"
               class="nav-link {{ request()->routeIs('it.onboarding') ? 'active' : '' }}">
                <i class="bi bi-person-plus"></i> Onboarding
            </a>
        </div>

        {{-- 3. Offboarding --}}
        <div class="nav-item">
            <a href="{{ route('it.offboarding.index') }}"
               class="nav-link {{ request()->routeIs('it.offboarding.*') ? 'active' : '' }}">
                <i class="bi bi-box-arrow-right"></i> Offboarding
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('employees.index') }}"
               class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Employee Listing
            </a>
        </div>

        {{-- Extras: Assets, AARF, Tasks (above Profile) --}}
        <div class="nav-item">
            <a href="{{ route('assets.index') }}"
               class="nav-link {{ request()->routeIs('assets.*') ? 'active' : '' }}">
                <i class="bi bi-laptop"></i> Asset Listing
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('it.tasks') }}"
               class="nav-link {{ request()->routeIs('it.tasks') ? 'active' : '' }}">
                <i class="bi bi-list-task"></i> Task Management
                @php $myTasks = \App\Models\ItTask::where('assigned_to', Auth::id())->where('status','!=','done')->count(); @endphp
                @if($myTasks > 0)
                    <span class="badge bg-warning text-dark ms-auto" style="font-size:10px;">{{ $myTasks }}</span>
                @endif
            </a>
        </div>

        @if(Auth::user()->isItManager())
        <div class="nav-item">
            <a href="{{ route('announcements.index') }}"
               class="nav-link {{ request()->routeIs('announcements.*') ? 'active' : '' }}">
                <i class="bi bi-megaphone"></i> Announcements
            </a>
        </div>
        @endif

        {{-- ── Support (Ticketing) ── --}}
        <div class="sidebar-section">Support</div>
        <div class="nav-item">
            <a href="{{ route('tickets.manage') }}"
               class="nav-link {{ request()->routeIs('tickets.manage') || (request()->routeIs('tickets.show') && request()->query('from') === 'manage') ? 'active' : '' }}">
                <i class="bi bi-headset"></i> Ticket Management
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('tickets.index') }}"
               class="nav-link {{ request()->routeIs('tickets.index') || request()->routeIs('tickets.create') ? 'active' : '' }}">
                <i class="bi bi-ticket-perforated"></i> My Tickets
            </a>
        </div>

        {{-- Self-Service (IT staff are also employees) --}}
        <div class="sidebar-section">Self-Service</div>
        <div class="nav-item">
            <a href="{{ route('user.leave.index') }}"
               class="nav-link {{ request()->routeIs('user.leave.index') ? 'active' : '' }}">
                <i class="bi bi-calendar-plus"></i> My Leave
            </a>
        </div>
        @if(Auth::user()->employee && \App\Models\Employee::where('manager_id', Auth::user()->employee->id)->exists())
        <div class="nav-item">
            <a href="{{ route('user.leave.team') }}"
               class="nav-link {{ request()->routeIs('user.leave.team*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Team Leave
                @php $__pendingTeam = \App\Models\LeaveApplication::whereIn('employee_id', \App\Models\Employee::where('manager_id', Auth::user()->employee->id)->pluck('id'))->where('status', 'pending')->count(); @endphp
                @if($__pendingTeam > 0)
                <span class="badge bg-warning text-dark ms-auto" style="font-size:10px;">{{ $__pendingTeam }}</span>
                @endif
            </a>
        </div>
        @endif
        <div class="nav-item">
            <a href="{{ route('user.payroll.index') }}"
               class="nav-link {{ request()->routeIs('user.payroll.index') || request()->routeIs('user.payroll.payslip') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i> My Payslips
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('user.payroll.ea-form') }}"
               class="nav-link {{ request()->routeIs('user.payroll.ea-form') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text"></i> My EA Form
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('user.attendance.index') }}"
               class="nav-link {{ request()->routeIs('user.attendance.*') ? 'active' : '' }}">
                <i class="bi bi-stopwatch"></i> My Attendance
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('user.claims.index') }}"
               class="nav-link {{ request()->routeIs('user.claims.index') ? 'active' : '' }}">
                <i class="bi bi-receipt-cutoff"></i> My Claims
            </a>
        </div>
        @if(Auth::user()->employee && \App\Models\Employee::where('manager_id', Auth::user()->employee->id)->exists())
        <div class="nav-item">
            <a href="{{ route('user.claims.team') }}"
               class="nav-link {{ request()->routeIs('user.claims.team*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Team Claims
                @php $__pendingTeamClaims = \App\Models\ExpenseClaim::whereIn('employee_id', \App\Models\Employee::where('manager_id', Auth::user()->employee->id)->pluck('id'))->where('status', 'submitted')->count(); @endphp
                @if($__pendingTeamClaims > 0)
                <span class="badge bg-warning text-dark ms-auto" style="font-size:10px;">{{ $__pendingTeamClaims }}</span>
                @endif
            </a>
        </div>
        @endif

        {{-- 5. Profile --}}
        <div class="nav-item">
            <a href="{{ route('profile') }}"
               class="nav-link {{ request()->routeIs('profile') ? 'active' : '' }}">
                <i class="bi bi-person-circle"></i> Profile
            </a>
        </div>

        {{-- 6. Account --}}
        <div class="nav-item">
            <a href="{{ route('account') }}"
               class="nav-link {{ request()->routeIs('account') ? 'active' : '' }}">
                <i class="bi bi-gear"></i> Account
            </a>
        </div>

        {{-- ══════════════════════════════════════════════════
             STANDARD USER MENU
             ══════════════════════════════════════════════════ --}}
        @else
        <div class="sidebar-section">Menu</div>
        <div class="nav-item">
            <a href="{{ route('user.dashboard') }}"
               class="nav-link {{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
                <i class="bi bi-house"></i> Dashboard
            </a>
        </div>
        @if(Auth::user()->employee?->work_role === 'manager')
        <div class="nav-item">
            <a href="{{ route('announcements.index') }}"
               class="nav-link {{ request()->routeIs('announcements.*') ? 'active' : '' }}">
                <i class="bi bi-megaphone"></i> Announcements
            </a>
        </div>
        @endif

        {{-- ── Support (Ticketing) ── --}}
        <div class="sidebar-section">Support</div>
        @if(Auth::user()->canAccessTicketManagement())
        <div class="nav-item">
            <a href="{{ route('tickets.manage') }}"
               class="nav-link {{ request()->routeIs('tickets.manage') || (request()->routeIs('tickets.show') && request()->query('from') === 'manage') ? 'active' : '' }}">
                <i class="bi bi-headset"></i> Ticket Management
            </a>
        </div>
        @endif
        <div class="nav-item">
            <a href="{{ route('tickets.index') }}"
               class="nav-link {{ request()->routeIs('tickets.index') || request()->routeIs('tickets.create') ? 'active' : '' }}">
                <i class="bi bi-ticket-perforated"></i> My Tickets
            </a>
        </div>

        {{-- Self-Service --}}
        <div class="sidebar-section">Self-Service</div>
        <div class="nav-item">
            <a href="{{ route('user.leave.index') }}"
               class="nav-link {{ request()->routeIs('user.leave.index') ? 'active' : '' }}">
                <i class="bi bi-calendar-plus"></i> My Leave
            </a>
        </div>
        @if(Auth::user()->employee && \App\Models\Employee::where('manager_id', Auth::user()->employee->id)->exists())
        <div class="nav-item">
            <a href="{{ route('user.leave.team') }}"
               class="nav-link {{ request()->routeIs('user.leave.team*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Team Leave
                @php $__pendingTeam = \App\Models\LeaveApplication::whereIn('employee_id', \App\Models\Employee::where('manager_id', Auth::user()->employee->id)->pluck('id'))->where('status', 'pending')->count(); @endphp
                @if($__pendingTeam > 0)
                <span class="badge bg-warning text-dark ms-auto" style="font-size:10px;">{{ $__pendingTeam }}</span>
                @endif
            </a>
        </div>
        @endif
        <div class="nav-item">
            <a href="{{ route('user.payroll.index') }}"
               class="nav-link {{ request()->routeIs('user.payroll.index') || request()->routeIs('user.payroll.payslip') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i> My Payslips
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('user.payroll.ea-form') }}"
               class="nav-link {{ request()->routeIs('user.payroll.ea-form') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text"></i> My EA Form
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('user.attendance.index') }}"
               class="nav-link {{ request()->routeIs('user.attendance.*') ? 'active' : '' }}">
                <i class="bi bi-stopwatch"></i> My Attendance
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('user.claims.index') }}"
               class="nav-link {{ request()->routeIs('user.claims.index') ? 'active' : '' }}">
                <i class="bi bi-receipt-cutoff"></i> My Claims
            </a>
        </div>
        @if(Auth::user()->employee && \App\Models\Employee::where('manager_id', Auth::user()->employee->id)->exists())
        <div class="nav-item">
            <a href="{{ route('user.claims.team') }}"
               class="nav-link {{ request()->routeIs('user.claims.team*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Team Claims
                @php $__pendingTeamClaims = \App\Models\ExpenseClaim::whereIn('employee_id', \App\Models\Employee::where('manager_id', Auth::user()->employee->id)->pluck('id'))->where('status', 'submitted')->count(); @endphp
                @if($__pendingTeamClaims > 0)
                <span class="badge bg-warning text-dark ms-auto" style="font-size:10px;">{{ $__pendingTeamClaims }}</span>
                @endif
            </a>
        </div>
        @endif

        <div class="nav-item">
            <a href="{{ route('profile') }}"
               class="nav-link {{ request()->routeIs('profile') ? 'active' : '' }}">
                <i class="bi bi-person-circle"></i> Profile
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('account') }}"
               class="nav-link {{ request()->routeIs('account') ? 'active' : '' }}">
                <i class="bi bi-gear"></i> Account
            </a>
        </div>
        @endif

        {{-- Accounting Module (non-superadmin roles with finance access) --}}
        @if(!Auth::user()->isSuperadmin() && Auth::user()->canViewAccounting())
        <div class="sidebar-section">Accounting</div>
        <div class="nav-item">
            <a href="{{ route('accounting.dashboard') }}"
               class="nav-link {{ request()->routeIs('accounting.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('accounting.chart-of-accounts.index') }}"
               class="nav-link {{ request()->routeIs('accounting.chart-of-accounts.*') ? 'active' : '' }}">
                <i class="bi bi-diagram-3"></i> Chart of Accounts
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('accounting.journal-entries.index') }}"
               class="nav-link {{ request()->routeIs('accounting.journal-entries.*') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i> General Ledger
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('accounting.invoices.index') }}"
               class="nav-link {{ request()->routeIs('accounting.invoices.*', 'accounting.customers.*', 'accounting.customer-payments.*', 'accounting.credit-notes.*') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i> Receivables
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('accounting.bills.index') }}"
               class="nav-link {{ request()->routeIs('accounting.bills.*', 'accounting.vendors.*', 'accounting.vendor-payments.*', 'accounting.purchase-orders.*') ? 'active' : '' }}">
                <i class="bi bi-receipt-cutoff"></i> Payables
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('accounting.banking.index') }}"
               class="nav-link {{ request()->routeIs('accounting.banking.*', 'accounting.bank-transfers.*') ? 'active' : '' }}">
                <i class="bi bi-bank"></i> Banking
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('accounting.tax.index') }}"
               class="nav-link {{ request()->routeIs('accounting.tax.*', 'accounting.tax-returns.*') ? 'active' : '' }}">
                <i class="bi bi-percent"></i> Tax
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('accounting.fixed-assets.index') }}"
               class="nav-link {{ request()->routeIs('accounting.fixed-assets.*', 'accounting.asset-categories.*') ? 'active' : '' }}">
                <i class="bi bi-building"></i> Fixed Assets
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('accounting.budgets.index') }}"
               class="nav-link {{ request()->routeIs('accounting.budgets.*') ? 'active' : '' }}">
                <i class="bi bi-pie-chart"></i> Budgets
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('accounting.reports.trial-balance') }}"
               class="nav-link {{ request()->routeIs('accounting.reports.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-bar-graph"></i> Reports
            </a>
        </div>
        @if(Auth::user()->canUseAiChat())
        <div class="nav-item">
            <a href="{{ route('accounting.ai.invoice-scanner') }}"
               class="nav-link {{ request()->routeIs('accounting.ai.*') ? 'active' : '' }}">
                <i class="bi bi-robot"></i> AI Tools
            </a>
        </div>
        @endif
        @if(Auth::user()->canManageAccounting())
        <div class="nav-item">
            <a href="{{ route('accounting.settings') }}"
               class="nav-link {{ request()->routeIs('accounting.settings*') ? 'active' : '' }}">
                <i class="bi bi-sliders"></i> Settings
            </a>
        </div>
        @endif
        @endif

        {{-- 7. Logout --}}
        <div class="sidebar-section">Session</div>
        <div class="nav-item">
            <form action="{{ route('logout') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="nav-link w-100 border-0 bg-transparent text-start">
                    <i class="bi bi-box-arrow-left"></i> Logout
                </button>
            </form>
        </div>

    </div>

    <div class="sidebar-footer">
        <div class="user-chip d-flex align-items-center gap-2">
            <img src="{{ Auth::user()->profile_picture_url }}" alt="Avatar"
                 class="rounded-circle flex-shrink-0" style="width:34px;height:34px;object-fit:cover;">
            <div style="overflow:hidden;flex:1;">
                <div style="color:var(--ink);font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    {{ Auth::user()->name }}
                </div>
                <span class="role-badge">{{ str_replace('_', ' ', ucwords(Auth::user()->role)) }}</span>
            </div>
        </div>
    </div>
</nav>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-2">
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu">
                <i class="bi bi-list"></i>
            </button>
            <h4>@yield('page-title', 'Dashboard')</h4>
        </div>
        <div class="d-flex align-items-center gap-3">
            {{-- ── Notifications Bell ─────────────────────────────────── --}}
            <div class="dropdown" id="notifDropdown">
                <button id="notifBellBtn" type="button"
                        class="btn btn-sm position-relative p-1 border-0 bg-transparent"
                        data-bs-toggle="dropdown" data-bs-auto-close="outside"
                        aria-expanded="false" aria-label="Notifications"
                        style="font-size:20px;color:var(--ink-muted, #475569);">
                    <i class="bi bi-bell"></i>
                    <span id="notifBadge"
                          class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"
                          style="font-size:10px;padding:3px 5px;">0</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow p-0"
                     style="width:340px;">
                    <div style="max-height:440px;overflow:hidden;display:flex;flex-direction:column;">
                        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom"
                             style="background:var(--bg-soft, #f8fafc);">
                            <span class="fw-semibold small">Notifications</span>
                            <a href="#" id="notifMarkAllBtn" class="small text-decoration-none">Mark all as read</a>
                        </div>
                        <div id="notifList" style="overflow-y:auto;flex:1;">
                            <div class="text-center text-muted small py-4" id="notifLoading">Loading…</div>
                        </div>
                    </div>
                </div>
            </div>
            <span class="text-muted small">
                <i class="bi bi-calendar3 me-1"></i>{{ now()->format('d/m/Y') }}
            </span>
        </div>
    </div>
    <div class="content-area">
        @include('partials.trial-banner')
        @foreach(['success','error','info','warning'] as $type)
            @if(session($type))
                <div class="alert alert-{{ $type === 'error' ? 'danger' : $type }} alert-dismissible fade show">
                    <i class="bi bi-{{ $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-circle' : 'info-circle') }} me-2"></i>
                    {{ session($type) }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
        @endforeach
        @yield('content')
    </div>
</div>
@else
    @yield('content')
@endauth

<script nonce="{{ $cspNonce ?? '' }}" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
function openSidebar() {
    document.querySelector('.sidebar')?.classList.add('sidebar-open');
    document.getElementById('sidebarOverlay')?.classList.add('active');
}
function closeSidebar() {
    document.querySelector('.sidebar')?.classList.remove('sidebar-open');
    document.getElementById('sidebarOverlay')?.classList.remove('active');
}
// Bind sidebar open/close via addEventListener (CSP nonce-compatible)
document.getElementById('sidebarOverlay')?.addEventListener('click', closeSidebar);
document.getElementById('hamburgerBtn')?.addEventListener('click', openSidebar);
// Close sidebar when a nav link is clicked on mobile
document.querySelectorAll('.sidebar-nav .nav-link').forEach(function(link) {
    link.addEventListener('click', function() {
        if (window.innerWidth < 768) closeSidebar();
    });
});

// ── Theme switcher ────────────────────────────────────────────────────────
function setTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    // Update active state on theme option cards if present
    document.querySelectorAll('.theme-option').forEach(function(el) {
        el.classList.remove('border-primary', 'shadow-sm');
    });
    var active = document.querySelector('.theme-option[onclick="setTheme(\'' + theme + '\')"]');
    if (active) active.classList.add('border-primary', 'shadow-sm');
}
</script>
@auth
{{-- ── Idle Session Timeout ───────────────────────────────────────────────
     Logs the user out after 60 seconds of inactivity.
     "Activity" = any mouse move, keypress, click, scroll, or touch.
     A 30-second warning modal appears before the logout fires, giving
     the user a chance to click "Stay Logged In" and reset the timer.
     The logout is performed via a real POST to /logout (with CSRF token)
     so the server-side session is fully invalidated — not just a redirect.
──────────────────────────────────────────────────────────────────────── --}}
<div class="modal fade" id="idleWarningModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="idleWarningLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:380px;">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0" style="background:#fff3cd;">
                <h6 class="modal-title fw-bold text-warning-emphasis" id="idleWarningLabel">
                    <i class="bi bi-clock-history me-2"></i>Session Expiring
                </h6>
            </div>
            <div class="modal-body pt-2 text-center">
                <p class="mb-1" style="font-size:14px;">You have been inactive. You will be logged out in</p>
                <div id="idleCountdown" style="font-size:36px;font-weight:700;color:#dc3545;line-height:1.1;">30</div>
                <p class="text-muted mt-1 mb-0" style="font-size:12px;">seconds</p>
            </div>
            <div class="modal-footer border-0 justify-content-center pt-0 pb-3">
                <button type="button" class="btn btn-primary btn-sm px-4" id="idleStayBtn">
                    <i class="bi bi-arrow-clockwise me-1"></i>Stay Logged In
                </button>
            </div>
        </div>
    </div>
</div>
<form id="idleLogoutForm" action="{{ route('logout') }}" method="POST" style="display:none;">
    @csrf
</form>
<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    // ── Configuration ─────────────────────────────────────────────────────
    var IDLE_TIMEOUT_MS  = 15 * 60 * 1000;  // 15 min of inactivity → trigger warning
    var WARNING_DURATION = 30;          // seconds of countdown shown in modal
    // ─────────────────────────────────────────────────────────────────────

    var idleTimer      = null;
    var countdownTimer = null;
    var countdown      = WARNING_DURATION;
    var modal          = null;
    var modalEl        = document.getElementById('idleWarningModal');
    var countdownEl    = document.getElementById('idleCountdown');
    var stayBtn        = document.getElementById('idleStayBtn');

    // Lazy-init Bootstrap modal (Bootstrap is loaded after this script)
    function getModal() {
        if (!modal) modal = new bootstrap.Modal(modalEl);
        return modal;
    }

    // ── Reset idle timer on any user activity ────────────────────────────
    function resetTimer() {
        clearTimeout(idleTimer);
        // Only reset if the warning modal is NOT currently open
        if (!modalEl.classList.contains('show')) {
            idleTimer = setTimeout(showWarning, IDLE_TIMEOUT_MS);
        }
    }

    // ── Show the 30-second countdown warning modal ───────────────────────
    function showWarning() {
        countdown   = WARNING_DURATION;
        countdownEl.textContent = countdown;
        getModal().show();

        clearInterval(countdownTimer);
        countdownTimer = setInterval(function () {
            countdown--;
            countdownEl.textContent = countdown;
            if (countdown <= 0) {
                clearInterval(countdownTimer);
                doLogout();
            }
        }, 1000);
    }

    // ── Perform server-side logout via form POST ─────────────────────────
    function doLogout() {
        getModal().hide();
        document.getElementById('idleLogoutForm').submit();
    }

    // ── "Stay Logged In" button — dismiss modal and restart timer ────────
    stayBtn.addEventListener('click', function () {
        clearInterval(countdownTimer);
        getModal().hide();
        resetTimer(); // restart the 60-second idle clock
    });

    // ── Activity events that reset the idle timer ────────────────────────
    ['mousemove', 'keydown', 'mousedown', 'touchstart', 'scroll', 'click'].forEach(function (evt) {
        document.addEventListener(evt, resetTimer, { passive: true });
    });

    // ── Start the timer when the page loads ──────────────────────────────
    resetTimer();
})();
</script>
@endauth

@auth
    @include('partials.ai-assistant-drawer')
@endauth

@auth
{{-- ── Notifications bell: fetch, render, poll, mark read ─────────────── --}}
<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    'use strict';

    var bellBtn    = document.getElementById('notifBellBtn');
    var badge      = document.getElementById('notifBadge');
    var list       = document.getElementById('notifList');
    var markAllBtn = document.getElementById('notifMarkAllBtn');
    var csrfMeta   = document.querySelector('meta[name="csrf-token"]');
    var csrfToken  = csrfMeta ? csrfMeta.getAttribute('content') : '';

    if (!bellBtn) return;

    var POLL_INTERVAL = 60000; // 60s
    var pollTimer = null;

    function escapeHtml(s) {
        if (s == null) return '';
        return String(s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function renderBadge(count) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }
    }

    function renderList(items) {
        if (!items || items.length === 0) {
            list.innerHTML = '<div class="text-center text-muted small py-4">No notifications yet.</div>';
            return;
        }
        var html = '';
        items.forEach(function (n) {
            var d = n.data || {};
            var icon  = d.icon || 'bi-bell';
            var color = d.color || 'secondary';
            var msg   = d.message || '';
            var url   = d.url || '#';
            var unreadStyle = n.read
                ? 'background:#fff;'
                : 'background:#eff6ff;border-left:3px solid #2563eb;';
            html +=
                '<a href="' + escapeHtml(url) + '" data-notif-id="' + escapeHtml(n.id) + '"' +
                '   class="notif-item d-flex gap-2 px-3 py-2 text-decoration-none border-bottom"' +
                '   style="' + unreadStyle + 'color:#1e293b;">' +
                '  <div class="flex-shrink-0">' +
                '    <i class="bi ' + escapeHtml(icon) + ' text-' + escapeHtml(color) + '" style="font-size:18px;"></i>' +
                '  </div>' +
                '  <div style="min-width:0;flex:1;">' +
                '    <div class="small" style="line-height:1.35;">' + escapeHtml(msg) + '</div>' +
                '    <div class="text-muted" style="font-size:11px;">' + escapeHtml(n.time_ago || '') + '</div>' +
                '  </div>' +
                '</a>';
        });
        list.innerHTML = html;

        // Mark a notification read on click. Don't preventDefault — let
        // the browser navigate via the href in parallel.
        list.querySelectorAll('.notif-item').forEach(function (el) {
            el.addEventListener('click', function () {
                var id = el.getAttribute('data-notif-id');
                fetch('/notifications/' + encodeURIComponent(id) + '/read', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    credentials: 'same-origin'
                }).catch(function () {});
            });
        });
    }

    function fetchNotifications() {
        if (document.visibilityState !== 'visible') return;
        fetch('/notifications', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
            if (!data) return;
            renderBadge(data.unread_count || 0);
            renderList(data.notifications || []);
        })
        .catch(function () { /* silent */ });
    }

    markAllBtn.addEventListener('click', function (e) {
        e.preventDefault();
        // EIAAW route is /notifications/mark-all-read (not Claritas's /read-all).
        fetch('/notifications/mark-all-read', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function () { fetchNotifications(); });
    });

    function startPolling() {
        if (pollTimer) return;
        pollTimer = setInterval(fetchNotifications, POLL_INTERVAL);
    }
    function stopPolling() {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') { fetchNotifications(); startPolling(); }
        else { stopPolling(); }
    });

    // Initial fetch + start polling
    fetchNotifications();
    startPolling();
})();
</script>
@endauth

@stack('scripts')
</body>
</html>