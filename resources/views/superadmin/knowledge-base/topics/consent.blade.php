@extends('superadmin.knowledge-base.topic-layout')

@section('topic-content')
{{-- ── Two Flows ── --}}
<div class="kb-section card" id="overview">
    <div class="card-body">
        <h4><i class="bi bi-signpost-split me-2"></i>Two Distinct Edit Flows</h4>
        <div class="alert alert-warning small">
            <i class="bi bi-exclamation-triangle me-1"></i>
            The system has <strong>two completely separate</strong> edit/consent mechanisms depending on whether the record is an onboarding (pre-hire) or employee (post-hire).
        </div>
        <div class="diagram-container">
            <div class="mermaid">
flowchart LR
    subgraph PRE["Pre-Hire (Onboarding)"]
        A["HR Edits Onboarding"] --> B["OnboardingEditLog\nconsent_required: false"]
        B --> C["OnboardingEditNotificationMail\nNotification only, no token"]
    end

    subgraph POST["Post-Hire (Employee)"]
        D["HR Edits Employee"] --> E["EmployeeEditLog\nconsent_required: true"]
        E --> F["EmployeeConsentRequestMail\n64-char token, 60 min expiry"]
        F --> G["Employee re-acknowledges\nvia /profile/re-consent"]
    end

    style C fill:#cfe2ff,stroke:#0d6efd,color:#000
    style F fill:#fef3cd,stroke:#ffc107,color:#000
    style G fill:#d1e7dd,stroke:#198754,color:#000
            </div>
        </div>
    </div>
</div>

{{-- ── Sections That Trigger ── --}}
<div class="kb-section card" id="sections">
    <div class="card-body">
        <h4><i class="bi bi-layout-text-sidebar me-2"></i>Sections That Trigger Emails</h4>
        <table class="table table-sm relation-table">
            <thead><tr><th>Section</th><th>Onboarding Edit</th><th>Employee Edit</th></tr></thead>
            <tbody>
                <tr><td><span class="badge bg-primary">A</span> Personal Details</td><td>Notification email</td><td>Consent email + token</td></tr>
                <tr><td><span class="badge bg-secondary">B</span> Work Details</td><td>No email</td><td>No email</td></tr>
                <tr><td><span class="badge bg-secondary">C</span> Asset Provisioning</td><td>No email</td><td>No email</td></tr>
                <tr><td><span class="badge bg-warning text-dark">F</span> Education</td><td>Notification email</td><td>Consent email + token</td></tr>
                <tr><td><span class="badge bg-warning text-dark">G</span> Spouse</td><td>Notification email</td><td>Consent email + token</td></tr>
                <tr><td><span class="badge bg-warning text-dark">H</span> Emergency Contacts</td><td>Notification email</td><td>Consent email + token</td></tr>
                <tr><td><span class="badge bg-warning text-dark">I</span> Children</td><td>Notification email</td><td>Consent email + token</td></tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ── Onboarding Edit Flow ── --}}
<div class="kb-section card" id="onboarding-flow">
    <div class="card-body">
        <h4><i class="bi bi-pencil me-2"></i>Onboarding Edit Flow (Notification Only)</h4>
        <div class="diagram-container">
            <div class="mermaid">
sequenceDiagram
    participant HR as HR Manager
    participant SYS as System
    participant LOG as OnboardingEditLog
    participant NEW as New Hire

    HR->>SYS: Edit onboarding (Sections A/F/G/H/I)
    SYS->>LOG: Create log entry (consent_required=false)
    SYS->>NEW: OnboardingEditNotificationMail
    Note over NEW: Information only — no action required
    Note over SYS: buildStagingJson() preserves F-I JSON when B/C edited
            </div>
        </div>
    </div>
</div>

{{-- ── Employee Edit Flow ── --}}
<div class="kb-section card" id="employee-flow">
    <div class="card-body">
        <h4><i class="bi bi-pencil-square me-2"></i>Employee Edit Flow (Consent Required)</h4>
        <div class="diagram-container">
            <div class="mermaid">
sequenceDiagram
    participant HR as HR Manager
    participant SYS as System
    participant LOG as EmployeeEditLog
    participant EMP as Employee

    HR->>SYS: Edit employee (Sections A/F/G/H/I)
    SYS->>LOG: Create log (consent_required=true, token=random64, expires=60min)
    SYS->>EMP: EmployeeConsentRequestMail (with token link)
    EMP->>SYS: Click link → /profile/re-consent (must be logged in)
    SYS->>SYS: Validate token not expired
    EMP->>SYS: Review changes & Acknowledge
    SYS->>LOG: acknowledged_by, acknowledged_at set
    SYS->>SYS: consent_given_at updated on employee
            </div>
        </div>
    </div>
</div>

{{-- ── Edit Log Schema ── --}}
<div class="kb-section card" id="schema">
    <div class="card-body">
        <h4><i class="bi bi-database me-2"></i>Edit Log Schema</h4>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="border rounded p-3">
                    <h6 class="text-primary">OnboardingEditLog</h6>
                    <ul class="small mb-0">
                        <li><code>onboarding_id</code></li>
                        <li><code>edited_by_user_id</code>, name, role</li>
                        <li><code>sections_changed</code> (JSON array)</li>
                        <li><code>change_notes</code></li>
                        <li><code>consent_required = false</code></li>
                        <li><code>consent_token = null</code></li>
                        <li><code>consent_sent_to_email</code></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-3">
                    <h6 class="text-warning">EmployeeEditLog</h6>
                    <ul class="small mb-0">
                        <li><code>employee_id</code></li>
                        <li><code>edited_by_user_id</code>, name, role</li>
                        <li><code>sections_changed</code> (JSON array)</li>
                        <li><code>change_notes</code></li>
                        <li><code>consent_required = true</code></li>
                        <li><code>consent_token</code> (64 chars)</li>
                        <li><code>consent_token_expires_at</code> (60 min)</li>
                        <li><code>acknowledged_by</code>, <code>acknowledged_at</code></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
