@extends('superadmin.knowledge-base.topic-layout')

@section('topic-content')
{{-- ── Overview ── --}}
<div class="kb-section card" id="overview">
    <div class="card-body">
        <h4><i class="bi bi-clock-history me-2"></i>All Scheduled Jobs</h4>
        <div class="diagram-container">
            <div class="mermaid">
flowchart TD
    subgraph MINUTE["Every Minute"]
        A["employees:activate\nActivate on start_date\nDeactivate on exit_date"]
        B["offboarding:notify\n30d / 7d / 3d reminders\nSendoff when assets cleared"]
    end

    subgraph HOURLY["Hourly"]
        C["security:audit-report\nGenerate security summary"]
        R["system:refresh-metadata\nCache system overview data"]
    end

    subgraph DAILY["Daily at 09:00"]
        D["leave:remind-managers\nPending leave reminders"]
        E["claims:remind\nPending claim reminders"]
    end

    subgraph WEEKLY["Wednesday at Midnight"]
        W["sweep:pending-weekly\nConsents, AARF, Leave, Claims"]
    end

    subgraph BACKUP["Backup Schedule"]
        F["Daily 02:00\nFull encrypted backup\nKeep 30 days"]
        G["Every 6 hours\nDB-only snapshot\nKeep 7 days"]
    end

    subgraph INTEGRITY["Daily at 03:00"]
        H["log:verify-integrity\nAudit log chain check"]
    end
            </div>
        </div>
    </div>
</div>

{{-- ── Job Details ── --}}
<div class="kb-section card" id="details">
    <div class="card-body">
        <h4><i class="bi bi-list-check me-2"></i>Job Details</h4>
        <table class="table table-sm relation-table">
            <thead><tr><th>Command</th><th>Schedule</th><th>What It Does</th></tr></thead>
            <tbody>
                <tr>
                    <td><code>employees:activate</code></td>
                    <td>Every minute</td>
                    <td>
                        <strong>Morning:</strong> Finds onboardings with <code>start_date = today</code>, creates Employee record, calls <code>populateFromOnboarding()</code>, sends WelcomeNewHire email.<br>
                        <strong>23:59:</strong> Finds employees with <code>exit_date = today</code>, sets <code>is_active = false</code>, <code>active_until = exit_date</code>.
                    </td>
                </tr>
                <tr>
                    <td><code>offboarding:notify</code></td>
                    <td>Every minute</td>
                    <td>
                        Checks all pending offboardings against exit_date:<br>
                        • 30 days: OffboardingNoticeMail + calendar<br>
                        • 7 days: OffboardingWeekReminderMail<br>
                        • 3 days: OffboardingReminderMail<br>
                        • Post-exit + assets cleared: OffboardingSendoffMail
                    </td>
                </tr>
                <tr>
                    <td><code>security:audit-report</code></td>
                    <td>Hourly</td>
                    <td>Generates security audit summary from SecurityAuditLog. Detects anomalies.</td>
                </tr>
                <tr>
                    <td><code>leave:remind-managers</code></td>
                    <td>Daily 09:00</td>
                    <td>Finds pending leave applications older than threshold, reminds HR/managers.</td>
                </tr>
                <tr>
                    <td><code>claims:remind</code></td>
                    <td>Daily 09:00</td>
                    <td>Within <code>reminder_days_before</code> (default 3) of <code>submission_deadline_day</code> (default 20th), emails employees with un-submitted draft claims that have items.</td>
                </tr>
                <tr>
                    <td><code>backup:run</code></td>
                    <td>Daily 02:00 + 6-hourly</td>
                    <td>Full encrypted backup (30 day retention) at 02:00. DB-only snapshot every 6 hours (7 day retention).</td>
                </tr>
                <tr>
                    <td><code>log:verify-integrity</code></td>
                    <td>Daily 03:00</td>
                    <td>Validates audit log chain integrity using hash verification. Alerts on tampering.</td>
                </tr>
                <tr>
                    <td><code>sweep:pending-weekly</code></td>
                    <td>Wednesday 00:00</td>
                    <td>
                        Weekly sweep of all pending acknowledgements and approvals. Sends <code>WeeklyPendingSweepMail</code> reminders to the responsible party:<br>
                        • Employee profile consents → employee<br>
                        • AARF forms (employee) → employee<br>
                        • AARF forms (IT) → IT managers<br>
                        • Pending leave → reporting manager<br>
                        • Expense claims (submitted) → assigned manager<br>
                        • Expense claims (manager_approved) → HR managers
                    </td>
                </tr>
                <tr>
                    <td><code>system:refresh-metadata</code></td>
                    <td>Hourly</td>
                    <td>Refreshes cached system metadata for System Overview and Knowledge Base pages (1-hour TTL).</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ── Activation Logic ── --}}
<div class="kb-section card" id="activation">
    <div class="card-body">
        <h4><i class="bi bi-arrow-left-right me-2"></i>employees:activate — Dual Purpose</h4>
        <div class="diagram-container">
            <div class="mermaid">
flowchart LR
    subgraph MORNING["On start_date"]
        A["Find pending onboardings\nwith start_date = today"] --> B["Create Employee record"]
        B --> C["populateFromOnboarding\nFlush staging JSON"]
        C --> D["Send WelcomeNewHire email"]
    end

    subgraph EVENING["At 23:59 on exit_date"]
        E["Find employees\nwith exit_date = today"] --> F["User.is_active = false\ndeactivation_reason = exit_date"]
        F --> G["active_until = exit_date\nRemoved from listings"]
    end
            </div>
        </div>
    </div>
</div>
@endsection
