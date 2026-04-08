@extends('superadmin.knowledge-base.topic-layout')

@section('topic-content')
{{-- ── Overview ── --}}
<div class="kb-section card" id="overview">
    <div class="card-body">
        <h4><i class="bi bi-envelope me-2"></i>All Email Classes by Module</h4>
        <p class="text-muted small">The system has 25 mail classes. All use the default sender <code>hr@claritas.com</code> (MAIL_FROM_ADDRESS).</p>
        <div class="diagram-container">
            <div class="mermaid">
flowchart TD
    subgraph OB_MAIL["Onboarding (5)"]
        M1["OnboardingInviteMail"]
        M2["ConsentRequestMail"]
        M3["OnboardingEditNotificationMail"]
        M4["CalendarInvite"]
        M5["WelcomeNewHire"]
    end

    subgraph OFF_MAIL["Offboarding (4)"]
        M6["OffboardingNoticeMail"]
        M7["OffboardingWeekReminderMail"]
        M8["OffboardingReminderMail"]
        M9["OffboardingSendoffMail"]
    end

    subgraph ASSET_MAIL["Assets (1)"]
        M10["AarfAcknowledgementMail"]
    end

    subgraph PAY_MAIL["Payroll (2)"]
        M11["PayslipReadyMail"]
        M12["EaFormReadyMail"]
    end

    subgraph LEAVE_MAIL["Leave (3)"]
        M13["LeaveApplicationNotifyMail"]
        M14["LeaveApprovalNotifyMail"]
        M22["PendingLeaveReminderMail"]
    end

    subgraph CLAIM_MAIL["Claims (4)"]
        M15["ClaimSubmittedMail"]
        M16["ClaimApprovedMail"]
        M17["ClaimRejectedMail"]
        M18["ClaimReminderMail"]
    end

    subgraph EMP_MAIL["Employee Edits (2)"]
        M19["EmployeeConsentRequestMail"]
        M20["OnboardingConsentRequestMail"]
    end

    subgraph SEC_MAIL["Security & System (3)"]
        M23["SecurityAuditMail"]
        M24["SuspiciousActivityAlert"]
        M25["WeeklyPendingSweepMail"]
    end

    subgraph OTHER["Other (1)"]
        M21["AnnouncementMail"]
    end
            </div>
        </div>
    </div>
</div>

