@extends('superadmin.knowledge-base.topic-layout')

@section('topic-content')
{{-- ── Pipeline ── --}}
<div class="kb-section card" id="pipeline">
    <div class="card-body">
        <h4><i class="bi bi-signpost-split me-2"></i>Offboarding Pipeline</h4>
        <div class="diagram-container">
            <div class="mermaid">
flowchart TD
    A["HR Sets exit_date\non Employee Record"] --> B["Offboarding Record Created\nAll status columns: pending"]
    B --> C{"30 days before exit_date"}
    C -->|"offboarding:notify"| D["OffboardingNoticeMail\n+ Calendar .ics to team"]
    D --> E{"7 days before"}
    E -->|"offboarding:notify"| F["OffboardingWeekReminderMail"]
    F --> G{"3 days before"}
    G -->|"offboarding:notify"| H["OffboardingReminderMail"]
    H --> I["exit_date Arrives"]
    I --> J["ActivateEmployees at 23:59\nis_active = false\nactive_until = exit_date"]
    J --> K{"All assets returned?"}
    K -->|"No"| L["Sendoff held\nAssets still assigned"]
    K -->|"Yes"| M["OffboardingSendoffMail sent\naarf_status = done"]
    L -->|"Assets cleared later"| M

    style A fill:#fef3cd,stroke:#ffc107,color:#000
    style D fill:#cfe2ff,stroke:#0d6efd,color:#000
    style F fill:#cfe2ff,stroke:#0d6efd,color:#000
    style H fill:#fff3cd,stroke:#fd7e14,color:#000
    style J fill:#f8d7da,stroke:#dc3545,color:#000
    style M fill:#d1e7dd,stroke:#198754,color:#000
            </div>
        </div>
    </div>
</div>

{{-- ── Status Columns ── --}}
<div class="kb-section card" id="status">
    <div class="card-body">
        <h4><i class="bi bi-toggles me-2"></i>Status Tracking (9 Columns)</h4>
        <p class="text-muted small">Unlike other modules, offboarding uses <strong>individual status columns</strong> rather than a single status enum.</p>
        <table class="table table-sm relation-table">
            <thead><tr><th>Column</th><th>Values</th><th>Trigger</th></tr></thead>
            <tbody>
                <tr><td><code>notice_email_status</code></td><td><span class="status-badge" style="background:#fef3cd;color:#856404;">pending</span> → <span class="status-badge" style="background:#d1e7dd;color:#0f5132;">sent</span></td><td>30 days before exit</td></tr>
                <tr><td><code>reminder_email_status</code></td><td>pending → sent</td><td>3 days before exit</td></tr>
                <tr><td><code>week_reminder_email_status</code></td><td>pending → sent</td><td>7 days before exit</td></tr>
                <tr><td><code>sendoff_email_status</code></td><td>pending → sent</td><td>After exit + assets cleared</td></tr>
                <tr><td><code>calendar_reminder_status</code></td><td>pending → sent</td><td>With notice email</td></tr>
                <tr><td><code>exiting_email_status</code></td><td>pending → sent</td><td>Exit day</td></tr>
                <tr><td><code>aarf_status</code></td><td>pending → done</td><td>Assets acknowledged</td></tr>
                <tr><td><code>asset_cleaning_status</code></td><td>pending → done</td><td>All assets returned</td></tr>
                <tr><td><code>deactivation_status</code></td><td>pending → done</td><td>User deactivated on exit_date</td></tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ── Notification Timeline ── --}}
<div class="kb-section card" id="timeline">
    <div class="card-body">
        <h4><i class="bi bi-clock-history me-2"></i>Notification Timeline</h4>
        <div class="diagram-container">
            <div class="mermaid">
gantt
    title Offboarding Notification Timeline
    dateFormat X
    axisFormat %s days
    section Notifications
    Notice Email (30d before)     :done, 0, 1
    Week Reminder (7d before)     :active, 23, 24
    3-Day Reminder                :crit, 27, 28
    Exit Date                     :milestone, 30, 30
    Account Deactivation (23:59)  :crit, 30, 31
    Sendoff (assets cleared)      :done, 31, 32
            </div>
        </div>
    </div>
</div>

{{-- ── Asset Gate ── --}}
<div class="kb-section card" id="asset-gate">
    <div class="card-body">
        <h4><i class="bi bi-shield-exclamation me-2"></i>Asset Return Gate</h4>
        <div class="alert alert-warning small mb-0">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <strong>Critical Logic:</strong> The sendoff email is <strong>held</strong> until all assigned assets are returned
            (<code>asset_cleaning_status = done</code>). This prevents farewell being sent while company assets remain with the exiting employee.
        </div>
    </div>
</div>

{{-- ── Emails ── --}}
<div class="kb-section card" id="emails">
    <div class="card-body">
        <h4><i class="bi bi-envelope me-2"></i>Email Notifications</h4>
        <table class="table table-sm relation-table">
            <thead><tr><th>Timing</th><th>Mail Class</th><th>Recipients</th></tr></thead>
            <tbody>
                <tr><td>30 days before</td><td><code>OffboardingNoticeMail</code></td><td>Employee + manager + HR/IT team</td></tr>
                <tr><td>7 days before</td><td><code>OffboardingWeekReminderMail</code></td><td>Employee + manager</td></tr>
                <tr><td>3 days before</td><td><code>OffboardingReminderMail</code></td><td>Employee + manager</td></tr>
                <tr><td>Post-exit (assets cleared)</td><td><code>OffboardingSendoffMail</code></td><td>Team farewell</td></tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ── Two View Paths ── --}}
<div class="kb-section card" id="views">
    <div class="card-body">
        <h4><i class="bi bi-layout-split me-2"></i>Two Separate View Paths</h4>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="border rounded p-3">
                    <h6 class="text-primary"><i class="bi bi-person-badge me-1"></i>HR View</h6>
                    <ul class="small mb-0">
                        <li>Route: <code>hr.offboarding.show</code></li>
                        <li>Full edit access to all sections</li>
                        <li>Can download contracts/handbook</li>
                        <li>Can manage IT tasks</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-3">
                    <h6 class="text-info"><i class="bi bi-laptop me-1"></i>IT View</h6>
                    <ul class="small mb-0">
                        <li>Route: <code>it.offboarding-show</code></li>
                        <li>Read-only on employee sections</li>
                        <li>Contracts/handbook locked with "HR only" badge</li>
                        <li>Can update asset_cleaning_status</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
