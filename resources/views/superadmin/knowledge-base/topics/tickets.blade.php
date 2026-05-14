@extends('superadmin.knowledge-base.topic-layout')

@section('topic-content')
{{-- ── Pipeline ── --}}
<div class="kb-section card" id="pipeline">
    <div class="card-body">
        <h4><i class="bi bi-signpost-split me-2"></i>Ticket Lifecycle</h4>
        <div class="diagram-container">
            <div class="mermaid">
flowchart TD
    A["Employee Raises Ticket\n/tickets/create\nticket_number = TIC-YYYY-NNNN"] --> B{"Subject Recognised?"}
    B -->|"Standardised subject"| C["Department resolved\nfrom subject→dept map"]
    B -->|"'Other' + keywords match"| D["Department inferred\nfrom SUBJECT_KEYWORD_HINTS"]
    B -->|"'Other' + no keyword match"| E["Manual dept picker shown\nuser picks department"]

    C --> F["Status: Open\nManagers notified via\nemail + bell"]
    D --> F
    E --> F

    F --> G{"PIC Assignment\n(by Manager / Admin)"}
    G -->|"Assigned"| H["Status: In Progress\nassigned_at stamped\nPIC notified"]
    G -->|"Idle 24h+ no PIC"| I["Status: Pending\n(auto by tickets:remind-stale)"]
    I --> G

    H --> J{"Resolution"}
    J -->|"Marked Resolved"| K["Status: Resolved (terminal)\nresolved_at stamped\nCreator notified"]
    J -->|"Manually closed"| L["Status: Closed (terminal)\nNo resolution credit"]

    K -.->|"Re-opened"| H
    L -.->|"Re-opened"| H

    M["Misfiled?\nManager edits department\nvia /tickets/.../edit-admin"] -.-> F
    M -.-> N["TicketEditLog recorded\nold PIC cleared\nNew dept managers notified"]

    style A fill:#e2e3e5,stroke:#6c757d,color:#000
    style F fill:#cfe2ff,stroke:#0d6efd,color:#000
    style H fill:#fef3cd,stroke:#ffc107,color:#000
    style I fill:#e7f5ff,stroke:#0ea5e9,color:#000
    style K fill:#d1e7dd,stroke:#198754,color:#000
    style L fill:#e2e3e5,stroke:#6c757d,color:#000
            </div>
        </div>
    </div>
</div>

{{-- ── Status Lifecycle ── --}}
<div class="kb-section card" id="status">
    <div class="card-body">
        <h4><i class="bi bi-tag me-2"></i>Status Lifecycle (5 States)</h4>
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <span class="status-badge" style="background:#e2e3e5;color:#41464b;">Open</span><span>&rarr;</span>
            <span class="status-badge" style="background:#fef3cd;color:#664d03;">In Progress</span><span>&rarr;</span>
            <span class="status-badge" style="background:#e7f5ff;color:#0c4a6e;">Pending</span><span>&rarr;</span>
            <span class="status-badge" style="background:#d1e7dd;color:#0f5132;">Resolved</span>
            <span class="text-muted small">or</span>
            <span class="status-badge" style="background:#e2e3e5;color:#41464b;">Closed</span>
        </div>
        <table class="table table-sm small mb-0">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>How it's entered</th>
                    <th>Visible in</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Open</strong></td>
                    <td>Default on creation; cleared by manager when PIC is removed.</td>
                    <td>Active tab</td>
                </tr>
                <tr>
                    <td><strong>In Progress</strong></td>
                    <td>Auto-set the moment a PIC is assigned. Manual selection requires an existing PIC.</td>
                    <td>Active tab, Assigned-to-Me tab</td>
                </tr>
                <tr>
                    <td><strong>Pending</strong></td>
                    <td>Auto-set by <code>tickets:remind-stale</code> cron after 24h+ with no PIC and no activity.</td>
                    <td>Active tab</td>
                </tr>
                <tr>
                    <td><strong>Resolved</strong></td>
                    <td>Terminal. PIC or manager marks resolved; <code>resolved_at</code> stamped; creator emailed + bell-notified.</td>
                    <td>Archived tab</td>
                </tr>
                <tr>
                    <td><strong>Closed</strong></td>
                    <td>Terminal. Manually closed without resolution credit (e.g. duplicate, cancelled).</td>
                    <td>Archived tab</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ── Routing & PIC pool ── --}}
