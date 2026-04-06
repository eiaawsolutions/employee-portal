@extends('superadmin.knowledge-base.topic-layout')

@section('topic-content')
{{-- ── Login Flow ── --}}
<div class="kb-section card" id="login">
    <div class="card-body">
        <h4><i class="bi bi-box-arrow-in-right me-2"></i>Login Flow</h4>
        <div class="diagram-container">
            <div class="mermaid">
flowchart TD
    A["User enters\nwork_email + password"] --> B["WorkEmailUserProvider\nmaps email → work_email column"]
    B --> C{"User found?"}
    C -->|"No"| D["Generic error\n(no user enumeration)"]
    C -->|"Yes"| E{"is_active?"}
    E -->|"No"| F["Account deactivated\nShow reason"]
    E -->|"Yes"| G{"exit_date <= today?"}
    G -->|"Yes"| H["Account expired\nCannot login"]
    G -->|"No"| I{"Password correct?"}
    I -->|"No"| J["Increment login_attempts"]
    J --> K{"attempts >= 5?"}
    K -->|"Yes"| L["ACCOUNT LOCKED\nis_active = false\nreason: login_lockout\nSecurityAuditLog"]
    K -->|"No"| D
    I -->|"Yes"| M["Reset login_attempts = 0\nGenerate session_token\nStore in DB + session\nSecurityAuditLog"]
    M --> N{"User role?"}
    N -->|"HR / SuperAdmin"| O["→ hr.dashboard"]
    N -->|"IT"| P["→ it.dashboard"]
    N -->|"Employee"| Q["→ user.dashboard"]

    style L fill:#f8d7da,stroke:#dc3545,color:#000
    style M fill:#d1e7dd,stroke:#198754,color:#000
    style D fill:#fef3cd,stroke:#ffc107,color:#000
            </div>
        </div>
    </div>
</div>

{{-- ── Single Session ── --}}
<div class="kb-section card" id="session">
    <div class="card-body">
        <h4><i class="bi bi-phone me-2"></i>Single-Session Enforcement</h4>
        <div class="diagram-container">
            <div class="mermaid">
sequenceDiagram
    participant D1 as Device 1
    participant SRV as Server
    participant DB as Database
    participant D2 as Device 2

    D1->>SRV: Login (work_email, password)
    SRV->>DB: Store session_token = "abc"
    SRV->>D1: Session cookie (token: abc)
    Note over D1: Active session

    D2->>SRV: Login (same user)
    SRV->>DB: Store session_token = "xyz" (overwrites)
    SRV->>D2: Session cookie (token: xyz)

    D1->>SRV: Next request (token: abc)
    SRV->>DB: Check: abc ≠ xyz
    SRV->>D1: Logout! "Signed in from another device"
            </div>
        </div>
        <div class="alert alert-info small mt-3 mb-0">
            <i class="bi bi-info-circle me-1"></i>
            <strong>EnforceSingleSession middleware</strong> runs on every authenticated request. If DB token doesn't match session token, the user is logged out and redirected to login.
        </div>
    </div>
</div>

{{-- ── Password Reset ── --}}
<div class="kb-section card" id="reset">
    <div class="card-body">
        <h4><i class="bi bi-key me-2"></i>Password Reset Flow</h4>
        <div class="diagram-container">
            <div class="mermaid">
flowchart LR
    A["Forgot Password\nEnter work_email"] -->|"Throttled: 5/min"| B["Generate token\nStore in password_reset_tokens"]
    B --> C["ResetPasswordNotification\nLink: /reset-password/token?email=x"]
    C --> D["User clicks link\n60 min expiry"]
    D --> E["New password form\nValidate token + expiry"]
    E --> F["Password updated\nToken deleted\nSecurityAuditLog"]
            </div>
        </div>
    </div>
</div>

{{-- ── Session Timeout ── --}}
<div class="kb-section card" id="timeout">
    <div class="card-body">
        <h4><i class="bi bi-hourglass-split me-2"></i>Session & Idle Timeout</h4>
        <table class="table table-sm relation-table">
            <thead><tr><th>Setting</th><th>Value</th><th>Purpose</th></tr></thead>
            <tbody>
                <tr><td><code>password_timeout</code></td><td>3 hours (10,800s)</td><td>Re-authentication required</td></tr>
                <tr><td>Idle timeout (JS)</td><td>15 minutes</td><td>Frontend inactivity detection</td></tr>
                <tr><td>Warning modal</td><td>30 seconds</td><td>Countdown before auto-logout</td></tr>
                <tr><td>Password reset expiry</td><td>60 minutes</td><td>Token validity window</td></tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ── Security Middleware ── --}}
<div class="kb-section card" id="middleware">
    <div class="card-body">
        <h4><i class="bi bi-shield-check me-2"></i>Security Middleware Stack</h4>
        <div class="diagram-container">
            <div class="mermaid">
flowchart LR
    A["Every Request"] --> B["auth\nLaravel authentication"]
    B --> C["EnforceSingleSession\nToken match check"]
    C --> D["SecurityAuditMiddleware\nRate anomaly detection\n403 logging"]
    D --> E["Controller Action"]
            </div>
        </div>
        <table class="table table-sm relation-table mt-3">
            <thead><tr><th>Middleware</th><th>Purpose</th></tr></thead>
            <tbody>
                <tr><td><code>EnforceSingleSession</code></td><td>Kicks previous sessions when new login detected</td></tr>
                <tr><td><code>SecurityAuditMiddleware</code></td><td>Detects rate anomalies (automated attacks), logs 403 responses</td></tr>
                <tr><td><code>ForceHttps</code></td><td>Redirects HTTP → HTTPS in production</td></tr>
                <tr><td><code>SecurityHeaders</code></td><td>CSP, HSTS, X-Frame-Options, X-Content-Type-Options</td></tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ── Lockout Recovery ── --}}
<div class="kb-section card" id="lockout">
    <div class="card-body">
        <h4><i class="bi bi-person-x me-2"></i>Account Lockout Recovery</h4>
        <div class="diagram-container">
            <div class="mermaid">
flowchart LR
    A["5 failed logins\nAccount locked"] --> B["Superadmin goes to\nAccount Management"]
    B --> C["Find locked account\nReason: login_lockout"]
    C --> D["Click Activate\nis_active=true\nlogin_attempts=0"]
            </div>
        </div>
    </div>
</div>
@endsection
