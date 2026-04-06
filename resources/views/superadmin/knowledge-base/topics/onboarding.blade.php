@extends('superadmin.knowledge-base.topic-layout')

@section('topic-content')
{{-- ── Pipeline Diagram ── --}}
<div class="kb-section card" id="pipeline">
    <div class="card-body">
        <h4><i class="bi bi-signpost-split me-2"></i>Onboarding Pipeline</h4>
        <div class="diagram-container">
            <div class="mermaid">
flowchart TD
    A["HR Creates Record\nSections A-C\nstatus: pending"] -->|"Send Invite"| B["Invite Email Sent\ninvite_token generated\nExpires in 7 days"]
    B -->|"New Hire clicks link"| C["Public Invite Form\n/onboarding-invite/token\nNo auth required"]
    C -->|"Fills Sections F-I"| D["Staging JSON Saved\npersonal_details.invite_staging_json\nNOT in relationship tables yet"]
    D -->|"HR Reviews & Completes"| E["Status: active\nCalendar invites sent\nConsent email sent"]
    E -->|"start_date arrives"| F["ActivateEmployees Command\nRuns every minute"]
    F --> G["Employee Record Created\npopulateFromOnboarding\nFlushes JSON → DB tables"]
    G --> H["WelcomeNewHire Email\nEmployee is now active"]

    style A fill:#fef3cd,stroke:#ffc107,color:#000
    style B fill:#cfe2ff,stroke:#0d6efd,color:#000
    style C fill:#e2e3e5,stroke:#6c757d,color:#000
    style D fill:#fff3cd,stroke:#fd7e14,color:#000
    style E fill:#d1e7dd,stroke:#198754,color:#000
    style F fill:#f8d7da,stroke:#dc3545,color:#000
    style G fill:#d1e7dd,stroke:#198754,color:#000
    style H fill:#d1e7dd,stroke:#198754,color:#000
            </div>
        </div>
    </div>
</div>

{{-- ── Staging JSON Explained ── --}}
<div class="kb-section card" id="staging">
    <div class="card-body">
        <h4><i class="bi bi-braces me-2"></i>invite_staging_json Mechanism</h4>
        <div class="diagram-container">
            <div class="mermaid">
flowchart LR
    subgraph INPUT["New Hire Submits"]
        F["Section F\nEducation"]
        G["Section G\nSpouse"]
        H["Section H\nEmergency Contacts"]
        I["Section I\nChildren"]
    end

    subgraph STAGING["Temporary Storage"]
        JSON["personal_details\n.invite_staging_json\n(JSON blob)"]
    end

    subgraph ACTIVATION["On start_date"]
        POP["populateFromOnboarding()"]
    end

    subgraph TABLES["Permanent Tables"]
        T1["employee_education_histories"]
        T2["employee_spouse_details"]
        T3["employee_emergency_contacts"]
        T4["employee_child_registrations"]
    end

    F & G & H & I --> JSON
    JSON -->|"ActivateEmployees"| POP
    POP --> T1 & T2 & T3 & T4
            </div>
        </div>
        <div class="alert alert-info small mt-3 mb-0">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Key Rule:</strong> When HR edits Sections B/C only, <code>buildStagingJson()</code> preserves existing JSON unchanged to prevent wiping new-hire data.
        </div>
    </div>
</div>

{{-- ── Sections Breakdown ── --}}
<div class="kb-section card" id="sections">
    <div class="card-body">
        <h4><i class="bi bi-layout-text-sidebar me-2"></i>Form Sections</h4>
        <table class="table table-sm relation-table">
            <thead>
                <tr><th>Section</th><th>Name</th><th>Input By</th><th>Storage</th><th>Email on Edit?</th></tr>
            </thead>
            <tbody>
                <tr><td><span class="badge bg-primary">A</span></td><td>Personal Details</td><td>HR</td><td>personal_details table</td><td>Yes (notification)</td></tr>
                <tr><td><span class="badge bg-secondary">B</span></td><td>Work Details</td><td>HR</td><td>work_details table</td><td>No</td></tr>
                <tr><td><span class="badge bg-secondary">C</span></td><td>Asset Provisioning</td><td>HR</td><td>asset_provisionings table</td><td>No</td></tr>
                <tr><td><span class="badge bg-warning text-dark">F</span></td><td>Education</td><td>New Hire</td><td>invite_staging_json → education_histories</td><td>Yes (notification)</td></tr>
                <tr><td><span class="badge bg-warning text-dark">G</span></td><td>Spouse</td><td>New Hire</td><td>invite_staging_json → spouse_details</td><td>Yes (notification)</td></tr>
                <tr><td><span class="badge bg-warning text-dark">H</span></td><td>Emergency Contacts</td><td>New Hire</td><td>invite_staging_json → emergency_contacts</td><td>Yes (notification)</td></tr>
                <tr><td><span class="badge bg-warning text-dark">I</span></td><td>Children</td><td>New Hire</td><td>invite_staging_json → child_registrations</td><td>Yes (notification)</td></tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ── Emails ── --}}
<div class="kb-section card" id="emails">
    <div class="card-body">
        <h4><i class="bi bi-envelope me-2"></i>Email Notifications</h4>
        <table class="table table-sm relation-table">
            <thead><tr><th>Trigger</th><th>Mail Class</th><th>Recipient</th></tr></thead>
            <tbody>
                <tr><td>HR sends invite</td><td><code>OnboardingInviteMail</code></td><td>New hire personal email</td></tr>
                <tr><td>HR completes record</td><td><code>ConsentRequestMail</code></td><td>New hire work email</td></tr>
                <tr><td>HR edits sections A/F/G/H/I</td><td><code>OnboardingEditNotificationMail</code></td><td>New hire (notification only)</td></tr>
                <tr><td>HR creates record</td><td><code>CalendarInvite</code></td><td>HR/IT contacts</td></tr>
                <tr><td>start_date activation</td><td><code>WelcomeNewHire</code></td><td>New hire work email</td></tr>
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
    ONBOARDINGS ||--|| PERSONAL_DETAILS : has
    ONBOARDINGS ||--|| WORK_DETAILS : has
    ONBOARDINGS ||--|| ASSET_PROVISIONINGS : has
    ONBOARDINGS ||--o| AARFS : generates
    ONBOARDINGS }o--|| EMPLOYEES : "activates into"
    ONBOARDINGS ||--o{ ONBOARDING_EDIT_LOGS : audits
    PERSONAL_DETAILS {
        string invite_staging_json
        string nric_file_paths
        timestamp consent_given_at
    }
    ONBOARDINGS {
        string status
        string invite_token
        date start_date
        boolean invite_submitted
    }
            </div>
        </div>
    </div>
</div>

{{-- ── Role Access ── --}}
<div class="kb-section card" id="roles">
    <div class="card-body">
        <h4><i class="bi bi-shield me-2"></i>Role Access</h4>
        <table class="table table-sm relation-table">
            <thead><tr><th>Role</th><th>Access Level</th></tr></thead>
            <tbody>
                <tr><td><span class="badge bg-danger">HR Manager</span></td><td>Full CRUD, send invites, edit all sections</td></tr>
                <tr><td><span class="badge bg-warning text-dark">HR Executive / Intern</span></td><td>View only or limited editing</td></tr>
                <tr><td><span class="badge bg-info">IT Manager</span></td><td>View AARF, manage assets, acknowledge</td></tr>
                <tr><td><span class="badge bg-dark">SuperAdmin</span></td><td>Same as HR Manager</td></tr>
                <tr><td><span class="badge bg-secondary">New Hire (Public)</span></td><td>Fill invite form (no auth)</td></tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