<div class="kb-section card" id="routing">
    <div class="card-body">
        <h4><i class="bi bi-diagram-2 me-2"></i>Routing &amp; PIC Eligibility</h4>
        <p class="text-muted small">
            Tickets are <strong>tenant-scoped</strong> via <code>TenantScope</code> + Postgres RLS. Within a tenant, every department's team handles every ticket in its area — Claritas's multi-company cluster routing was dropped (see <code>docs/PLAN-CLARITAS-REPLICATION.md §0</code>).
        </p>

        <h6 class="mt-3">Department types</h6>
        <table class="table table-sm small">
            <thead><tr><th>Type</th><th>Manager check</th><th>Departments</th></tr></thead>
            <tbody>
                <tr>
                    <td>App-role gated</td>
                    <td><code>users.role &isin; DEPARTMENT_MANAGER_ROLES[dept]</code></td>
                    <td>HRA, Group IT, Finance, Admin</td>
                </tr>
                <tr>
                    <td>Work-role gated</td>
                    <td><code>employees.work_role = 'manager' AND employees.department = dept</code></td>
                    <td>Community, Consulting, Content, Design, Digital, Ecommerce, KOL, Management, Marketing, Media, Production, Projects, Sales, Tech</td>
                </tr>
            </tbody>
        </table>

        <h6 class="mt-3">PIC pool (who can be assigned)</h6>
        <ul class="small mb-2">
            <li><strong>App-role gated:</strong> managers + executives + interns (per <code>DEPARTMENT_PIC_EXTRA_ROLES</code> — HRA includes <code>hr_intern</code>; Group IT includes <code>it_intern</code>).</li>
            <li><strong>Work-role gated:</strong> any employee with <code>work_role = 'manager'</code> in the matching department.</li>
            <li><strong>Always eligible (catch-all):</strong> <code>superadmin</code> and <code>system_admin</code>.</li>
        </ul>

        <h6 class="mt-3">Visibility (<code>Ticket::scopeVisibleTo</code>)</h6>
        <ul class="small mb-0">
            <li><strong>Superadmin / system_admin:</strong> every ticket in the tenant.</li>
            <li><strong>Department managers:</strong> tickets in their managed department(s) OR assigned to them as PIC.</li>
            <li><strong>Other staff (incl. interns):</strong> only tickets <em>assigned</em> to them.</li>
            <li><strong>Creator's own tickets:</strong> shown via the Self-Service <code>/tickets</code> page, not the management list.</li>
        </ul>
    </div>
</div>

{{-- ── Subject inference ── --}}
<div class="kb-section card" id="subjects">
    <div class="card-body">
        <h4><i class="bi bi-search me-2"></i>Subject &rarr; Department Resolution</h4>
        <p class="text-muted small">
            17 departments, each with a controlled-vocabulary subject list (<code>Ticket::DEPARTMENT_SUBJECTS</code>). Standardised subjects keep analytics aggregations clean — no more "Incorrect details" vs "Incorrect information" duplicates.
        </p>
        <ol class="small mb-2">
            <li>User picks a standardised subject from the optgroup (TomSelect, searchable).</li>
            <li><code>Ticket::subjectToDepartmentMap()</code> validates the (subject, department) pair server-side.</li>
            <li>Multi-department subjects (e.g. "Brand Asset Request" &rarr; Design or Marketing): the chosen optgroup disambiguates client-side.</li>
            <li>"Other" subjects route via <code>Ticket::inferDepartmentFromText()</code> — first-match keyword inference (Digital comes first so "Facebook password" routes to Digital not Group IT).</li>
            <li>If keyword inference fails, a manual dept picker is shown.</li>
        </ol>
    </div>
</div>

{{-- ── Notifications & email map ── --}}
<div class="kb-section card" id="notifications">
    <div class="card-body">
        <h4><i class="bi bi-bell me-2"></i>Notifications &amp; Email Map</h4>
        <table class="table table-sm small mb-0">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Mailable</th>
                    <th>Notification (bell)</th>
                    <th>Recipients</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Ticket created</td>
                    <td><code>TicketCreatedMail</code></td>
                    <td><code>TicketRaisedNotification</code></td>
                    <td>Department managers (no interns)</td>
                </tr>
                <tr>
                    <td>PIC assigned</td>
                    <td><code>TicketAssignedMail</code></td>
                    <td><code>TicketAssignedNotification</code></td>
                    <td>Newly-assigned PIC</td>
                </tr>
                <tr>
                    <td>PIC removed</td>
                    <td>&mdash;</td>
                    <td><code>TicketUnassignedNotification</code></td>
                    <td>Previously-assigned PIC</td>
                </tr>
                <tr>
                    <td>Chat message posted</td>
                    <td><code>TicketNewMessageMail</code></td>
                    <td><code>NewTicketMessageNotification</code></td>
                    <td>Raiser + PIC (excluding sender). If raiser replies on an un-PIC'd ticket, dept managers added (manager-loop fallback).</td>
                </tr>
                <tr>
                    <td>Resolved</td>
                    <td><code>TicketResolvedMail</code></td>
                    <td><code>TicketResolvedNotification</code></td>
                    <td>Creator</td>
                </tr>
                <tr>
                    <td>Idle 24h+ (stale)</td>
                    <td><code>TicketReminderMail</code></td>
                    <td><code>TicketReminderNotification</code></td>
                    <td>PIC if assigned, else dept managers. Throttled to one per 24h via <code>last_reminder_sent_at</code>.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ── Attachments & malware ── --}}