{{-- ── Detailed Table ── --}}
<div class="kb-section card" id="details">
    <div class="card-body">
        <h4><i class="bi bi-table me-2"></i>Complete Email Reference</h4>
        <div class="table-responsive">
            <table class="table table-sm relation-table">
                <thead><tr><th>Module</th><th>Mail Class</th><th>Trigger</th><th>Recipient</th><th>Type</th></tr></thead>
                <tbody>
                    {{-- Onboarding --}}
                    <tr class="table-light"><td colspan="5" class="fw-bold">Onboarding</td></tr>
                    <tr><td></td><td><code>OnboardingInviteMail</code></td><td>HR sends invite</td><td>New hire personal email</td><td>Action required</td></tr>
                    <tr><td></td><td><code>ConsentRequestMail</code></td><td>HR completes record</td><td>New hire work email</td><td>Action required</td></tr>
                    <tr><td></td><td><code>OnboardingEditNotificationMail</code></td><td>HR edits sections A/F/G/H/I</td><td>New hire</td><td>Info only</td></tr>
                    <tr><td></td><td><code>CalendarInvite</code></td><td>Record created</td><td>HR + IT contacts</td><td>.ics attachment</td></tr>
                    <tr><td></td><td><code>WelcomeNewHire</code></td><td>start_date activation</td><td>New hire work email</td><td>Info</td></tr>

                    {{-- Offboarding --}}
                    <tr class="table-light"><td colspan="5" class="fw-bold">Offboarding</td></tr>
                    <tr><td></td><td><code>OffboardingNoticeMail</code></td><td>30 days before exit</td><td>Employee + manager + team</td><td>Scheduled</td></tr>
                    <tr><td></td><td><code>OffboardingWeekReminderMail</code></td><td>7 days before exit</td><td>Employee + manager</td><td>Scheduled</td></tr>
                    <tr><td></td><td><code>OffboardingReminderMail</code></td><td>3 days before exit</td><td>Employee + manager</td><td>Scheduled</td></tr>
                    <tr><td></td><td><code>OffboardingSendoffMail</code></td><td>Post-exit, assets cleared</td><td>Team farewell</td><td>Scheduled</td></tr>

                    {{-- Assets --}}
                    <tr class="table-light"><td colspan="5" class="fw-bold">Assets</td></tr>
                    <tr><td></td><td><code>AarfAcknowledgementMail</code></td><td>IT Manager acknowledges AARF</td><td>Employee (token link)</td><td>Action required</td></tr>

                    {{-- Payroll --}}
                    <tr class="table-light"><td colspan="5" class="fw-bold">Payroll</td></tr>
                    <tr><td></td><td><code>PayslipReadyMail</code></td><td>Payslip issued</td><td>Employee</td><td>Info</td></tr>
                    <tr><td></td><td><code>EaFormReadyMail</code></td><td>EA form generated</td><td>Employee</td><td>Info</td></tr>

                    {{-- Leave --}}
                    <tr class="table-light"><td colspan="5" class="fw-bold">Leave</td></tr>
                    <tr><td></td><td><code>LeaveApplicationNotifyMail</code></td><td>Employee applies</td><td>HR</td><td>Action required</td></tr>
                    <tr><td></td><td><code>LeaveApprovalNotifyMail</code></td><td>HR approves/rejects</td><td>Employee</td><td>Info</td></tr>
                    <tr><td></td><td><code>PendingLeaveReminderMail</code></td><td>Daily 09:00 cron</td><td>Reporting managers with pending requests</td><td>Scheduled</td></tr>

                    {{-- Claims --}}
                    <tr class="table-light"><td colspan="5" class="fw-bold">Expense Claims</td></tr>
                    <tr><td></td><td><code>ClaimSubmittedMail</code></td><td>Employee submits / Manager forwards to HR</td><td>Manager / All HR</td><td>Action required</td></tr>
                    <tr><td></td><td><code>ClaimApprovedMail</code></td><td>Manager or HR approves</td><td>Employee</td><td>Info</td></tr>
                    <tr><td></td><td><code>ClaimRejectedMail</code></td><td>Manager or HR rejects</td><td>Employee</td><td>Info</td></tr>
                    <tr><td></td><td><code>ClaimReminderMail</code></td><td>Deadline approaching</td><td>Employees with draft items</td><td>Scheduled</td></tr>

                    {{-- Employee Edits --}}
                    <tr class="table-light"><td colspan="5" class="fw-bold">Employee Edits</td></tr>
                    <tr><td></td><td><code>EmployeeConsentRequestMail</code></td><td>HR edits active employee</td><td>Employee (token link)</td><td>Action required</td></tr>
                    <tr><td></td><td><code>OnboardingConsentRequestMail</code></td><td>Specific onboarding consent</td><td>New hire</td><td>Action required</td></tr>

                    {{-- Security & System --}}
                    <tr class="table-light"><td colspan="5" class="fw-bold">Security & System</td></tr>
                    <tr><td></td><td><code>SecurityAuditMail</code></td><td>Hourly cron</td><td>System administrators</td><td>Scheduled</td></tr>
                    <tr><td></td><td><code>SuspiciousActivityAlert</code></td><td>Threat detected in real-time</td><td>System administrators</td><td>Action required</td></tr>
                    <tr><td></td><td><code>WeeklyPendingSweepMail</code></td><td>Wednesday midnight cron</td><td>Employee / Manager / HR / IT (per item type)</td><td>Scheduled</td></tr>

                    {{-- Other --}}
                    <tr class="table-light"><td colspan="5" class="fw-bold">Other</td></tr>
                    <tr><td></td><td><code>AnnouncementMail</code></td><td>HR/Manager creates announcement</td><td>All employees</td><td>Info</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Email Types ── --}}
<div class="kb-section card" id="types">
    <div class="card-body">
        <h4><i class="bi bi-tags me-2"></i>Email Type Legend</h4>
        <div class="d-flex flex-wrap gap-3">
            <div class="small">
                <span class="status-badge" style="background:#d1e7dd;color:#0f5132;">Info</span>
                — Informational, no action needed
            </div>
            <div class="small">
                <span class="status-badge" style="background:#fef3cd;color:#856404;">Action required</span>
                — Recipient must take action (acknowledge, approve, etc.)
            </div>
            <div class="small">
                <span class="status-badge" style="background:#cfe2ff;color:#084298;">Scheduled</span>
                — Triggered by cron job, not user action
            </div>
        </div>
    </div>
</div>
@endsection
