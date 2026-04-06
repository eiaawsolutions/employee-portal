@extends('superadmin.knowledge-base.topic-layout')

@section('topic-content')
{{-- ── Pipeline ── --}}
<div class="kb-section card" id="pipeline">
    <div class="card-body">
        <h4><i class="bi bi-signpost-split me-2"></i>Payroll Pipeline</h4>
        <div class="diagram-container">
            <div class="mermaid">
flowchart TD
    subgraph SETUP["Setup Phase"]
        A["Define Payroll Items\nAllowances & Deductions"] --> B["Set Employee Salaries\nbasic_salary + items"]
    end

    subgraph PAYRUN["Pay Run"]
        C["HR Creates Pay Run\nCompany, Year, Month\nStatus: draft"] --> D["Auto-Generate Payslips\nfor all active employees"]
    end

    subgraph CALC["Payslip Calculation"]
        D --> E["Basic Salary"]
        D --> F["+ Earning Items\n(allowances)"]
        D --> G["− Statutory Deductions\nEPF, SOCSO, EIS, PCB, HRDF"]
        D --> H["− Leave Deductions\nunpaid days × basic/21.67"]
        D --> I["− Other Deductions"]
        D --> J["+ Claim Reimbursement\n(hr_approved claims)"]
        E & F & G & H & I & J --> K["= Net Pay"]
    end

    subgraph APPROVE["Approval & Payment"]
        K --> L["HR Manager Approves\nStatus: approved"]
        L --> M["Status: paid\nPayslipReadyMail → Employee"]
    end

    SETUP --> PAYRUN

    style C fill:#cfe2ff,stroke:#0d6efd,color:#000
    style G fill:#f8d7da,stroke:#dc3545,color:#000
    style J fill:#d1e7dd,stroke:#198754,color:#000
    style L fill:#fef3cd,stroke:#ffc107,color:#000
    style M fill:#d1e7dd,stroke:#198754,color:#000
            </div>
        </div>
    </div>
</div>

{{-- ── Status Values ── --}}
<div class="kb-section card" id="status">
    <div class="card-body">
        <h4><i class="bi bi-tag me-2"></i>Pay Run Statuses</h4>
        <div class="d-flex flex-wrap gap-2">
            <span class="status-badge" style="background:#e2e3e5;color:#41464b;">draft</span>
            <span>→</span>
            <span class="status-badge" style="background:#cfe2ff;color:#084298;">processing</span>
            <span>→</span>
            <span class="status-badge" style="background:#fef3cd;color:#856404;">approved</span>
            <span>→</span>
            <span class="status-badge" style="background:#d1e7dd;color:#0f5132;">paid</span>
        </div>
        <div class="mt-2">
            <span class="status-badge" style="background:#f8d7da;color:#842029;">cancelled</span>
            <span class="text-muted small ms-2">(can be cancelled from draft)</span>
        </div>
    </div>
</div>

{{-- ── Statutory Calculations ── --}}
<div class="kb-section card" id="statutory">
    <div class="card-body">
        <h4><i class="bi bi-bank me-2"></i>Malaysian Statutory Contributions</h4>
        <table class="table table-sm relation-table">
            <thead><tr><th>Component</th><th>Employee</th><th>Employer</th><th>Notes</th></tr></thead>
            <tbody>
                <tr>
                    <td><strong>EPF</strong></td>
                    <td>11%</td><td>12-13%</td>
                    <td>
                        Category 1: Both contribute<br>
                        Category 2: Employer only<br>
                        Category 3: No deduction
                    </td>
                </tr>
                <tr><td><strong>SOCSO</strong></td><td>0.5%</td><td>1.75%</td><td>Capped at salary ceiling</td></tr>
                <tr><td><strong>EIS</strong></td><td>0.2%</td><td>0.2%</td><td>Employment Insurance System</td></tr>
                <tr><td><strong>PCB</strong></td><td>Graduated</td><td>—</td><td>Resident vs foreigner rates</td></tr>
                <tr><td><strong>HRDF</strong></td><td>—</td><td>1%</td><td>Employer-only contribution</td></tr>
            </tbody>
        </table>
        <div class="alert alert-info small mt-3 mb-0">
            <i class="bi bi-info-circle me-1"></i>
            EPF category and tax residency are set per employee in their salary configuration. <code>is_resident</code> flag affects PCB calculation rates.
        </div>
    </div>
</div>

{{-- ── Claim Integration ── --}}
<div class="kb-section card" id="claims">
    <div class="card-body">
        <h4><i class="bi bi-receipt me-2"></i>Expense Claim Integration</h4>
        <div class="diagram-container">
            <div class="mermaid">
flowchart LR
    A["hr_approved Claims\nfor pay period"] -->|"Sum total_with_gst"| B["claim_reimbursement\non Payslip"]
    B --> C["PayslipItem created\ntype: earning\nis_statutory: false"]
    C --> D["Included in Net Pay\nExcluded from EPF/SOCSO/EIS/PCB"]
    B -->|"Link back"| E["claim.payslip_id set\nclaim.pay_run_id set\nStatus → paid"]
            </div>
        </div>
        <div class="alert alert-warning small mt-3 mb-0">
            <strong>Non-Taxable:</strong> Claim reimbursements are tracked in a separate <code>claim_reimbursement</code> column so <code>calculateStatutory()</code> can exclude them from contribution calculations.
        </div>
    </div>
</div>

{{-- ── Database ── --}}
<div class="kb-section card" id="database">
    <div class="card-body">
        <h4><i class="bi bi-database me-2"></i>Database Relations</h4>
        <div class="diagram-container">
            <div class="mermaid">
erDiagram
    PAY_RUNS ||--o{ PAYSLIPS : generates
    EMPLOYEES ||--o{ PAYSLIPS : receives
    PAYSLIPS ||--o{ PAYSLIP_ITEMS : contains
    EMPLOYEES ||--o{ EMPLOYEE_SALARIES : has
    EMPLOYEE_SALARIES ||--o{ EMPLOYEE_SALARY_ITEMS : includes
    PAYROLL_ITEMS ||--o{ EMPLOYEE_SALARY_ITEMS : templates
    EMPLOYEES ||--o{ SALARY_ADJUSTMENTS : audits
    PAY_RUNS {
        string status
        string company
        int year
        int month
        date pay_date
    }
    PAYSLIPS {
        decimal basic_salary
        decimal gross_earnings
        decimal total_deductions
        decimal net_pay
        decimal claim_reimbursement
    }
            </div>
        </div>
    </div>
</div>
@endsection