<div class="kb-section card" id="attachments">
    <div class="card-body">
        <h4><i class="bi bi-shield-check me-2"></i>Attachments &amp; Malware Scanning</h4>
        <ul class="small mb-2">
            <li><strong>Max:</strong> 10 files per upload, 10 MB per file, MIME-restricted to <code>pdf, jpg, jpeg, png, gif, webp</code>.</li>
            <li><strong>Storage:</strong> Private disk under <code>storage/app/private/ticket_attachments/</code>. Served via <code>SecureFileController</code> with per-ticket access checks.</li>
            <li><strong>Image processing:</strong> <code>AttachmentProcessor</code> uses GD to resize images &gt; 1920px wide and re-encode (strips EXIF as a side effect). JPEG quality 80.</li>
            <li><strong>Malware scan:</strong> <code>ScanUploadsForMalware</code> middleware (alias <code>scan-uploads</code>) runs before the controller, two layers:
                <ul>
                    <li><strong>Heuristic (always):</strong> head/tail sample of file bytes; regex against EICAR, embedded PHP/ASP/JSP, webshell function chains, Office VBA autoexec + Shell call. Conservative patterns to minimise false positives on legitimate documents.</li>
                    <li><strong>ClamAV (optional):</strong> if <code>CLAMAV_HOST</code> env is set, full INSTREAM TCP scan over port 3310. Network failures degrade gracefully to heuristic-only (logged at warning level).</li>
                </ul>
            </li>
            <li><strong>Audit:</strong> blocked uploads logged to <code>security_audit_logs</code> via <code>SecurityAuditLog::record('malware_blocked', ...)</code>.</li>
            <li><strong>Two attachment tables:</strong>
                <ul>
                    <li><code>ticket_attachments</code> &mdash; files at ticket creation</li>
                    <li><code>ticket_message_attachments</code> &mdash; files per chat message (multi-file)</li>
                </ul>
                Plus legacy single-attachment columns on <code>ticket_messages</code> for backward-compat — kept but unused by new messages.
            </li>
        </ul>
    </div>
</div>

{{-- ── Analytics ── --}}
<div class="kb-section card" id="analytics">
    <div class="card-body">
        <h4><i class="bi bi-graph-up me-2"></i>Analytics (Three Cards on /tickets/manage)</h4>
        <ul class="small mb-2">
            <li><strong>Card 1 &mdash; Active by Priority:</strong> count of active tickets grouped by Low/Medium/High/Urgent. Superadmin sees tenant-wide; managers see scoped to their managed dept(s).</li>
            <li><strong>Card 2 &mdash; PIC Times:</strong> per-PIC avg resolution time, only for tickets in status <code>Resolved</code>. <strong>Measured from <code>assigned_at</code>, not <code>created_at</code></strong> — a ticket sitting unassigned for days isn't the PIC's fault.</li>
            <li><strong>Card 3 &mdash; Department Health:</strong> per-dept avg resolution time, colour-tiered:
                <ul>
                    <li><span class="status-badge" style="background:#d1e7dd;color:#0f5132;">good</span> &le; 24 h (<code>HEALTH_GOOD_MAX_MINUTES = 1440</code>)</li>
                    <li><span class="status-badge" style="background:#fef3cd;color:#664d03;">amber</span> &le; 72 h (<code>HEALTH_AMBER_MAX_MINUTES = 4320</code>)</li>
                    <li><span class="status-badge" style="background:#f8d7da;color:#842029;">poor</span> &gt; 72 h</li>
                    <li><span class="status-badge" style="background:#e2e3e5;color:#41464b;">nodata</span> no resolved tickets yet</li>
                </ul>
            </li>
        </ul>
        <p class="text-muted small mb-0">
            Postgres-native SQL: <code>EXTRACT(EPOCH FROM (resolved_at - COALESCE(assigned_at, created_at))) / 60</code> for minute-difference; <code>CASE status WHEN ... END</code> for status sort order.
        </p>
    </div>
</div>

