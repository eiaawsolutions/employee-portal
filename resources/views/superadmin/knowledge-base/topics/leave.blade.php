@extends('superadmin.knowledge-base.topic-layout')

@section('topic-content')
{{-- ── Pipeline ── --}}
<div class="kb-section card" id="pipeline">
    <div class="card-body">
        <h4><i class="bi bi-signpost-split me-2"></i>Leave Application Pipeline</h4>
        <div class="diagram-container">
            <div class="mermaid">
flowchart TD
    A["Employee Applies\nstart_date, end_date\nleave_type, half_day?"] --> B["Status: pending\nLeaveApplicationNotifyMail → HR"]
    B --> C{"HR / Manager Review"}
    C -->|"Approve"| D["Status: approved\nLeaveBalance.taken += days\nLeaveApprovalNotifyMail → Employee"]
    C -->|"Reject"| E["Status: rejected\nrejection_reason required\nLeaveApprovalNotifyMail → Employee"]
    A -->|"Employee cancels"| F["Status: cancelled\nBalance restored if was approved"]

    D -->|"If unpaid leave"| G["Payroll Deduction\nUnpaid days × basic/21.67"]

    style A fill:#cfe2ff,stroke:#0d6efd,color:#000
    style B fill:#fef3cd,stroke:#ffc107,color:#000
    style D fill:#d1e7dd,stroke:#198754,color:#000
    style E fill:#f8d7da,stroke:#dc3545,color:#000
    style F fill:#e2e3e5,stroke:#6c757d,color:#000
    style G fill:#e8daef,stroke:#6f42c1,color:#000
            </div>
        </div>
    </div>
</div>

{{-- ── Balance Calculation ── --}}
<div class="kb-section card" id="balance">
    <div class="card-body">
        <h4><i class="bi bi-calculator me-2"></i>Balance Calculation</h4>
        <div class="diagram-container">
            <div class="mermaid">
flowchart LR
    E["Entitled Days\n(tenure-based)"] --> CALC["Available Balance"]
    CF["Carry Forward\n(from prior year)"] --> CALC
    ADJ["Manual Adjustments\n(HR override)"] --> CALC
    CALC --> FINAL["= Entitled + CF + Adj - Taken"]
    TAKEN["Taken Days\n(auto on approval)"] --> FINAL
            </div>
        </div>
        <div class="alert alert-info small mt-3 mb-0">
            <strong>Tenure-Based Entitlements:</strong> Leave days are allocated based on <code>min_tenure_months</code> / <code>max_tenure_months</code> ranges.
            Each leave type defines its own entitlement tiers and carry-forward limits per company.
        </div>
    </div>
</div>

{{-- ── Status Values ── --}}
<div class="kb-section card" id="status">
    <div class="card-body">
        <h4><i class="bi bi-tag me-2"></i>Status Values</h4>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="status-badge" style="background:#fef3cd;color:#856404;">pending</span>
            <span class="status-badge" style="background:#d1e7dd;color:#0f5132;">approved</span>
            <span class="status-badge" style="background:#f8d7da;color:#842029;">rejected</span>
            <span class="status-badge" style="background:#e2e3e5;color:#41464b;">cancelled</span>
        </div>
        <table class="table table-sm relation-table">
            <thead><tr><th>Feature</th><th>Details</th></tr></thead>
            <tbody>
                <tr><td>Half-day support</td><td><code>half_day</code> boolean + <code>period</code> (morning/afternoon)</td></tr>
                <tr><td>Attachments</td><td>Required for certain types (sick leave proof)</td></tr>
                <tr><td>Public holidays</td><td>Auto-excluded from day count (company-scoped)</td></tr>
                <tr><td>Manager approval</td><td>Optional <code>manager_status</code> field for two-tier flow</td></tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ── Database ── --}}
<div class="kb-section card" id="database">
    <div class="card-body">
        <h4><i class="bi bi-database me-2"></i>Database Relations</h4>
        <div class="diagram-container">
            <div class="mermaid">
erDiagram
    EMPLOYEES ||--o{ LEAVE_APPLICATIONS : applies
    EMPLOYEES ||--o{ LEAVE_BALANCES : has
    LEAVE_TYPES ||--o{ LEAVE_APPLICATIONS : categorizes
    LEAVE_TYPES ||--o{ LEAVE_ENTITLEMENTS : defines
    LEAVE_TYPES ||--o{ LEAVE_BALANCES : tracks
    PUBLIC_HOLIDAYS }o--|| COMPANIES : "scoped to"
    LEAVE_BALANCES {
        int entitled
        float taken
        float carry_forward
        float adjustment
    }
    LEAVE_APPLICATIONS {
        string status
        date start_date
        date end_date
        float total_days
        boolean half_day
    }
            </div>
        </div>
    </div>
</div>

{{-- ── Payroll Integration ── --}}
<div class="kb-section card" id="payroll">
    <div class="card-body">
        <h4><i class="bi bi-link-45deg me-2"></i>Payroll Integration</h4>
        <div class="border rounded p-3 small">
            <p class="mb-2"><strong>Unpaid leave deduction formula:</strong></p>
            <code class="d-block bg-light p-2 rounded">deduction = unpaid_leave_days × (basic_salary / 21.67)</code>
            <p class="text-muted mt-2 mb-0">Applied during pay run generation for the matching month. Only leave types where <code>is_paid = false</code> trigger deductions.</p>
        </div>
    </div>
</div>
@endsection
