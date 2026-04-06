@extends('superadmin.knowledge-base.topic-layout')

@section('topic-content')
{{-- ── Pipeline ── --}}
<div class="kb-section card" id="pipeline">
    <div class="card-body">
        <h4><i class="bi bi-signpost-split me-2"></i>Attendance Pipeline</h4>
        <div class="diagram-container">
            <div class="mermaid">
flowchart TD
    A["HR Configures Work Schedule\nstart_time, end_time\nbreak_start, break_end\nworking_days per company"] --> B["Employee Clocks In\nTimestamp recorded"]
    B --> C{"clock_in vs schedule"}
    C -->|"Within 15 min"| D["Status: present"]
    C -->|"> 15 min late"| E["Status: late"]
    D & E --> F["Employee Clocks Out\nclock_out timestamp\nwork_hours calculated"]
    F --> G{"Overtime needed?"}
    G -->|"Yes"| H["Submit OT Request\ndate, hours, reason\nStatus: pending"]
    H --> I{"HR Review"}
    I -->|"Approve"| J["OT approved\novertime_hours updated"]
    I -->|"Reject"| K["OT rejected\nwith reason"]

    style B fill:#cfe2ff,stroke:#0d6efd,color:#000
    style D fill:#d1e7dd,stroke:#198754,color:#000
    style E fill:#fef3cd,stroke:#ffc107,color:#000
    style F fill:#d1e7dd,stroke:#198754,color:#000
    style H fill:#fef3cd,stroke:#ffc107,color:#000
    style J fill:#d1e7dd,stroke:#198754,color:#000
    style K fill:#f8d7da,stroke:#dc3545,color:#000
            </div>
        </div>
    </div>
</div>

{{-- ── Status Values ── --}}
<div class="kb-section card" id="status">
    <div class="card-body">
        <h4><i class="bi bi-tag me-2"></i>Status Values</h4>
        <div class="row g-3">
            <div class="col-md-6">
                <h6>Attendance Record</h6>
                <div class="d-flex flex-wrap gap-2">
                    <span class="status-badge" style="background:#d1e7dd;color:#0f5132;">present</span>
                    <span class="status-badge" style="background:#fef3cd;color:#856404;">late</span>
                    <span class="status-badge" style="background:#f8d7da;color:#842029;">absent</span>
                </div>
            </div>
            <div class="col-md-6">
                <h6>Overtime Request</h6>
                <div class="d-flex flex-wrap gap-2">
                    <span class="status-badge" style="background:#fef3cd;color:#856404;">pending</span>
                    <span class="status-badge" style="background:#d1e7dd;color:#0f5132;">approved</span>
                    <span class="status-badge" style="background:#f8d7da;color:#842029;">rejected</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Work Schedule ── --}}
<div class="kb-section card" id="schedule">
    <div class="card-body">
        <h4><i class="bi bi-calendar3 me-2"></i>Work Schedule Configuration</h4>
        <table class="table table-sm relation-table">
            <thead><tr><th>Field</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>name</code></td><td>Schedule name (e.g., "Standard 9-6")</td></tr>
                <tr><td><code>company</code></td><td>Company scope</td></tr>
                <tr><td><code>start_time</code></td><td>Expected clock-in time (e.g., 09:00)</td></tr>
                <tr><td><code>end_time</code></td><td>Expected clock-out time (e.g., 18:00)</td></tr>
                <tr><td><code>break_start / break_end</code></td><td>Lunch break period</td></tr>
                <tr><td><code>working_days</code></td><td>JSON array of day numbers (0=Sun through 6=Sat)</td></tr>
                <tr><td><code>is_default</code></td><td>Default schedule for the company</td></tr>
                <tr><td><code>grace_period</code></td><td>15 minutes before marked late</td></tr>
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
    EMPLOYEES ||--o{ ATTENDANCE_RECORDS : records
    WORK_SCHEDULES ||--o{ ATTENDANCE_RECORDS : "applies to"
    EMPLOYEES ||--o{ OVERTIME_REQUESTS : requests
    COMPANIES ||--o{ WORK_SCHEDULES : configures
    ATTENDANCE_RECORDS {
        datetime clock_in
        datetime clock_out
        string status
        decimal work_hours
        decimal overtime_hours
    }
    OVERTIME_REQUESTS {
        date request_date
        decimal hours
        string status
        string reason
    }
            </div>
        </div>
    </div>
</div>

{{-- ── Payroll Link ── --}}
<div class="kb-section card" id="payroll">
    <div class="card-body">
        <h4><i class="bi bi-link-45deg me-2"></i>Payroll Integration</h4>
        <div class="alert alert-info small mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Late arrivals and absences can be linked to payroll deductions. The attendance data is visible to HR during pay run generation for manual review.
            Approved overtime hours may trigger overtime pay calculations based on company policy.
        </div>
    </div>
</div>
@endsection