{{-- ── Scheduled jobs ── --}}
<div class="kb-section card" id="cron">
    <div class="card-body">
        <h4><i class="bi bi-clock-history me-2"></i>Scheduled Commands</h4>
        <table class="table table-sm small mb-0">
            <thead><tr><th>Command</th><th>Frequency</th><th>Purpose</th></tr></thead>
            <tbody>
                <tr>
                    <td><code>tickets:remind-stale</code></td>
                    <td>hourly, <code>withoutOverlapping</code></td>
                    <td>Find tickets with no activity for 24h+. Auto-transition Open &rarr; Pending for un-PIC'd. Email + bell-notify PIC or dept managers. Throttled per ticket via <code>last_reminder_sent_at</code>. Iterates every active tenant via <code>TenantContext::forEach</code>. Logs to <code>storage/logs/ticket-reminders.log</code>.</td>
                </tr>
                <tr>
                    <td><code>birthdays:send-wishes</code></td>
                    <td>everyMinute (Asia/Kuala_Lumpur)</td>
                    <td>Animated themed e-card to active employees whose DOB = today. Idempotent via <code>employees.birthday_email_sent_year</code>. Feb 29 babies receive on Feb 28 in non-leap years. 6 deterministic themes per <code>employee.id</code>. Logs to <code>storage/logs/birthday-wishes.log</code>.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ── Routes ── --}}
<div class="kb-section card" id="routes">
    <div class="card-body">
        <h4><i class="bi bi-signpost me-2"></i>Routes</h4>
        <p class="text-muted small">All under the standard <code>auth</code> + <code>EnforceSingleSession</code> + <code>SecurityAudit</code> + <code>EnforceTwoFactor</code> stack. <strong>No plan gate</strong> — ticketing is universal per the §0 decision.</p>
        <table class="table table-sm small mb-0">
            <thead><tr><th>Method</th><th>Path</th><th>Name</th></tr></thead>
            <tbody>
                <tr><td>GET</td><td><code>/tickets</code></td><td><code>tickets.index</code></td></tr>
                <tr><td>GET</td><td><code>/tickets/create</code></td><td><code>tickets.create</code></td></tr>
                <tr><td>POST</td><td><code>/tickets</code> <span class="badge bg-info-subtle text-info-emphasis">scan-uploads</span></td><td><code>tickets.store</code></td></tr>
                <tr><td>GET</td><td><code>/tickets/manage</code></td><td><code>tickets.manage</code></td></tr>
                <tr><td>GET</td><td><code>/tickets/{ticket}</code></td><td><code>tickets.show</code></td></tr>
                <tr><td>GET</td><td><code>/tickets/{ticket}/edit-admin</code></td><td><code>tickets.edit-admin</code></td></tr>
                <tr><td>PUT</td><td><code>/tickets/{ticket}/admin</code></td><td><code>tickets.update-admin</code></td></tr>
                <tr><td>POST</td><td><code>/tickets/{ticket}/assign-pic</code></td><td><code>tickets.assign-pic</code></td></tr>
                <tr><td>POST</td><td><code>/tickets/{ticket}/status</code></td><td><code>tickets.status</code></td></tr>
                <tr><td>GET</td><td><code>/tickets/{ticket}/messages</code></td><td><code>tickets.messages.index</code></td></tr>
                <tr><td>POST</td><td><code>/tickets/{ticket}/messages</code> <span class="badge bg-info-subtle text-info-emphasis">scan-uploads</span></td><td><code>tickets.messages.store</code></td></tr>
                <tr><td>GET</td><td><code>/notifications</code></td><td><code>notifications.index</code></td></tr>
                <tr><td>POST</td><td><code>/notifications/{id}/read</code></td><td><code>notifications.read</code></td></tr>
                <tr><td>POST</td><td><code>/notifications/mark-all-read</code></td><td><code>notifications.mark-all-read</code></td></tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ── Data model ── --}}
<div class="kb-section card" id="data-model">
    <div class="card-body">
        <h4><i class="bi bi-database me-2"></i>Tables</h4>
        <p class="text-muted small">All Postgres-only, all tenant-scoped via <code>tenant_id</code> + <code>FORCE ROW LEVEL SECURITY</code> with policy <code>USING (tenant_id = eiaaw_current_tenant_id())</code>.</p>
        <ul class="small mb-0">
            <li><code>tickets</code> — core record. <code>ticket_number</code> unique per <code>(tenant_id, ticket_number)</code> for TIC-YYYY-NNNN sequencing.</li>
            <li><code>ticket_messages</code> — chat thread (one row per message). Legacy single-attachment columns retained for backward-compat.</li>
            <li><code>ticket_attachments</code> — files attached at ticket creation.</li>
            <li><code>ticket_message_attachments</code> — multi-file attachments per chat message.</li>
            <li><code>ticket_edit_logs</code> — audit trail for the Edit Department action (visible to <code>superadmin</code> / <code>system_admin</code> only).</li>
            <li><code>notifications</code> — Laravel's standard <code>DatabaseNotification</code> table, tenant-scoped + RLS.</li>
            <li><code>employees.birthday_email_sent_year</code> — column added by Phase A migration; idempotency tag for the everyMinute birthday cron.</li>
        </ul>
    </div>
</div>
@endsection
