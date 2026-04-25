@extends('layouts.app')
@section('title', 'System Overview')
@section('page-title', 'System Overview')

@section('content')
<style>
    .overview-hero {
        background: linear-gradient(135deg, #0b5ed7 0%, #0d6efd 40%, #6610f2 100%);
        border-radius: 1rem;
        color: #fff;
        padding: 3rem 2rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }
    .overview-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 500px;
        height: 500px;
        background: rgba(255,255,255,0.05);
        border-radius: 50%;
    }
    .overview-hero::after {
        content: '';
        position: absolute;
        bottom: -30%;
        left: 10%;
        width: 300px;
        height: 300px;
        background: rgba(255,255,255,0.03);
        border-radius: 50%;
    }
    .overview-hero h1 { font-size: 2.2rem; font-weight: 800; letter-spacing: -0.5px; }
    .overview-hero .lead { font-size: 1.1rem; opacity: 0.9; max-width: 700px; }
    .stat-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(255,255,255,0.15);
        backdrop-filter: blur(10px);
        border-radius: 2rem;
        padding: 0.5rem 1.2rem;
        font-size: 0.85rem;
        font-weight: 600;
        border: 1px solid rgba(255,255,255,0.2);
    }
    .stat-pill .num { font-size: 1.3rem; font-weight: 800; }

    .module-card {
        border: none;
        border-radius: 1rem;
        transition: all 0.3s ease;
        overflow: hidden;
        height: 100%;
    }
    .module-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.12);
    }
    .module-card .card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: none;
        font-weight: 700;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .module-card .card-header i { font-size: 1.5rem; }
    .module-card .card-body { padding: 1rem 1.5rem 1.5rem; }
    .module-card .feature-list { list-style: none; padding: 0; margin: 0; }
    .module-card .feature-list li {
        padding: 0.35rem 0;
        font-size: 0.875rem;
        color: #495057;
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
    }
    .module-card .feature-list li::before {
        content: '✓';
        color: #198754;
        font-weight: 700;
        flex-shrink: 0;
        margin-top: 1px;
    }

    .mc-onboarding .card-header  { background: linear-gradient(135deg, #0d6efd, #0b5ed7); color: #fff; }
    .mc-employee .card-header    { background: linear-gradient(135deg, #198754, #157347); color: #fff; }
    .mc-offboarding .card-header { background: linear-gradient(135deg, #dc3545, #b02a37); color: #fff; }
    .mc-assets .card-header      { background: linear-gradient(135deg, #fd7e14, #e8590c); color: #fff; }
    .mc-leave .card-header       { background: linear-gradient(135deg, #20c997, #0ca678); color: #fff; }
    .mc-payroll .card-header     { background: linear-gradient(135deg, #6610f2, #520dc2); color: #fff; }
    .mc-attendance .card-header  { background: linear-gradient(135deg, #0dcaf0, #0aa3c4); color: #fff; }
    .mc-claims .card-header      { background: linear-gradient(135deg, #d63384, #ab296a); color: #fff; }
    .mc-reports .card-header     { background: linear-gradient(135deg, #1a237e, #283593); color: #fff; }
    .mc-accounting .card-header  { background: linear-gradient(135deg, #00695c, #00897b); color: #fff; }
    .mc-announcements .card-header { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; }
    .mc-knowledge .card-header { background: linear-gradient(135deg, #6366f1, #4338ca); color: #fff; }
    .mc-company .card-header { background: linear-gradient(135deg, #0ea5e9, #0284c7); color: #fff; }

    .flow-section {
        background: #f8f9fa;
        border-radius: 1rem;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    .flow-section h3 { color: #0b5ed7; font-weight: 700; margin-bottom: 1rem; }

    .flow-step {
        text-align: center;
        position: relative;
    }
    .flow-step .step-circle {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.75rem;
        font-size: 1.5rem;
        color: #fff;
        font-weight: 700;
    }
    .flow-step .step-label { font-weight: 600; font-size: 0.9rem; color: #212529; }
    .flow-step .step-desc { font-size: 0.8rem; color: #6c757d; margin-top: 0.25rem; }

    .flow-arrow {
        display: flex;
        align-items: center;
        justify-content: center;
        padding-top: 1rem;
    }
    .flow-arrow i { font-size: 1.5rem; color: #adb5bd; }

    .role-card {
        border-radius: 0.75rem;
        padding: 1.25rem;
        text-align: center;
        transition: all 0.3s ease;
        height: 100%;
    }
    .role-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
    .role-card i { font-size: 2rem; margin-bottom: 0.5rem; display: block; }
    .role-card h6 { font-weight: 700; margin-bottom: 0.5rem; }
    .role-card .role-perms { font-size: 0.78rem; color: #6c757d; text-align: left; }

    .security-badge {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        background: #fff;
        border-radius: 0.75rem;
        border: 1px solid #e9ecef;
        height: 100%;
    }
    .security-badge i { font-size: 1.5rem; flex-shrink: 0; }
    .security-badge .sb-title { font-weight: 600; font-size: 0.9rem; }
    .security-badge .sb-desc { font-size: 0.78rem; color: #6c757d; }

    .compliance-stamp {
        background: linear-gradient(135deg, #198754, #0f5132);
        color: #fff;
        border-radius: 1rem;
        padding: 2rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .compliance-stamp .score {
        font-size: 4rem;
        font-weight: 900;
        line-height: 1;
    }
    .compliance-stamp .score-label {
        font-size: 1rem;
        opacity: 0.8;
    }
    .compliance-stamp.grade-a { background: linear-gradient(135deg, #198754, #0f5132); }
    .compliance-stamp.grade-b { background: linear-gradient(135deg, #0d6efd, #0a58ca); }
    .compliance-stamp.grade-c { background: linear-gradient(135deg, #ffc107, #e0a800); color: #212529; }
    .compliance-stamp.grade-d { background: linear-gradient(135deg, #fd7e14, #dc6a10); }
    .compliance-stamp.grade-f { background: linear-gradient(135deg, #dc3545, #b02a37); }

    .score-ring {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        position: relative;
    }
    .score-ring svg { position: absolute; top: 0; left: 0; transform: rotate(-90deg); }
    .score-ring .score-inner {
        font-size: 2.5rem;
        font-weight: 900;
        line-height: 1;
        z-index: 1;
    }
    .score-ring .score-inner small { font-size: 1rem; font-weight: 600; opacity: 0.7; }

    .check-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0; font-size: 0.82rem; }
    .check-item .check-dot {
        width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
    }
    .check-dot.pass { background: #198754; }
    .check-dot.warn { background: #ffc107; }
    .check-dot.fail { background: #dc3545; }

    .update-card {
        border-radius: 0.75rem;
        border: 1px solid #e9ecef;
        padding: 1rem 1.25rem;
        transition: all 0.2s ease;
    }
    .update-card:hover { border-color: #0d6efd; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .severity-critical { border-left: 4px solid #dc3545; }
    .severity-major { border-left: 4px solid #fd7e14; }
    .severity-minor { border-left: 4px solid #ffc107; }
    .severity-patch { border-left: 4px solid #0dcaf0; }
    .severity-current { border-left: 4px solid #198754; }

    .health-bar { height: 8px; border-radius: 4px; background: #e9ecef; overflow: hidden; }
    .health-bar-fill { height: 100%; border-radius: 4px; transition: width 0.5s ease; }

    .refresh-btn {
        background: rgba(255,255,255,0.15);
        border: 1px solid rgba(255,255,255,0.3);
        color: inherit;
        border-radius: 0.5rem;
        padding: 0.25rem 0.75rem;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .refresh-btn:hover { background: rgba(255,255,255,0.25); }
    .refresh-btn.dark { background: rgba(0,0,0,0.05); border-color: #dee2e6; color: #495057; }
    .refresh-btn.dark:hover { background: rgba(0,0,0,0.1); }
    .refresh-btn .spinner-border { width: 14px; height: 14px; border-width: 2px; }

    .email-flow-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .email-flow-item:last-child { border-bottom: none; }
    .email-count {
        background: #0d6efd;
        color: #fff;
        border-radius: 50%;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    .tech-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.8rem;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 2rem;
        font-size: 0.82rem;
        font-weight: 600;
        color: #495057;
    }

    @media print {
        .overview-hero { background: #0d6efd !important; -webkit-print-color-adjust: exact; }
        .module-card:hover { transform: none; box-shadow: none; }
    }
</style>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- HERO SECTION --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="overview-hero">
    <div class="position-relative" style="z-index:1;">
        <h1><i class="bi bi-diagram-3 me-2"></i>HRM &amp; Finance System</h1>
        <p class="lead mb-4">
            Complete multi-role HR management &amp; AI-powered accounting platform covering the entire employee
            lifecycle — from pre-hire onboarding through payroll, leave, attendance, and expense claims to exit
            offboarding — plus full double-entry accounting with AI invoice scanning and chatbot.
            Built for Malaysian companies with full statutory compliance.
        </p>
        <div class="d-flex flex-wrap gap-3">
            <span class="stat-pill"><span class="num">{{ \App\Models\Employee::whereNull('active_until')->count() }}</span> Active Employees</span>
            <span class="stat-pill"><span class="num">{{ \App\Models\User::where('is_active', true)->count() }}</span> User Accounts</span>
            <span class="stat-pill"><span class="num">{{ $meta['module_count'] }}</span> Modules</span>
            <span class="stat-pill"><span class="num">{{ $meta['controllers'] }}</span> Controllers</span>
            <span class="stat-pill"><span class="num">{{ $meta['models'] }}</span> Models</span>
            <span class="stat-pill"><span class="num">{{ $meta['views'] }}</span> Views</span>
            <span class="stat-pill"><span class="num">{{ $meta['tables'] }}</span> DB Tables</span>
            <span class="stat-pill"><span class="num">{{ $meta['endpoints'] }}</span> Endpoints</span>
            <span class="stat-pill"><span class="num">{{ $meta['migrations'] }}</span> Migrations</span>
            <span class="stat-pill"><span class="num">{{ $meta['mail_classes'] }}</span> Automated Emails</span>
            <span class="stat-pill"><span class="num" id="hero-security-score">{{ $securityScore['score'] }}/100</span> Security Score</span>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- EMPLOYEE LIFECYCLE FLOW --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="flow-section">
    <h3><i class="bi bi-arrow-right-circle me-2"></i>Employee Lifecycle Flow</h3>
    <p class="text-muted mb-4">The complete journey of an employee through the system — each stage is fully automated with email notifications and task tracking.</p>

    <div class="row g-0 align-items-start">
        <div class="col flow-step">
            <div class="step-circle" style="background:#0d6efd;">1</div>
            <div class="step-label">Onboarding</div>
            <div class="step-desc">HR creates record,<br>invite email sent</div>
        </div>
        <div class="col-auto flow-arrow"><i class="bi bi-chevron-right"></i></div>
        <div class="col flow-step">
            <div class="step-circle" style="background:#6610f2;">2</div>
            <div class="step-label">Registration</div>
            <div class="step-desc">New hire fills form,<br>sets password</div>
        </div>
        <div class="col-auto flow-arrow"><i class="bi bi-chevron-right"></i></div>
        <div class="col flow-step">
            <div class="step-circle" style="background:#198754;">3</div>
            <div class="step-label">Active Employee</div>
            <div class="step-desc">Full HRM services:<br>payroll, leave, claims</div>
        </div>
        <div class="col-auto flow-arrow"><i class="bi bi-chevron-right"></i></div>
        <div class="col flow-step">
            <div class="step-circle" style="background:#dc3545;">4</div>
            <div class="step-label">Offboarding</div>
            <div class="step-desc">Exit process, asset<br>return, IT cleanup</div>
        </div>
        <div class="col-auto flow-arrow"><i class="bi bi-chevron-right"></i></div>
        <div class="col flow-step">
            <div class="step-circle" style="background:#212529;">5</div>
            <div class="step-label">Archived</div>
            <div class="step-desc">Permanent record<br>retained</div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- 8 MODULE CARDS --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<h3 class="fw-bold mb-3"><i class="bi bi-grid-3x3-gap text-primary me-2"></i>System Modules</h3>
<p class="text-muted mb-4">Thirteen integrated modules covering human resource management, AI-powered accounting, and administration.</p>

<div class="row g-4 mb-5">
    {{-- Onboarding --}}
    <div class="col-md-6 col-lg-3">
        <div class="card module-card mc-onboarding">
            <div class="card-header"><i class="bi bi-person-plus"></i> Onboarding</div>
            <div class="card-body">
                <ul class="feature-list">
                    <li>Automated invite email with secure token</li>
                    <li>Multi-step self-service form (9 sections)</li>
                    <li>Staging JSON for pre-hire data</li>
                    <li>Auto-activation on start date</li>
                    <li>IT task auto-generation</li>
                    <li>Consent tracking & re-acknowledgement</li>
                    <li>CSV export with date/company filters</li>
                    <li>Overview dashboard (YTD &amp; monthly by company)</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Employee Management --}}
    <div class="col-md-6 col-lg-3">
        <div class="card module-card mc-employee">
            <div class="card-header"><i class="bi bi-people"></i> Employee Mgmt</div>
            <div class="card-body">
                <ul class="feature-list">
                    <li>Central employee record (50+ fields)</li>
                    <li>Personal, work, education, family details</li>
                    <li>Employment contracts management</li>
                    <li>NRIC/passport multi-file upload</li>
                    <li>Edit log with re-consent flow</li>
                    <li>Manager hierarchy tracking</li>
                    <li>Self-service profile editing</li>
                    <li>CSV import/export (29 fields)</li>
                    <li>Overview cards (by company, dept, type with filters)</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Offboarding --}}
    <div class="col-md-6 col-lg-3">
        <div class="card module-card mc-offboarding">
            <div class="card-header"><i class="bi bi-person-dash"></i> Offboarding</div>
            <div class="card-body">
                <ul class="feature-list">
                    <li>Automated exit process tracking</li>
                    <li>10+ status fields per exit step</li>
                    <li>Calendar invites (ICS attachments)</li>
                    <li>Timed email reminders (1 month → 1 week → 3 days)</li>
                    <li>Asset return coordination</li>
                    <li>Separate HR & IT views</li>
                    <li>Employee history archival snapshot</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- IT Assets --}}
    <div class="col-md-6 col-lg-3">
        <div class="card module-card mc-assets">
            <div class="card-header"><i class="bi bi-laptop"></i> IT Assets</div>
            <div class="card-body">
                <ul class="feature-list">
                    <li>Full asset inventory with cascading category/type/brand</li>
                    <li>Assignment & provisioning workflow</li>
                    <li>AARF with dual acknowledgement (email token)</li>
                    <li>Rental & warranty tracking</li>
                    <li>Decommissioning with reason tracking</li>
                    <li>Multi-invoice upload & photo documentation</li>
                    <li>CSV import/export with auto-spec parsing</li>
                    <li>Overview cards (by company, ownership, brand)</li>
                    <li>7 filter dimensions (category, type, brand, status, ownership, vendor, company)</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Leave Management --}}
    <div class="col-md-6 col-lg-3">
        <div class="card module-card mc-leave">
            <div class="card-header"><i class="bi bi-calendar-check"></i> Leave</div>
            <div class="card-body">
                <ul class="feature-list">
                    <li>9 Malaysian statutory leave types</li>
                    <li>Tenure-based entitlement engine</li>
                    <li>Two-tier approval (Manager → HR)</li>
                    <li>Balance tracking with carry-forward</li>
                    <li>Half-day leave support</li>
                    <li>Public holiday management</li>
                    <li>Automated manager reminders</li>
                    <li>"On Leave This Week" dashboard widget</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Payroll --}}
    <div class="col-md-6 col-lg-3">
        <div class="card module-card mc-payroll">
            <div class="card-header"><i class="bi bi-cash-stack"></i> Payroll</div>
            <div class="card-body">
                <ul class="feature-list">
                    <li>Malaysian statutory deductions (EPF, SOCSO, EIS, PCB)</li>
                    <li>Pay run workflow (draft → approve → paid)</li>
                    <li>Auto payslip generation</li>
                    <li>Salary management & adjustments</li>
                    <li>Borang EA / CP.8D tax forms</li>
                    <li>HRDF employer contribution</li>
                    <li>Expense claim auto-integration</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Attendance --}}
    <div class="col-md-6 col-lg-3">
        <div class="card module-card mc-attendance">
            <div class="card-header"><i class="bi bi-clock-history"></i> Attendance</div>
            <div class="card-body">
                <ul class="feature-list">
                    <li>Clock in/out with IP logging</li>
                    <li>Auto work hours calculation</li>
                    <li>Multiple work schedules per company</li>
                    <li>Overtime request & approval</li>
                    <li>Multiplier-based OT calculation</li>
                    <li>Status tracking (present, late, absent, etc.)</li>
                    <li>HR attendance reports</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Expense Claims --}}
    <div class="col-md-6 col-lg-3">
        <div class="card module-card mc-claims">
            <div class="card-header"><i class="bi bi-receipt-cutoff"></i> eClaims</div>
            <div class="card-body">
                <ul class="feature-list">
                    <li>Monthly claim submission</li>
                    <li>13 expense categories with auto-detect</li>
                    <li>GST handling (configurable rate)</li>
                    <li>Receipt upload & management</li>
                    <li>Two-tier approval + bulk approve</li>
                    <li>CSV export with security protection</li>
                    <li>Auto payroll integration on approval</li>
                    <li>Claims overview dashboard with status KPIs</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- C-Suite Reports --}}
    <div class="col-md-6 col-lg-3">
        <div class="card module-card mc-reports">
            <div class="card-header"><i class="bi bi-graph-up-arrow"></i> C-Suite Reports</div>
            <div class="card-body">
                <ul class="feature-list">
                    <li>Executive dashboard with 10 KPIs</li>
                    <li>Workforce analytics & demographics</li>
                    <li>Financial & payroll trend reports</li>
                    <li>Statutory contribution summaries</li>
                    <li>Leave & attendance analytics</li>
                    <li>Asset portfolio overview</li>
                    <li>Interactive Chart.js visualizations</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- AI Accounting SaaS --}}
    <div class="col-md-6 col-lg-3">
        <div class="card module-card mc-accounting">
            <div class="card-header"><i class="bi bi-calculator"></i> AI Accounting</div>
            <div class="card-body">
                <ul class="feature-list">
                    <li>Double-entry bookkeeping engine</li>
                    <li>Chart of Accounts (Malaysian CoA)</li>
                    <li>AR/AP: invoices, bills, purchase orders</li>
                    <li>Banking, reconciliation & transfers</li>
                    <li>SST/WHT tax codes & returns</li>
                    <li>Fixed assets & depreciation</li>
                    <li>Budgets with 12-month variance</li>
                    <li>8 financial reports (TB, P&L, BS, CF…)</li>
                    <li>AI invoice OCR (OpenAI Vision)</li>
                    <li>AI finance chatbot</li>
                    <li>Executive financial dashboard</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Announcements --}}
    <div class="col-md-6 col-lg-3">
        <div class="card module-card mc-announcements">
            <div class="card-header"><i class="bi bi-megaphone"></i> Announcements</div>
            <div class="card-body">
                <ul class="feature-list">
                    <li>Company-wide news & announcements</li>
                    <li>Multi-company targeting (per announcement)</li>
                    <li>Multi-file attachments (images + PDFs)</li>
                    <li>Dashboard widget with latest 5 entries</li>
                    <li>Birthday babies of the month widget</li>
                    <li>On Leave This Week widget</li>
                    <li>Announcement email notifications</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Knowledge Base --}}
    <div class="col-md-6 col-lg-3">
        <div class="card module-card mc-knowledge">
            <div class="card-header"><i class="bi bi-book"></i> Knowledge Base</div>
            <div class="card-body">
                <ul class="feature-list">
                    <li>Searchable help topics by module</li>
                    <li>Password-protected admin access</li>
                    <li>Markdown content rendering</li>
                    <li>Module-organized topic categories</li>
                    <li>Auto-cached metadata (1-hour TTL)</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Company Management --}}
    <div class="col-md-6 col-lg-3">
        <div class="card module-card mc-company">
            <div class="card-header"><i class="bi bi-building-gear"></i> Company Mgmt</div>
            <div class="card-body">
                <ul class="feature-list">
                    <li>Multi-company registration</li>
                    <li>Statutory details (KWSP, SOCSO, EIS, TIN)</li>
                    <li>Company logo upload</li>
                    <li>Address & contact information</li>
                    <li>Cross-module company normalization</li>
                    <li>Company-scoped data filtering throughout system</li>
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- TWO-TIER APPROVAL FLOW --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="flow-section">
    <h3><i class="bi bi-check2-circle me-2"></i>Two-Tier Approval Workflow</h3>
    <p class="text-muted mb-4">Leave applications, expense claims, and overtime requests follow a consistent two-tier approval process.</p>

    <div class="row g-0 align-items-start">
        <div class="col flow-step">
            <div class="step-circle" style="background:#6c757d;">
                <i class="bi bi-pencil-square" style="font-size:1.3rem;"></i>
            </div>
            <div class="step-label">Employee Submits</div>
            <div class="step-desc">Fills details and<br>uploads documents</div>
        </div>
        <div class="col-auto flow-arrow"><i class="bi bi-chevron-right"></i></div>
        <div class="col flow-step">
            <div class="step-circle" style="background:#ffc107;color:#212529;">
                <i class="bi bi-person-check" style="font-size:1.3rem;"></i>
            </div>
            <div class="step-label">Manager Review</div>
            <div class="step-desc">Approve or reject<br>with remarks</div>
        </div>
        <div class="col-auto flow-arrow"><i class="bi bi-chevron-right"></i></div>
        <div class="col flow-step">
            <div class="step-circle" style="background:#0d6efd;">
                <i class="bi bi-shield-check" style="font-size:1.3rem;"></i>
            </div>
            <div class="step-label">HR Review</div>
            <div class="step-desc">Final approval<br>or bulk approve</div>
        </div>
        <div class="col-auto flow-arrow"><i class="bi bi-chevron-right"></i></div>
        <div class="col flow-step">
            <div class="step-circle" style="background:#198754;">
                <i class="bi bi-check-lg" style="font-size:1.3rem;"></i>
            </div>
            <div class="step-label">Approved</div>
            <div class="step-desc">Auto-notifications<br>and balance updates</div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- ROLE ACCESS OVERVIEW --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<h3 class="fw-bold mb-3"><i class="bi bi-shield-lock text-primary me-2"></i>Role-Based Access</h3>
<p class="text-muted mb-4">Five role groups with granular sub-roles controlling access to every feature.</p>

<div class="row g-3 mb-5">
    <div class="col-md-6 col-lg-3">
        <div class="role-card" style="background: linear-gradient(180deg, #fce4ec 0%, #fff 100%); border: 1px solid #f8bbd0;">
            <i class="bi bi-star-fill text-danger"></i>
            <h6 class="text-danger">SuperAdmin</h6>
            <div class="role-perms">
                <div>✓ Full system access</div>
                <div>✓ Company management</div>
                <div>✓ Role & permission assignment</div>
                <div>✓ Account activation</div>
                <div>✓ All HR Manager capabilities</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="role-card" style="background: linear-gradient(180deg, #e3f2fd 0%, #fff 100%); border: 1px solid #bbdefb;">
            <i class="bi bi-person-badge text-primary"></i>
            <h6 class="text-primary">HR Group</h6>
            <div class="role-perms">
                <div><strong>Manager:</strong> Full HR + HRM operations</div>
                <div><strong>Executive:</strong> Restricted editing</div>
                <div><strong>Intern:</strong> View-only access</div>
                <div>✓ Onboarding / Offboarding</div>
                <div>✓ Payroll, Leave, Attendance, Claims</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="role-card" style="background: linear-gradient(180deg, #e8f5e9 0%, #fff 100%); border: 1px solid #c8e6c9;">
            <i class="bi bi-gear text-success"></i>
            <h6 class="text-success">IT Group</h6>
            <div class="role-perms">
                <div><strong>Manager:</strong> Full IT operations</div>
                <div><strong>Executive:</strong> Asset management</div>
                <div><strong>Intern:</strong> View-only access</div>
                <div>✓ Asset inventory & provisioning</div>
                <div>✓ IT task management</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="role-card" style="background: linear-gradient(180deg, #f3e5f5 0%, #fff 100%); border: 1px solid #e1bee7;">
            <i class="bi bi-person text-purple" style="color:#6610f2 !important;"></i>
            <h6 style="color:#6610f2;">Employee</h6>
            <div class="role-perms">
                <div>✓ Self-service profile</div>
                <div>✓ Leave applications</div>
                <div>✓ Payslip & EA form viewing</div>
                <div>✓ Clock in/out & overtime</div>
                <div>✓ Expense claim submission</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="role-card" style="background: linear-gradient(180deg, #e0f2f1 0%, #fff 100%); border: 1px solid #b2dfdb;">
            <i class="bi bi-calculator" style="color:#00695c;"></i>
            <h6 style="color:#00695c;">Finance Group</h6>
            <div class="role-perms">
                <div><strong>Manager:</strong> Full accounting access + AI</div>
                <div><strong>Executive:</strong> View accounting data</div>
                <div>✓ Chart of Accounts & General Ledger</div>
                <div>✓ AR/AP, Banking, Tax, Budgets</div>
                <div>✓ Financial reports & dashboards</div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- DYNAMIC SECURITY SCORE --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
@php
    $scoreGradeClass = match(true) {
        $securityScore['score'] >= 85 => 'grade-a',
        $securityScore['score'] >= 75 => 'grade-b',
        $securityScore['score'] >= 60 => 'grade-c',
        $securityScore['score'] >= 50 => 'grade-d',
        default => 'grade-f',
    };
    $scoreColor = match(true) {
        $securityScore['score'] >= 85 => '#198754',
        $securityScore['score'] >= 75 => '#0d6efd',
        $securityScore['score'] >= 60 => '#ffc107',
        $securityScore['score'] >= 50 => '#fd7e14',
        default => '#dc3545',
    };
    $circumference = 2 * 3.14159 * 60;
    $offset = $circumference - ($securityScore['score'] / 100) * $circumference;
@endphp

<div class="row g-4 mb-5" id="security-section">
    <div class="col-lg-4">
        <div class="compliance-stamp {{ $scoreGradeClass }} h-100" id="score-card">
            <button class="refresh-btn position-absolute" style="top:1rem;right:1rem;" id="btn-refresh-score">
                <i class="bi bi-arrow-clockwise" id="score-refresh-icon"></i> Scan
            </button>

            <div class="score-ring">
                <svg width="140" height="140">
                    <circle cx="70" cy="70" r="60" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="10"/>
                    <circle cx="70" cy="70" r="60" fill="none" stroke="rgba(255,255,255,0.9)" stroke-width="10"
                            stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}"
                            stroke-linecap="round" id="score-ring-progress"/>
                </svg>
                <div class="score-inner">
                    <span id="score-value">{{ $securityScore['score'] }}</span><small>/100</small>
                    <div style="font-size:0.7rem;font-weight:600;opacity:0.8;" id="score-grade">Grade {{ $securityScore['grade'] }}</div>
                </div>
            </div>

            <div class="d-flex justify-content-center gap-3 mb-3" style="font-size:0.82rem;">
                <span><i class="bi bi-check-circle-fill me-1"></i><span id="checks-passed">{{ $securityScore['passed'] }}</span> Passed</span>
                <span><i class="bi bi-exclamation-circle-fill me-1"></i><span id="checks-warnings">{{ $securityScore['warnings'] }}</span> Warnings</span>
                <span><i class="bi bi-x-circle-fill me-1"></i><span id="checks-failed">{{ $securityScore['failed'] }}</span> Failed</span>
            </div>

            <hr style="border-color:rgba(255,255,255,0.2);">

            <div class="d-flex flex-column gap-2 text-start" style="font-size:0.85rem;">
                <div><i class="bi bi-check-circle-fill me-2"></i>OWASP Top 10 Compliant</div>
                <div><i class="bi bi-check-circle-fill me-2"></i>6-Layer Defense Architecture</div>
                <div><i class="bi bi-check-circle-fill me-2"></i>Malaysian PDPA Aligned</div>
                <div><i class="bi bi-check-circle-fill me-2"></i>Employment Act 1955 Compliant</div>
                <div><i class="bi bi-check-circle-fill me-2"></i>Real-time Threat Detection</div>
                <div><i class="bi bi-check-circle-fill me-2"></i>HMAC Log Integrity Chain</div>
                <div><i class="bi bi-check-circle-fill me-2"></i>Two-Factor Authentication (TOTP)</div>
                <div><i class="bi bi-check-circle-fill me-2"></i>Weekly Pending Compliance Sweep</div>
                <div><i class="bi bi-check-circle-fill me-2"></i>CSP Nonce-based Script Security</div>
            </div>

            <div class="mt-3" style="font-size:0.72rem;opacity:0.7;">
                Last scanned: <span id="score-timestamp">{{ $securityScore['calculated_at'] }}</span>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <h4 class="fw-bold mb-3"><i class="bi bi-shield-fill-check text-success me-2"></i>Security Architecture — Live Check Results</h4>
        <div class="row g-2" id="security-checks-grid">
            @foreach($securityScore['checks'] as $check)
            <div class="col-md-6">
                <div class="security-badge">
                    <i class="bi {{ $check['icon'] }} text-{{ $check['color'] }}"></i>
                    <div style="flex:1;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="sb-title">{{ $check['name'] }}</span>
                            @if($check['passed'])
                                <span class="badge bg-success" style="font-size:0.65rem;">PASS</span>
                            @elseif(($check['partial'] ?? 0) > 0)
                                <span class="badge bg-warning text-dark" style="font-size:0.65rem;">PARTIAL</span>
                            @else
                                <span class="badge bg-danger" style="font-size:0.65rem;">FAIL</span>
                            @endif
                        </div>
                        <div class="sb-desc">{{ $check['detail'] }}</div>
                        @if(!empty($check['items']))
                        <div class="mt-1">
                            @foreach($check['items'] as $item)
                            <span class="check-item" style="display:inline-flex;margin-right:0.75rem;">
                                <span class="check-dot {{ $item['ok'] ? 'pass' : 'fail' }}"></span>
                                {{ $item['label'] }}
                            </span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- DEPENDENCY UPDATE CHECKER --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="flow-section" style="background: linear-gradient(135deg, #f0f4ff 0%, #fff 100%); border: 1px solid #c7d2fe;" id="update-section">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h3 style="color:var(--primary-dark);"><i class="bi bi-arrow-repeat me-2"></i>Dependency Update Checker</h3>
            <p class="text-muted mb-0">Package versions compared against Packagist and npm registries. Auto-checked daily at 6:00 AM.</p>
        </div>
        <button class="refresh-btn dark" id="btn-refresh-updates">
            <i class="bi bi-arrow-clockwise" id="update-refresh-icon"></i> Check Now
        </button>
    </div>

    {{-- Summary row --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center p-3" style="border-radius:0.75rem;">
                <div class="fw-bold fs-3 text-primary" id="pkg-total">{{ $updateCheck['total_packages'] }}</div>
                <div class="text-muted" style="font-size:0.8rem;">Total Packages</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center p-3" style="border-radius:0.75rem;">
                <div class="fw-bold fs-3 text-success" id="pkg-uptodate">{{ $updateCheck['up_to_date'] }}</div>
                <div class="text-muted" style="font-size:0.8rem;">Up to Date</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center p-3" style="border-radius:0.75rem;">
                <div class="fw-bold fs-3 text-warning" id="pkg-outdated">{{ $updateCheck['outdated'] }}</div>
                <div class="text-muted" style="font-size:0.8rem;">Updates Available</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center p-3" style="border-radius:0.75rem;">
                <div class="text-muted mb-1" style="font-size:0.75rem;">Dependency Health</div>
                <div class="health-bar mb-1">
                    <div class="health-bar-fill" id="health-bar-fill" style="width:{{ $updateCheck['health'] }}%;background:{{ $updateCheck['health'] >= 80 ? '#198754' : ($updateCheck['health'] >= 60 ? '#ffc107' : '#dc3545') }};"></div>
                </div>
                <div class="fw-bold" id="health-value" style="color:{{ $updateCheck['health'] >= 80 ? '#198754' : ($updateCheck['health'] >= 60 ? '#ffc107' : '#dc3545') }};">{{ $updateCheck['health'] }}%</div>
            </div>
        </div>
    </div>

    {{-- Severity legend --}}
    @if($updateCheck['outdated'] > 0)
    <div class="d-flex gap-3 mb-3" style="font-size:0.78rem;">
        @if($updateCheck['critical_count'] > 0)
        <span class="badge bg-danger">{{ $updateCheck['critical_count'] }} Critical</span>
        @endif
        @if($updateCheck['major_count'] > 0)
        <span class="badge" style="background:#fd7e14;">{{ $updateCheck['major_count'] }} Major</span>
        @endif
        @if($updateCheck['minor_count'] > 0)
        <span class="badge bg-warning text-dark">{{ $updateCheck['minor_count'] }} Minor</span>
        @endif
        @if($updateCheck['patch_count'] > 0)
        <span class="badge bg-info">{{ $updateCheck['patch_count'] }} Patch</span>
        @endif
    </div>
    @endif

    {{-- Package list --}}
    <div class="row g-2" id="package-list">
        @foreach($updateCheck['packages'] as $pkg)
        <div class="col-md-6 col-lg-4">
            <div class="update-card severity-{{ $pkg['severity'] }}">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi {{ $pkg['icon'] }} {{ $pkg['type'] === 'composer' ? 'text-primary' : 'text-warning' }}"></i>
                    <span class="fw-bold" style="font-size:0.85rem;">{{ $pkg['name'] }}</span>
                    @if($pkg['is_dev'])
                    <span class="badge bg-secondary" style="font-size:0.6rem;">DEV</span>
                    @endif
                </div>
                <div class="d-flex align-items-center gap-2" style="font-size:0.8rem;">
                    <code style="font-size:0.75rem;background:#f1f5f9;padding:0.15rem 0.4rem;border-radius:0.25rem;">v{{ $pkg['current_version'] }}</code>
                    @if($pkg['update_available'])
                    <i class="bi bi-arrow-right text-muted" style="font-size:0.7rem;"></i>
                    <code style="font-size:0.75rem;background:#fef3c7;padding:0.15rem 0.4rem;border-radius:0.25rem;">v{{ $pkg['latest_version'] }}</code>
                    <span class="badge {{ match($pkg['severity']) { 'critical' => 'bg-danger', 'major' => 'bg-warning text-dark', 'minor' => 'bg-info', default => 'bg-secondary' } }}" style="font-size:0.6rem;">{{ strtoupper($pkg['severity']) }}</span>
                    @else
                    <span class="text-success" style="font-size:0.75rem;"><i class="bi bi-check-circle-fill"></i> Latest</span>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="text-center mt-3" style="font-size:0.75rem;color:#6c757d;">
        <i class="bi bi-clock me-1"></i>Last checked: <span id="update-timestamp">{{ $updateCheck['checked_at'] }}</span>
        &nbsp;|&nbsp; Auto-updates: <code>composer update</code> &amp; <code>npm update</code>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- EMAIL AUTOMATION --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="flow-section">
    <h3><i class="bi bi-envelope-open me-2"></i>Automated Email System — {{ $meta['mail_classes'] }} Workflows</h3>
    <div class="row g-4">
        @php $counter = 0; @endphp
        @foreach($meta['email_groups'] as $groupName => $group)
        <div class="col-md-{{ count($meta['email_groups']) <= 3 ? '4' : '3' }}">
            <h6 class="fw-bold mb-3" style="color:{{ $group['color'] }};">{{ $groupName }}</h6>
            @foreach($group['items'] as $i => $mail)
            <div class="email-flow-item">
                <span class="email-count" style="background:{{ $group['color'] }};">{{ ++$counter }}</span>
                <span style="font-size:0.85rem;">{{ $mail }}</span>
            </div>
            @endforeach
        </div>
        @endforeach
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- PAYROLL FLOW --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="flow-section">
    <h3><i class="bi bi-cash-coin me-2"></i>Payroll Processing Flow</h3>
    <p class="text-muted mb-4">Malaysian statutory-compliant payroll with automatic EPF, SOCSO, EIS, and PCB deductions.</p>

    <div class="row g-0 align-items-start">
        <div class="col flow-step">
            <div class="step-circle" style="background:#6c757d;">
                <i class="bi bi-currency-dollar" style="font-size:1.3rem;"></i>
            </div>
            <div class="step-label">Salary Setup</div>
            <div class="step-desc">Basic salary,<br>allowances, items</div>
        </div>
        <div class="col-auto flow-arrow"><i class="bi bi-chevron-right"></i></div>
        <div class="col flow-step">
            <div class="step-circle" style="background:#0d6efd;">
                <i class="bi bi-file-earmark-plus" style="font-size:1.3rem;"></i>
            </div>
            <div class="step-label">Create Pay Run</div>
            <div class="step-desc">Select month,<br>company, dates</div>
        </div>
        <div class="col-auto flow-arrow"><i class="bi bi-chevron-right"></i></div>
        <div class="col flow-step">
            <div class="step-circle" style="background:#ffc107;color:#212529;">
                <i class="bi bi-calculator" style="font-size:1.3rem;"></i>
            </div>
            <div class="step-label">Generate Payslips</div>
            <div class="step-desc">Auto EPF/SOCSO/<br>EIS/PCB calculation</div>
        </div>
        <div class="col-auto flow-arrow"><i class="bi bi-chevron-right"></i></div>
        <div class="col flow-step">
            <div class="step-circle" style="background:#198754;">
                <i class="bi bi-check-circle" style="font-size:1.3rem;"></i>
            </div>
            <div class="step-label">Approve & Pay</div>
            <div class="step-desc">Manager approval,<br>payslip emails sent</div>
        </div>
        <div class="col-auto flow-arrow"><i class="bi bi-chevron-right"></i></div>
        <div class="col flow-step">
            <div class="step-circle" style="background:#6610f2;">
                <i class="bi bi-file-earmark-text" style="font-size:1.3rem;"></i>
            </div>
            <div class="step-label">EA Form</div>
            <div class="step-desc">Annual tax form<br>auto-generated</div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- TECHNOLOGY STACK --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<h3 class="fw-bold mb-3"><i class="bi bi-code-slash text-primary me-2"></i>Technology Stack</h3>
<div class="d-flex flex-wrap gap-2 mb-5">
    @foreach($meta['tech_stack'] as $tech)
    <span class="tech-badge"><i class="bi {{ $tech['icon'] }} {{ $tech['color'] }}" @if($tech['color'] === 'text-purple') style="color:#6610f2 !important;" @endif></i> {{ $tech['label'] }}</span>
    @endforeach
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- LIVE STATS --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<h3 class="fw-bold mb-3"><i class="bi bi-bar-chart text-primary me-2"></i>Live System Statistics</h3>
<div class="row g-3 mb-4">
    @foreach($meta['live_stats'] as $stat)
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm text-center p-3" style="border-radius:0.75rem;">
            <i class="bi {{ $stat['icon'] }}" style="font-size:2rem; color:{{ $stat['color'] }};"></i>
            <div class="fw-bold fs-3 mt-2" style="color:{{ $stat['color'] }};">{{ $stat['value'] }}</div>
            <div class="text-muted" style="font-size:0.8rem;">{{ $stat['label'] }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- MALAYSIAN COMPLIANCE --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="flow-section" style="background: linear-gradient(135deg, #fff3cd 0%, #fff 100%); border: 1px solid #ffc107;">
    <h3 style="color:#856404;"><i class="bi bi-flag me-2"></i>Malaysian Statutory Compliance</h3>
    <div class="row g-4">
        <div class="col-md-3">
            <div class="text-center">
                <div class="fw-bold fs-5" style="color:#856404;">EPF / KWSP</div>
                <div class="text-muted small">Employee & employer contributions with 4 categories (A-D) based on age and nationality</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center">
                <div class="fw-bold fs-5" style="color:#856404;">SOCSO / PERKESO</div>
                <div class="text-muted small">Employee & employer social security contributions auto-calculated</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center">
                <div class="fw-bold fs-5" style="color:#856404;">EIS / SIP</div>
                <div class="text-muted small">Employment Insurance System deductions for both parties</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center">
                <div class="fw-bold fs-5" style="color:#856404;">PCB / MTD</div>
                <div class="text-mutable small">Monthly Tax Deduction with Borang EA / CP.8D annual reporting</div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- ACCOUNTING FLOW --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="flow-section">
    <h3><i class="bi bi-calculator me-2"></i>AI Accounting Processing Flow</h3>
    <p class="text-muted mb-4">Full double-entry accounting with AI-powered invoice scanning and conversational finance chatbot.</p>

    <div class="row g-0 align-items-start">
        <div class="col flow-step">
            <div class="step-circle" style="background:#00695c;">
                <i class="bi bi-diagram-3" style="font-size:1.3rem;"></i>
            </div>
            <div class="step-label">Chart of Accounts</div>
            <div class="step-desc">Malaysian standard<br>CoA (75+ accounts)</div>
        </div>
        <div class="col-auto flow-arrow"><i class="bi bi-chevron-right"></i></div>
        <div class="col flow-step">
            <div class="step-circle" style="background:#0d6efd;">
                <i class="bi bi-receipt" style="font-size:1.3rem;"></i>
            </div>
            <div class="step-label">Invoices & Bills</div>
            <div class="step-desc">AR/AP with<br>auto-numbering</div>
        </div>
        <div class="col-auto flow-arrow"><i class="bi bi-chevron-right"></i></div>
        <div class="col flow-step">
            <div class="step-circle" style="background:#6610f2;">
                <i class="bi bi-robot" style="font-size:1.3rem;"></i>
            </div>
            <div class="step-label">AI Invoice OCR</div>
            <div class="step-desc">OpenAI Vision API<br>auto-extract data</div>
        </div>
        <div class="col-auto flow-arrow"><i class="bi bi-chevron-right"></i></div>
        <div class="col flow-step">
            <div class="step-circle" style="background:#198754;">
                <i class="bi bi-journal-check" style="font-size:1.3rem;"></i>
            </div>
            <div class="step-label">Journal Entries</div>
            <div class="step-desc">Balanced debit/credit<br>auto-posted</div>
        </div>
        <div class="col-auto flow-arrow"><i class="bi bi-chevron-right"></i></div>
        <div class="col flow-step">
            <div class="step-circle" style="background:#dc3545;">
                <i class="bi bi-file-earmark-bar-graph" style="font-size:1.3rem;"></i>
            </div>
            <div class="step-label">Financial Reports</div>
            <div class="step-desc">TB, P&L, BS,<br>Cash Flow, Aged AR/AP</div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- RECENT CHANGES / GIT CHANGELOG --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
@if(!empty($meta['git']['recent_commits']))
<div class="flow-section" style="background: linear-gradient(135deg, #f5f3ff 0%, #fff 100%); border: 1px solid #ddd6fe;">
    <h3 style="color:#5b21b6;"><i class="bi bi-clock-history me-2"></i>Recent System Changes</h3>
    <p class="text-muted mb-3">Latest commits on <code>{{ $meta['git']['branch'] }}</code> branch.</p>
    <div class="table-responsive">
        <table class="table table-sm mb-0" style="font-size:0.82rem;">
            <thead>
                <tr style="background:#ede9fe;">
                    <th style="width:80px;font-weight:700;color:#5b21b6;">Commit</th>
                    <th style="font-weight:700;color:#5b21b6;">Description</th>
                    <th style="width:150px;font-weight:700;color:#5b21b6;">Date</th>
                    <th style="width:140px;font-weight:700;color:#5b21b6;">Author</th>
                </tr>
            </thead>
            <tbody>
                @foreach($meta['git']['recent_commits'] as $commit)
                <tr @if($loop->first) style="background:#f5f3ff;font-weight:600;" @endif>
                    <td><code style="font-size:0.75rem;background:#ede9fe;padding:0.15rem 0.4rem;border-radius:0.25rem;">{{ $commit['hash'] }}</code></td>
                    <td>{{ $commit['message'] }}</td>
                    <td class="text-muted">{{ \Carbon\Carbon::parse($commit['date'])->format('d/m/Y H:i') }}</td>
                    <td class="text-muted">{{ $commit['author'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="text-center text-muted py-4" style="font-size:0.8rem;">
    <i class="bi bi-info-circle me-1"></i>
    HRM &amp; Finance System &mdash; Auto-updated {{ $meta['collected_at'] }}
    &nbsp;|&nbsp; Branch <code>{{ $meta['git']['branch'] }}</code> @ <code>{{ $meta['git']['hash'] }}</code>
    &nbsp;|&nbsp; {{ $meta['tables'] }} tables &middot; {{ $meta['endpoints'] }} endpoints &middot; {{ $meta['mail_classes'] }} emails &middot; {{ $meta['models'] }} models &middot; {{ $meta['views'] }} views
</div>

<script nonce="{{ $cspNonce }}">
    const CIRCUMFERENCE = 2 * Math.PI * 60;

    function refreshSecurityScore() {
        const btn = document.getElementById('btn-refresh-score');
        const icon = document.getElementById('score-refresh-icon');
        btn.disabled = true;
        icon.className = 'spinner-border spinner-border-sm';

        fetch('{{ route("superadmin.security-score.refresh") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        })
        .then(r => r.json())
        .then(data => {
            // Update score ring
            document.getElementById('score-value').textContent = data.score;
            document.getElementById('score-grade').textContent = 'Grade ' + data.grade;
            document.getElementById('hero-security-score').textContent = data.score + '/100';
            document.getElementById('checks-passed').textContent = data.passed;
            document.getElementById('checks-warnings').textContent = data.warnings;
            document.getElementById('checks-failed').textContent = data.failed;
            document.getElementById('score-timestamp').textContent = data.calculated_at;

            // Update ring progress
            const offset = CIRCUMFERENCE - (data.score / 100) * CIRCUMFERENCE;
            document.getElementById('score-ring-progress').setAttribute('stroke-dashoffset', offset);

            // Update grade class
            const card = document.getElementById('score-card');
            card.className = card.className.replace(/grade-\w+/, '');
            if (data.score >= 85) card.classList.add('grade-a');
            else if (data.score >= 75) card.classList.add('grade-b');
            else if (data.score >= 60) card.classList.add('grade-c');
            else if (data.score >= 50) card.classList.add('grade-d');
            else card.classList.add('grade-f');

            // Rebuild checks grid
            const grid = document.getElementById('security-checks-grid');
            grid.innerHTML = data.checks.map(c => {
                let badgeClass = c.passed ? 'bg-success' : ((c.partial || 0) > 0 ? 'bg-warning text-dark' : 'bg-danger');
                let badgeLabel = c.passed ? 'PASS' : ((c.partial || 0) > 0 ? 'PARTIAL' : 'FAIL');
                let items = '';
                if (c.items) {
                    items = '<div class="mt-1">' + c.items.map(i =>
                        '<span class="check-item" style="display:inline-flex;margin-right:0.75rem;">' +
                        '<span class="check-dot ' + (i.ok ? 'pass' : 'fail') + '"></span>' +
                        i.label + '</span>'
                    ).join('') + '</div>';
                }
                return '<div class="col-md-6"><div class="security-badge">' +
                    '<i class="bi ' + c.icon + ' text-' + c.color + '"></i>' +
                    '<div style="flex:1;">' +
                    '<div class="d-flex align-items-center gap-2">' +
                    '<span class="sb-title">' + c.name + '</span>' +
                    '<span class="badge ' + badgeClass + '" style="font-size:0.65rem;">' + badgeLabel + '</span>' +
                    '</div>' +
                    '<div class="sb-desc">' + c.detail + '</div>' +
                    items +
                    '</div></div></div>';
            }).join('');
        })
        .catch(e => console.error('Security score refresh failed:', e))
        .finally(() => {
            btn.disabled = false;
            icon.className = 'bi bi-arrow-clockwise';
        });
    }

    function refreshUpdateCheck() {
        const btn = document.getElementById('btn-refresh-updates');
        const icon = document.getElementById('update-refresh-icon');
        btn.disabled = true;
        icon.className = 'spinner-border spinner-border-sm';
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Checking...';

        fetch('{{ route("superadmin.update-check.refresh") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('pkg-total').textContent = data.total_packages;
            document.getElementById('pkg-uptodate').textContent = data.up_to_date;
            document.getElementById('pkg-outdated').textContent = data.outdated;
            document.getElementById('health-value').textContent = data.health + '%';
            document.getElementById('update-timestamp').textContent = data.checked_at;

            const fill = document.getElementById('health-bar-fill');
            fill.style.width = data.health + '%';
            fill.style.background = data.health >= 80 ? '#198754' : (data.health >= 60 ? '#ffc107' : '#dc3545');

            // Rebuild package list
            const list = document.getElementById('package-list');
            list.innerHTML = data.packages.map(p => {
                const typeIcon = p.type === 'composer' ? 'text-primary' : 'text-warning';
                const severityBadge = {
                    critical: 'bg-danger', major: 'bg-warning text-dark', minor: 'bg-info', patch: 'bg-secondary'
                };
                let version = '<code style="font-size:0.75rem;background:#f1f5f9;padding:0.15rem 0.4rem;border-radius:0.25rem;">v' + p.current_version + '</code>';
                if (p.update_available) {
                    version += ' <i class="bi bi-arrow-right text-muted" style="font-size:0.7rem;"></i> ' +
                        '<code style="font-size:0.75rem;background:#fef3c7;padding:0.15rem 0.4rem;border-radius:0.25rem;">v' + p.latest_version + '</code>' +
                        ' <span class="badge ' + (severityBadge[p.severity] || 'bg-secondary') + '" style="font-size:0.6rem;">' + p.severity.toUpperCase() + '</span>';
                } else {
                    version += ' <span class="text-success" style="font-size:0.75rem;"><i class="bi bi-check-circle-fill"></i> Latest</span>';
                }
                return '<div class="col-md-6 col-lg-4"><div class="update-card severity-' + p.severity + '">' +
                    '<div class="d-flex align-items-center gap-2 mb-1">' +
                    '<i class="bi ' + p.icon + ' ' + typeIcon + '"></i>' +
                    '<span class="fw-bold" style="font-size:0.85rem;">' + p.name + '</span>' +
                    (p.is_dev ? '<span class="badge bg-secondary" style="font-size:0.6rem;">DEV</span>' : '') +
                    '</div>' +
                    '<div class="d-flex align-items-center gap-2" style="font-size:0.8rem;">' + version + '</div>' +
                    '</div></div>';
            }).join('');
        })
        .catch(e => console.error('Update check failed:', e))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Check Now';
        });
    }

    // Bind refresh buttons via addEventListener (CSP nonce-compatible)
    document.getElementById('btn-refresh-score').addEventListener('click', refreshSecurityScore);
    document.getElementById('btn-refresh-updates').addEventListener('click', refreshUpdateCheck);
</script>

@endsection
