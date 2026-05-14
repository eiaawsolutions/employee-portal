# PLAN — Claritas Onboarding → EIAAW Workforce Feature Port

**Status:** Plan only, no code shipped.
**Author:** Claude (Opus 4.7 + 1M ctx) for Amos.
**Date:** 2026-05-14.
**Goal:** Replicate Claritas's last batch of features (Ticketing module, Birthday-card sending, Auth/login hardening) into the EIAAW Workforce multi-tenant SaaS — adapted to per-tenant architecture, dropping multi-company-cluster routing.
**Source range:** Claritas commits `308e0d2..ae438e7` (everything between the duplicate-prevention commit and the auto-detect ticket form). ~11,400 LOC across 86 files.

---

## 0. Decisions locked before writing the plan

| Decision | Value | Rationale |
|---|---|---|
| Features to port | Ticketing + Birthday-card + Auth/login hardening | User selected "all three, gated correctly" |
| Ticketing plan-gate | **Universal — no plan gate.** Feature key reserved as `core.tickets` for future down-tiering. | User selected "universal" |
| Commit shape | **Single bundled commit on `main`** once all three are implemented. | User selected "single bundled commit" |
| Multi-company routing | **Dropped.** Claritas's `service_company_id` / `source_company_id` / `department_company_access` is for cross-sub-company servicing inside one Laravel install. EIAAW tenants don't share teams across companies. | User selected "simplify the port — drop multi-company routing" |
| Session cut | **Plan only.** Code in a follow-up session. | User selected "stop, write a detailed implementation plan, you do it later" |

What the dropped multi-company routing means concretely:

- No `service_company_id` / `source_company_id` columns on `tickets` or `department_company_access`.
- No `DepartmentCompanyAccess` model or pivot table at all.
- No `/superadmin/department-settings` route, controller, or view.
- No `Ticket::serviceOptionsForRaiser()`, `resolveServiceCompanyId()`, `sourceCompanyIdsForDepartment()`, `companyNameVariants()`, `resolveCompanyId()`, `normaliseCompanyName()`, `companiesServingDepartment()`, `companyNamesServingDepartment()`, `defaultServedCompanyIdsForDepartment()`, `departmentsForCompany()`, `departmentServesCompany()`, `eligibleManagersQuery()` (the cluster variant), `picPoolForDeptAndCompany()`, `unregisteredManagersForNotification()`.
- PIC eligibility = "all department managers in the tenant," nothing further to scope.
- Visibility = `tenant_id` (from `BelongsToTenant`) + department membership. RLS handles the rest.
- Drops ~1,500 lines from `Ticket.php` (1297 → roughly 700-800 lines).

---

## 1. Architectural deltas to expect (Claritas → EIAAW)

| Surface | Claritas | EIAAW Workforce |
|---|---|---|
| DB engine | MySQL | Postgres (every new migration must guard `if (DB::connection()->getDriverName() !== 'pgsql') return;`) |
| Tenancy | Single tenant | Multi-tenant. Every new table needs `tenant_id` + FK to `tenants` + RLS policy via `eiaaw_current_tenant_id()` |
| Eloquent base | No global scope | `BelongsToTenant` trait → `TenantScope` global scope + auto-fill `tenant_id` on create |
| Time-difference SQL | `TIMESTAMPDIFF(MINUTE, a, b)` | `EXTRACT(EPOCH FROM (b - a)) / 60` |
| Sort by static string list | `ORDER BY FIELD(status, 'Open', 'In Progress', ...)` | `ORDER BY CASE status WHEN 'Open' THEN 1 WHEN 'In Progress' THEN 2 ... END` |
| Boolean true literal | `1`, `0` | `true`, `false` (Eloquent handles in most cases — watch raw SQL) |
| Companies | `companies` table is a list of registered sub-entities under the same install (KOL, Enlinea, etc.) | `companies` is **per-tenant** sub-entities (already tenant-scoped). Use it as-is for the ticket `company_id` field — same model, different scope. |
| User roles | Same enum (`hr_manager`, `it_manager`, `superadmin`, `system_admin`, `finance_manager`, `hr_intern`, `it_intern`, etc.) | **Same enum.** EIAAW has `isHrManager()`, `isIt()`, `isSuperadmin()`, `isSystemAdmin()`, `requiresTwoFactor()` already. No translation needed. |
| Single-session | `EnforceSingleSession` middleware + `users.session_token` | **Already present.** Same column, same middleware. |
| 2FA | Confirmed in `AuthController::login()` | **Already present.** EIAAW has 2FA *enforcement* via `requiresTwoFactor()` (Claritas didn't). |
| Password reset | `WorkEmailUserProvider` resolves to `work_email` | **Same** — `getEmailForPasswordReset()` already on User. |
| CSP nonce | Inline `$cspNonce` variable from a service provider | **Same** — EIAAW already uses CSP nonces, every Blade `<script>` needs `nonce="{{ $cspNonce ?? '' }}"`. |
| File storage | `storage/app/private` served via `SecureFileController` | **Same** — EIAAW has `SecureFileController` with `DIRECTORY_PERMISSIONS` map. **Will need an addition for `ticket_attachments/`**. |
| Custom validators | `valid_file_content`, `sanitize_image` (registered in AppServiceProvider) | **Same** — already in EIAAW. |
| Notifications table | Created in Claritas migration 2026_04_28_000004 | **Need to create on EIAAW** if not already present — Laravel built-in shape. Check before writing migration. |

---

## 2. File-level plan

Files are listed in implementation order. Each entry says:
- **Path** (relative to `c:\laragon\www\EIAAW-employee portal\`)
- **Source** (the Claritas file to start from, or "new")
- **Adaptation notes** (what to change vs Claritas)
- **Estimated effort** (LOC + complexity)

### Phase A — Schema (migrations, Postgres-only)

Numbering plan: keep the YYYY_MM_DD_NNNNNN format; put all in one date stamp `2026_05_14_000NNN_*` so they apply as one logical batch.

**A1. `database/migrations/2026_05_14_000001_create_notifications_table.php`** — *NEW (Laravel-standard)*
- Standard Laravel `notifications` table: `uuid id`, `string type`, `morphs notifiable`, `text data`, `timestamp read_at`, `timestamps`.
- Postgres-only guard.
- Add `tenant_id` + RLS policy using the `TenancyMigration` base class pattern (parent_via = null, no backfill — it's a new table).
- Check first: `Schema::hasTable('notifications')` — Laravel's `php artisan notifications:table` may have already created it in earlier work; skip if so.

**A2. `database/migrations/2026_05_14_000002_create_tickets_table.php`** — *adapted from Claritas migrations 1+3+5+7+8 (consolidated)*

Single migration combining: `create_tickets_table`, `expand_ticket_departments` (varchar 50 not enum), `add_last_reminder_to_tickets`, `redesign_ticket_status_lifecycle` (final 5-status enum), `add_assigned_at_to_tickets`. Skip the intermediate enum-narrow steps — we're starting fresh, no historical data to migrate.

Columns:
- `id` PK
- `tenant_id` BIGINT NOT NULL → FK tenants(id) ON DELETE CASCADE — indexed
- `ticket_number` VARCHAR(20) UNIQUE — format `TIC-YYYY-0001`
- `user_id` BIGINT NOT NULL → FK users(id) ON DELETE CASCADE (creator)
- `company_id` BIGINT NULL → FK companies(id) ON DELETE SET NULL (raiser company)
- `assigned_to` BIGINT NULL → FK users(id) ON DELETE SET NULL (PIC)
- `assigned_at` TIMESTAMP NULL — indexed
- `department` VARCHAR(50) NOT NULL — *no enum*, just a varchar constrained at app level
- `priority` ENUM('Low','Medium','High','Urgent') DEFAULT 'Medium'
- `status` ENUM('Open','In Progress','Pending','Resolved','Closed') DEFAULT 'Open' — *Postgres CHECK constraint, not native ENUM type*
- `subject` VARCHAR(255) NOT NULL
- `description` TEXT NOT NULL
- `resolved_at` TIMESTAMP NULL
- `last_reminder_sent_at` TIMESTAMP NULL
- timestamps

Indexes:
- `(tenant_id, status)` composite
- `(tenant_id, department, status)` composite
- `(tenant_id, user_id)` composite
- `assigned_to`
- `assigned_at`

RLS policy: `tenant_id = eiaaw_current_tenant_id()` (USING + WITH CHECK), with `FORCE ROW LEVEL SECURITY`.

**A3. `database/migrations/2026_05_14_000003_create_ticket_messages_table.php`** — *adapted from Claritas migration 2*

Columns: `id`, `tenant_id`, `ticket_id` (FK), `user_id` (FK), `message TEXT NULL`, `attachment_path`, `attachment_original_name`, `attachment_mime VARCHAR(120)`, timestamps. Indexed `(tenant_id, ticket_id, id)`. RLS policy.

The legacy single-attachment columns are kept for backward compat in Claritas; on EIAAW we COULD drop them and only use the per-message attachment table, but keeping parity makes view code copy-paste cleaner. **Decision: keep them.**

**A4. `database/migrations/2026_05_14_000004_create_ticket_attachments_table.php`** — *adapted from Claritas migration*

Columns: `id`, `tenant_id`, `ticket_id` (FK cascade), `file_path`, `original_name`, `mime VARCHAR(120)`, `size BIGINT`, `is_image BOOLEAN DEFAULT false`, timestamps. RLS policy.

**A5. `database/migrations/2026_05_14_000005_create_ticket_message_attachments_table.php`** — *adapted from Claritas migration*

Columns: `id`, `tenant_id`, `message_id` → FK `ticket_messages(id)` cascade, `file_path`, `original_name`, `mime VARCHAR(120)`, `size BIGINT`, `is_image BOOLEAN DEFAULT false`, timestamps. RLS policy.

**A6. `database/migrations/2026_05_14_000006_create_ticket_edit_logs_table.php`** — *adapted from Claritas migration*

Columns: `id`, `tenant_id`, `ticket_id` (FK cascade), `edited_by_user_id` (FK cascade), `changes JSONB`, `note TEXT NULL`, timestamps. Indexed `(tenant_id, ticket_id, created_at)`. RLS policy.

**A7. `database/migrations/2026_05_14_000007_add_birthday_email_sent_year_to_employees.php`** — *adapted from Claritas migration*

`ALTER TABLE employees ADD COLUMN birthday_email_sent_year SMALLINT NULL` (after `date_of_birth`). Idempotent guard with `Schema::hasColumn`.

Notes on what's NOT in this batch:
- No `department_company_access` table.
- No `service_company_id` / `source_company_id` columns.
- No `add_company_id_to_tickets` migration — it's already in A2 from the start.

### Phase B — Models (8 files)

**B1. `app/Models/Ticket.php`** — *adapted from Claritas (~700-800 LOC, down from 1297)*

Keep:
- Constants: `DEPARTMENTS`, `PRIORITIES`, `STATUSES`, `ARCHIVED_STATUSES`, `ACTIVE_STATUSES`, `HEALTH_GOOD_MAX_MINUTES`, `HEALTH_AMBER_MAX_MINUTES`, `DEPARTMENT_MANAGER_ROLES`, `WORK_ROLE_MANAGER_DEPARTMENTS`, `DEPARTMENT_SUBJECTS` (all 17 departments), `SUBJECT_KEYWORD_HINTS`, `DEPARTMENT_PIC_EXTRA_ROLES`.
- Static helpers: `subjectToDepartmentMap()`, `inferDepartmentFromText()`, `generateTicketNumber()`, `formatMinutes()`, `healthTier()`.
- Relationships: `creator()`, `assignee()`, `messages()`, `attachments()`, `editLogs()`, `company()`.
- Scopes: `scopeForDepartment()`, plus a simplified `scopeVisibleTo()`.
- Boot hook for auto-generating `ticket_number`.
- Instance helpers: `statusColor()`, `priorityColor()`, `isArchivedStatus()`, `timeToResolve()`.

**Drop entirely** (multi-company routing):
- `serviceCompany()` relationship.
- `serviceOptionsForRaiser()`, `resolveServiceCompanyId()`, `sourceCompanyIdsForDepartment()`, `companyNameVariants()`, `resolveCompanyId()`, `normaliseCompanyName()`, `companiesServingDepartment()`, `companyNamesServingDepartment()`, `defaultServedCompanyIdsForDepartment()`, `defaultServedCompanyIdsForDepartmentPublic()`, `departmentsForCompany()`, `departmentServesCompany()`, `unregisteredManagersForNotification()`.
- `filterToDeptsServingUserCompany()`, `orWhereInManagedDeptsServingCreator()`.

**Replace** (simplified for per-tenant):

```php
// Simplified visibleTo — strictly tenant + role-based, no cross-company resolution.
public function scopeVisibleTo(Builder $query, User $user): Builder
{
    // TenantScope already constrains to current tenant. RLS belt-and-braces.
    if ($user->isSuperadmin() || $user->isSystemAdmin()) {
        return $query;
    }

    $managedDepartments = self::departmentsManagedBy($user);

    if (empty($managedDepartments)) {
        return $query->where('assigned_to', $user->id);
    }

    return $query->where(function ($q) use ($managedDepartments, $user) {
        $q->whereIn('department', $managedDepartments)
          ->orWhere('assigned_to', $user->id);
    });
}

// Department manager check — role/work_role only, no cluster.
public static function isManagerOf(User $user, string $department): bool
{
    if (in_array($department, self::WORK_ROLE_MANAGER_DEPARTMENTS, true)) {
        $emp = $user->employee;
        return $emp && $emp->work_role === 'manager' && $emp->department === $department;
    }
    $deptRoles = self::DEPARTMENT_MANAGER_ROLES[$department] ?? [];
    return in_array($user->role, $deptRoles, true);
}

// Eligible PIC pool — all users in the tenant with the right role/work_role.
// No company filter (already tenant-scoped via TenantScope on the User query).
public function eligiblePicQuery()
{
    return self::picPoolForDepartment($this->department, includePicExtras: true);
}

public function managersForNotification()
{
    return self::picPoolForDepartment($this->department, includePicExtras: false);
}

public static function picPoolForDepartment(string $department, bool $includePicExtras = false)
{
    $query = User::where('is_active', true);

    if (in_array($department, self::WORK_ROLE_MANAGER_DEPARTMENTS, true)) {
        $query->where(function ($outer) use ($department) {
            $outer->whereHas('employee', function ($q) use ($department) {
                $q->where('work_role', 'manager')->where('department', $department);
            })->orWhereIn('role', ['superadmin', 'system_admin']);
        });
        return $query;
    }

    $managerRoles = self::DEPARTMENT_MANAGER_ROLES[$department] ?? [];
    $extraRoles   = $includePicExtras
        ? (self::DEPARTMENT_PIC_EXTRA_ROLES[$department] ?? [])
        : [];
    $deptRoles = array_values(array_unique(array_merge($managerRoles, $extraRoles)));

    return $query->where(function ($q) use ($deptRoles) {
        $q->whereIn('role', $deptRoles)
          ->orWhereIn('role', ['superadmin', 'system_admin']);
    });
}

public static function departmentsManagedBy(User $user): array
{
    $managed = [];
    foreach (self::DEPARTMENTS as $dept) {
        if (self::isManagerOf($user, $dept)) {
            $managed[] = $dept;
        }
    }
    return $managed;
}
```

Add `use BelongsToTenant;` and include `tenant_id` in `$fillable`. Fillable list (final): `tenant_id`, `ticket_number`, `user_id`, `company_id`, `assigned_to`, `assigned_at`, `department`, `priority`, `status`, `subject`, `description`, `resolved_at`, `last_reminder_sent_at`.

**B2. `app/Models/TicketMessage.php`** — *direct port + BelongsToTenant + `tenant_id` in fillable*
**B3. `app/Models/TicketAttachment.php`** — same
**B4. `app/Models/TicketMessageAttachment.php`** — same
**B5. `app/Models/TicketEditLog.php`** — same

For all four: read each Claritas file, add `use BelongsToTenant;`, add `tenant_id` at the head of `$fillable`. Otherwise unchanged. Each is ~30-60 LOC.

**B6. `app/Models/Employee.php` (modify in place)** — add `birthday_email_sent_year` to `$fillable`.

**B7. `app/Models/User.php` (modify in place)** — add `canAccessTicketManagement()` and `canManageTicketsForDepartment(string $dept): bool` helper methods. The latter wraps `Ticket::isManagerOf($this, $dept)` so views can ask `$user->canManageTicketsForDepartment('HRA')` cleanly. Also add `canViewAllTickets()` returning `$this->isSuperadmin() || $this->isSystemAdmin()`. Plus `canEditTicket(Ticket $t)` mirroring Claritas's `TicketController::authorizeEdit()`.

**B8. (No new Notification model)** — Laravel's built-in `DatabaseNotification` is used directly; the migration creates the table; the controller queries `$user->notifications()`.

### Phase C — Notifications (5 files)

Direct port from Claritas, no adaptation beyond `use App\Models\Ticket;` paths. None of them store tenant context — they're sent to a specific User which is already tenant-scoped.

**C1. `app/Notifications/TicketRaisedNotification.php`**
**C2. `app/Notifications/TicketAssignedNotification.php`**
**C3. `app/Notifications/TicketUnassignedNotification.php`**
**C4. `app/Notifications/TicketResolvedNotification.php`**
**C5. `app/Notifications/TicketReminderNotification.php`**
**C6. `app/Notifications/NewTicketMessageNotification.php`**

Each is ~40-50 LOC. Pattern: `via()` returns `['database']`, `toArray()` returns ticket metadata. Copy verbatim — no tenant scoping needed in payload.

### Phase D — Mailables + templates (5 mail classes + 5 blade templates + 1 birthday)

**D1. `app/Mail/TicketCreatedMail.php`** — *direct port*. Accepts `Ticket` + `User|Employee` recipient.
**D2. `app/Mail/TicketAssignedMail.php`** — *direct port*. Accepts `Ticket` + `User` assignee.
**D3. `app/Mail/TicketResolvedMail.php`** — *direct port*. Accepts `Ticket`.
**D4. `app/Mail/TicketNewMessageMail.php`** — *direct port*. Accepts `Ticket`, `TicketMessage`, sender, recipient.
**D5. `app/Mail/TicketReminderMail.php`** — *direct port*. Accepts `Ticket`, recipient, `lastActivityAt`, `isUnassigned`.
**D6. `app/Mail/BirthdayWishMail.php`** — *adapted port*. Accepts `Employee` + optional theme override. Adaptation: Claritas resolves company logo via `Company::where('name', $emp->company)`; on EIAAW this is tenant-scoped automatically by `BelongsToTenant`. Also wire the EIAAW tenant name into the greeting line instead of hardcoding "Claritas".

Plus 6 blade templates under `resources/views/emails/`:
- `ticket-created.blade.php`
- `ticket-assigned.blade.php`
- `ticket-resolved.blade.php`
- `ticket-new-message.blade.php`
- `ticket-reminder.blade.php`
- `birthday-wish.blade.php` (the 210-line animated card — copy verbatim; only swap brand string)

Each blade is 50-100 LOC for ticket emails, 210 LOC for the birthday card. **Default sender is already configured on EIAAW** — no `MAIL_FROM_ADDRESS` change needed.

### Phase E — Services + middleware (3 files)

**E1. `app/Services/MalwareScanner.php`** — *direct port (188 LOC)*. Pure-PHP heuristic + optional ClamAV. No tenant scoping — stateless. EIAAW `config/services.php` may already have `clamav` config; if not, add `clamav.host` and `clamav.port` from env.

**E2. `app/Services/AttachmentProcessor.php`** — *direct port (136 LOC)*. Pure utility. No tenant scoping — caller passes the directory. Verify GD extension is available (it is on Railway by default).

**E3. `app/Http/Middleware/ScanUploadsForMalware.php`** — *direct port (94 LOC)*. Logs to `SecurityAuditLog` on detection. EIAAW already has `SecurityAuditLog`. **Register in `bootstrap/app.php`** under `$middleware->alias([...])` so route groups can opt in: `'scan-uploads' => \App\Http\Middleware\ScanUploadsForMalware::class`. Apply specifically on POST routes that accept file uploads (ticket store, ticket-message store, ticket edit), not globally.

### Phase F — Controllers (3 files)

**F1. `app/Http/Controllers/TicketController.php`** — *heavily adapted from Claritas (~700-800 LOC, down from 1117)*

Methods to keep, simplified:
- `index(Request)` — Self-service. Group by company → department, three tabs (active/assigned/archived). Drop the COALESCE subselect that fetched `ticket_company_name` — just use `tickets.company_id` directly through the `company()` relation (it's tenant-scoped already). Drop the resolution-time analytics build for non-assigned tabs (Claritas's `buildPicAnalytics` stays for the assigned tab).
- `manage(Request)` — Manager/admin view. Same three tabs. Use the simplified `scopeVisibleTo()`. Keep `buildAnalytics()` (superadmin) and `buildManagerAnalytics()` (manager) — both simplified to remove cross-company logic. `availableCompanies` = `Company::all()` (already tenant-scoped). Card 3 deptStats: seed every (company, dept) instead of "only depts that serve this company" — within a tenant, every dept is conceptually available to every sub-company unless the tenant chooses to restrict (out of scope here).
- `create()` — Form data. Drop `serviceOptions` and `resolvedServiceByDept`. Drop `autoCompanyId` overrides — just pass the user's employee.company as the default company_id picker. Pass `$companies = Company::orderBy('name')->get(['id','name'])` (tenant-scoped).
- `store(Request)` — Drop service_company_id resolution. Keep subject→dept validation, keyword inference fallback for "Other", attachment store via `AttachmentProcessor`. **Apply `ScanUploadsForMalware` middleware on this route** so attachments are scanned before this method runs. After create, notify dept managers via `$ticket->managersForNotification()`. Drop the `unregisteredManagersForNotification` email-only fallback (we don't have the same staging concept).
- `show(Request, Ticket)` — Same as Claritas, with simplified `authorizeView()`. The `?from=manage` flag still gates manager controls.
- `editAdmin(Ticket)` and `updateAdmin(Request, Ticket)` — Same. Drops `companies` from view data (no longer needed for cluster routing; only the department dropdown is editable).
- `assignPic(Request, Ticket)` — Same. Uses simplified `eligiblePicQuery()`.
- `updateStatus(Request, Ticket)` — Same.

Convert Claritas's SQL `TIMESTAMPDIFF(MINUTE, ...)` to Postgres-equivalent `EXTRACT(EPOCH FROM (...)) / 60` in `computeResolutionStats` and `buildPicAnalytics`.

Convert `FIELD(status, ...)` orderings to `CASE status WHEN ... END` style.

**Authorization**: drop `canAccessTicketManagement()` external call shape — define it on `User.php` (in B7) as `return $this->canViewAllTickets() || !empty(Ticket::departmentsManagedBy($this));`.

**F2. `app/Http/Controllers/TicketMessageController.php`** — *direct port + tenant awareness (~230 LOC)*. Methods: `index()` (poll endpoint), `store()` (post message + attachments). `authorizeAccess()` mirrors `TicketController::authorizeView()`. **Apply `ScanUploadsForMalware`** on the store route. Drop `unregisteredManagersForNotification` fallback.

**F3. `app/Http/Controllers/NotificationController.php`** — *direct port (~65 LOC)*. `index()`, `markRead($id)`, `markAllRead()`. Uses Laravel built-in `$user->notifications()`. No adaptation needed.

**F4. (No DepartmentSettingsController)** — dropped entirely with multi-company routing.

**F5. (No HelpController for tickets)** — Claritas added 3 help pages (`/help/tickets`, `/help/manage`, `/help/department-settings`). On EIAAW, the existing `KnowledgeBaseController` is the help home — instead, add 2 markdown pages to the knowledge base (no new controller needed): `Tickets — User Guide` and `Tickets — Manager Guide`. **Skip if knowledge base structure doesn't accommodate Blade content easily** — defer to a later session. Not a launch blocker.

### Phase G — Console commands (2 files)

**G1. `app/Console/Commands/SendBirthdayWishes.php`** — *adapted from Claritas*

Claritas runs single-tenant — its query is `Employee::whereNull('active_until')->whereMonth('date_of_birth', $month)->whereDay(...)`.

On EIAAW the command runs OUTSIDE any tenant context (CLI), so `TenantScope` won't kick in. Two options:

- **Option 1 (preferred):** Wrap each tenant in `TenantContext::run($tenant, fn() => ...)` — this is the existing pattern used by `MeterTenantUsage`, `BillingPastDueSuspend`, etc. Check `app/Support/TenantContext.php` (or wherever it lives) to confirm the helper name. Iterate `Tenant::all()` and process each in its own context so `BelongsToTenant` auto-scoping works correctly.

- **Option 2:** Bypass `TenantScope` with `Employee::withoutGlobalScope(TenantScope::class)` and explicitly filter by `tenant_id` in the loop. Simpler but loses tenant-context guarantees for downstream code (the mailable's tenant-aware logo lookup will break).

**Use Option 1.** Pattern:
```php
foreach (Tenant::query()->where('status', 'active')->get() as $tenant) {
    TenantContext::run($tenant, function () {
        $today = now();
        $employees = Employee::whereNull('active_until')
            ->whereMonth('date_of_birth', $today->month)
            ->whereDay('date_of_birth', $today->day)
            ->where(function ($q) use ($today) {
                $q->whereNull('birthday_email_sent_year')
                  ->orWhere('birthday_email_sent_year', '!=', $today->year);
            })
            ->get();
        foreach ($employees as $emp) {
            // resolve target email + dispatch BirthdayWishMail + update employee.birthday_email_sent_year
        }
    });
}
```

Don't forget the Feb 29 leap-year handling Claritas does (send on Feb 28 in non-leap years).

**G2. `app/Console/Commands/RemindStaleTickets.php`** — *adapted from Claritas (~137 LOC)*

Same tenant-iteration wrapper. Per-tenant:
- Find active tickets idle 24h+ (max of `tickets.updated_at` and `ticket_messages.created_at`).
- Throttle by `last_reminder_sent_at >= now() - 24h`.
- Auto-transition Open → Pending for tickets with no PIC and no manager activity in 24h.
- Email PIC if assigned; else dept managers via `Ticket::picPoolForDepartment(..., false)`.
- Use Postgres `EXTRACT(EPOCH FROM ...)` not `TIMESTAMPDIFF`.

**G3. Register schedules in `routes/console.php`** (append after the last existing schedule):
```php
Schedule::command('birthdays:send-wishes')
    ->everyMinute()
    ->timezone('Asia/Kuala_Lumpur')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/birthday-wishes.log'));

Schedule::command('tickets:remind-stale')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/ticket-reminders.log'));
```

Names match Claritas. Cron frequency: Claritas uses `everyMinute` for birthdays so an HR edit of someone's DOB takes effect immediately on the same day. Keep that pattern.

### Phase H — Views (Blade) (7 files)

These are the highest-volume copy-paste — total ~2,800 LOC. Each needs adaptation for EIAAW's layout shell (`resources/views/layouts/app.blade.php`) and CSP nonce pattern (`nonce="{{ $cspNonce ?? '' }}"` on every inline `<script>`).

**H1. `resources/views/tickets/index.blade.php`** (~373 LOC) — Self-service ticket list with company → dept accordion + tabs.

Drop:
- Any reference to `ticket_company_name` subselect (the controller now passes the company name directly through the relation).
- The company-filter UI on the Assigned tab (no cross-company concept).

Adapt:
- Replace any `@extends('layouts.claritas')` with EIAAW's layout name. **Verify layout name first.**
- All `<script nonce="...">` blocks need to use EIAAW's nonce variable.
- Replace any Claritas-specific brand strings ("Claritas", logo paths) with EIAAW tokens.

**H2. `resources/views/tickets/show.blade.php`** (~670 LOC) — Ticket detail page with chat panel, attachment grid, PIC dropdown, status dropdown, edit log audit (sysadmin only).

Drop:
- Service-company change picker.
- Routing preview block (no resolved-service-by-dept needed).

Adapt:
- Chat polling endpoint URL: change Claritas's `/tickets/{id}/messages` to whatever EIAAW route generates (likely the same — route names are under our control).
- CSP nonce on the polling JS block.
- Resolve EIAAW's CSRF token meta tag (the chat panel posts via fetch with X-CSRF-TOKEN header).

**H3. `resources/views/tickets/create.blade.php`** (~600 LOC) — Raise New Ticket form with department/subject cascading dropdown, attachment uploader, keyword inference.

Drop:
- Service-company picker.
- `resolvedServiceByDept` JSON blob.
- Routing-preview UI block.

Keep:
- Company dropdown (still useful — the raiser may file on behalf of a sub-company within the tenant; same Company list, tenant-scoped).
- The 17-department subjects optgroup with `subjectToDepartments` map for client-side validation.
- Keyword inference JS for "Other" subjects.
- Attachment drop-zone (10 files max, 10MB each, image/pdf only).

**H4. `resources/views/tickets/manage.blade.php`** (~589 LOC) — Manager dashboard with analytics cards + accordion ticket list.

Adapt as for H1. **The analytics cards (priority count, PIC times, dept health) are reused from `partials/analytics-card-2-pic-times.blade.php` and `partials/analytics-card-3-dept-health.blade.php`** — port those partials too (`H6`, `H7`).

**H5. `resources/views/tickets/edit-admin.blade.php`** (~150 LOC) — Re-route a misfiled ticket. Drop the company picker; keep only the department dropdown and the change-note field.

**H6. `resources/views/tickets/partials/analytics-card-2-pic-times.blade.php`** (~53 LOC) — direct port; remove the company filter dropdown if it's redundant in the simplified per-tenant view (decide during implementation — may be useful for sub-company-filtered analytics within a tenant).

**H7. `resources/views/tickets/partials/analytics-card-3-dept-health.blade.php`** (~73 LOC) — direct port.

**H8. `resources/views/layouts/app.blade.php` (modify in place)** — Add the "Tickets" nav link in the appropriate sidebar/topbar section. Show "Tickets" for everyone (creator view) + "Ticket Management" link for users where `Auth::user()->canAccessTicketManagement()` returns true.

Also add the notification bell icon if EIAAW doesn't already have one — Claritas added a header partial in `layouts/app.blade.php` (the 274-line diff in the commit log includes this). Confirm by reading the EIAAW layout first.

**H9. (Skipped — no `/superadmin/department-settings`)**

**H10. (Skipped — knowledge-base ticket help pages deferred)**

### Phase I — Auth hardening

EIAAW already has most of Claritas's hardening (single-session, lockout, 2FA enforcement, timing-resistant errors). The Claritas commits to apply are:

- `7800ec6` "Fixed bug in password creation page"
- `2f72cc8` "Security and login fix"

**I1. Diff Claritas's current `AuthController` against EIAAW's. The Claritas one shown in this session adds:**
- Unified generic error message `'The provided credentials do not match our records.'` for all login failures.
- Dummy hash check for non-existent users to prevent timing-based enumeration.
- Per-user `login_attempts` increment + auto-lockout at 5.
- `SecurityAuditLog::record('failed_login', ...)` and `'lockout'` events.
- `ThreatDetector::analyze(...)` hooks.
- Exit-date safety check on every login (deactivates if exit_date passed, even for "current" employees).
- Generic `'If an account exists ... reset link sent'` message on password reset.

**EIAAW likely already has most of this** (confirmed: `requiresTwoFactor`, `session_token`, `EnforceSingleSession`, `EnforceTwoFactor`, `SecurityAuditLog`). Run a diff before changing anything:

```
diff -u "c:/laragon/www/EIAAW-employee portal/app/Http/Controllers/AuthController.php" \
        "c:/laragon/www/claritas-onboarding/app/Http/Controllers/AuthController.php"
```

Apply only the missing pieces. The phantom-subdomain guard at the top of `showLogin()` is EIAAW-specific and must be preserved.

**I2. ~~`resources/views/auth/set-password.blade.php`~~ — DROPPED 2026-05-14.** EIAAW's signup flow uses `SignupInvite` (apex marketing → confirmation token → tenant provision), not Claritas's "verify email → set password" two-step. There is no equivalent view to upgrade.

The improved set-password page from Claritas commit `7800ec6` has:
- Live password strength bars (4-level: Weak/Fair/Good/Strong).
- Live requirement checklist (min 8, ≥1 number, ≥1 symbol — color-coded check marks).
- Match indicator with ✓/✗.
- Submit button disabled until all met.

Adapt: replace any Claritas branding (logo, gradient header) with EIAAW's lockup. CSP nonce on the inline JS. Same Bootstrap classes — EIAAW also uses Bootstrap-style utility classes.

**EIAAW's signup flow is different from Claritas** (Claritas: `register → checkEmail → setPassword`. EIAAW: `signup invite → tenant provisioning → set initial password`). Verify by reading EIAAW's signup routes and `SignupInvite` model before swapping the set-password page. The view itself should fit either flow; what's different is the form's POST target.

### Phase J — Routes (`routes/web.php`)

Add inside the existing tenant-app `auth` middleware group (the same group that hosts `/dashboard`, `/employees`, `/leave`, etc.):

```php
// Ticketing — self-service (no plan gate; universal per Amos's decision)
Route::prefix('tickets')->name('tickets.')->group(function () {
    Route::get('/', [TicketController::class, 'index'])->name('index');
    Route::get('/create', [TicketController::class, 'create'])->name('create');
    Route::post('/', [TicketController::class, 'store'])
        ->middleware('scan-uploads')
        ->name('store');
    Route::get('/manage', [TicketController::class, 'manage'])->name('manage');
    Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
    Route::get('/{ticket}/edit-admin', [TicketController::class, 'editAdmin'])->name('edit-admin');
    Route::put('/{ticket}/admin', [TicketController::class, 'updateAdmin'])->name('update-admin');
    Route::post('/{ticket}/assign-pic', [TicketController::class, 'assignPic'])->name('assign-pic');
    Route::post('/{ticket}/status', [TicketController::class, 'updateStatus'])->name('status');

    Route::get('/{ticket}/messages', [TicketMessageController::class, 'index'])->name('messages.index');
    Route::post('/{ticket}/messages', [TicketMessageController::class, 'store'])
        ->middleware('scan-uploads')
        ->name('messages.store');
});

// Notifications (bell icon feed)
Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('/{id}/read', [NotificationController::class, 'markRead'])->name('read');
    Route::post('/mark-all-read', [NotificationController::class, 'markAllRead'])->name('mark-all-read');
});
```

Register `scan-uploads` alias in `bootstrap/app.php` (see E3).

### Phase K — Wire-up + smoke test + commit

**K1.** Run `php artisan migrate` against a local dev tenant DB and confirm all 7 new tables + 1 column ALTER apply cleanly. Pay attention to RLS policy syntax (FORCE ROW LEVEL SECURITY).

**K2.** Smoke test path (manual, with one test tenant):
1. Log in as a creator → `/tickets/create` → raise a ticket with attachment → confirm it appears on `/tickets`.
2. Log in as a manager → `/tickets/manage` → see the new ticket → assign self as PIC → confirm status changes to "In Progress".
3. Add chat message with attachment → confirm appears in chat panel + creator gets email.
4. Mark Resolved → creator gets resolution email.
5. Log in as superadmin → confirm "Edit" button on the ticket re-routes to a different department + edit log recorded.
6. Cross-tenant check (CRITICAL): log in to a *second* tenant — confirm the first tenant's ticket is INVISIBLE. RLS + TenantScope must both block it.
7. Trigger `php artisan birthdays:send-wishes` manually with a test employee whose DOB = today → confirm email + `birthday_email_sent_year` set + idempotent on re-run.
8. Trigger `php artisan tickets:remind-stale` manually with a manually-aged ticket (set `updated_at` to 30h ago) → confirm reminder mail + `last_reminder_sent_at` set + transition to Pending if unassigned.
9. Auth/lockout: 5 wrong passwords → account locked → confirm `SecurityAuditLog` row + generic error message.

**K3.** Run `./vendor/bin/pint` to format. Run `composer run test` (which runs `config:clear + phpunit`). Investigate any failures — most likely candidates: tenant scoping leaks into existing tests; a Postgres-only RLS migration running in MySQL test config.

**K4.** Commit with a message like:

```
feat: replicate Claritas Ticketing + Birthday + auth hardening

Ports Claritas commits 308e0d2..ae438e7 into the multi-tenant SaaS,
adapted per Amos's scope decisions:
- Ticketing module: per-tenant, no multi-company cluster routing.
- Birthday-card cron with idempotent year-tag.
- Auth hardening: pulls in any missing pieces vs EIAAW's existing
  single-session/2FA/lockout stack.

Tickets: 17 departments, 5-status lifecycle, chat + attachments
(malware-scanned), edit logs, hourly stale-ticket reminders,
per-tenant analytics (priority, PIC times, department health).

Routes universal (no plan gate). RLS + TenantScope enforce tenant
isolation; smoke-test K6 confirmed cross-tenant invisibility.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
```

**K5.** Push only after K2/K3 pass.

---

## 3. Risks + open questions — RESOLVED 2026-05-14

1. **Notifications table** — does NOT exist. Create in A1 (Postgres-only, with `tenant_id` + RLS).
2. **Layout shell** — `layouts.app` on both sides. EIAAW uses `nonce="{{ $cspNonce ?? '' }}"` pattern.
3. **`Company` model** — uses `BelongsToTenant`. Drops in cleanly for `ticket.company_id`.
4. **EIAAW signup flow ≠ Claritas register flow** — EIAAW uses `SignupInvite` (apex marketing → confirmation token → tenant provision). Claritas's "register → checkEmail → setPassword" flow is not present. **Phase I.2 (set-password view replacement) is DROPPED.** Auth hardening reduces to a small AuthController diff for any missing pieces (lockout, timing-resistance, audit logging) — most or all likely already in place.
5. **TenantContext** — class is `App\Support\TenantContext`. Methods: `forEach(callable)` to iterate all active tenants, `run(Tenant, callable)` to enter one, `asNone(callable)` to clear context. Used by `LeaveReminder`, `ClaimDeadlineReminder`, `OffboardingNotifications`, `MeterTenantUsage`. **Use `forEach` for `birthdays:send-wishes` and `tickets:remind-stale`.**
6. **`SecurityAuditLog::record(string $eventType, array $context = []): void`** — matches Claritas signature exactly. No adaptation.
7. **CSP `connect-src`** — currently `'self' https://sa.eiaawsolutions.com`. Same-origin chat polling is fine.

Items still flagged for in-flight watchfulness:
- **Postgres syntax** — TIMESTAMPDIFF → EXTRACT(EPOCH ...) / 60, FIELD → CASE, native ENUM → CHECK constraint.
- **Views heaviest phase** — 60-70% of session H time. Split point is between Phase H and prior phases.

---

## 4. Implementation session budget

| Phase | Estimated LOC | Estimated time |
|---|---:|---:|
| A — Migrations (7 files) | ~400 | 1.0 hr |
| B — Models (8 files, mostly Ticket.php) | ~900 | 1.5 hr |
| C — Notifications (6 files) | ~270 | 0.4 hr |
| D — Mailables + templates (6 + 6) | ~700 | 1.0 hr |
| E — Services + middleware (3 files) | ~420 | 0.5 hr |
| F — Controllers (3 files) | ~1000 | 1.5 hr |
| G — Console commands (2 files + register) | ~250 | 0.5 hr |
| H — Views (7 files) | ~2800 | 3.5 hr |
| I — Auth hardening (1 controller + 1 view) | ~300 | 0.7 hr |
| J — Routes | ~50 | 0.1 hr |
| K — Smoke test + Pint + commit | — | 1.5 hr |
| **Total** | **~7000** | **~12 hr** |

Single contiguous Claude Code session can probably reach **half** of this realistically (Phases A-G + the auth fix). Phase H (views) is the natural split point — they're the most copy-paste-heavy and can be a second session.

---

## 5. Pre-flight checklist before implementation session starts

- [ ] Reread `docs/PLAN-CLARITAS-REPLICATION.md` (this file).
- [ ] Confirm decisions in §0 still hold; if any changed, update the plan first.
- [ ] Confirm we're on a clean `main` branch with no uncommitted work.
- [ ] Run `php artisan migrate:status` to confirm we're up to date with prior migrations.
- [ ] Resolve open questions §3.1-3.6 (read the relevant EIAAW files) before writing code.
- [ ] Have a test tenant + a second test tenant ready in local dev for cross-tenant isolation verification.
- [ ] Have a test employee with `date_of_birth = today` ready for birthday-card smoke test.
- [ ] Mailhog / Mailtrap running locally to capture outbound email.
- [ ] (Optional) ClamAV running locally if testing the malware scanner's full path. Heuristic-only path requires nothing.

---

## 6. Out-of-scope notes

- Knowledge-base help pages for tickets (Claritas `/help/tickets`, `/help/manage`) — deferred. Add later if Amos requests.
- Bulk ticket actions (close-many, reassign-many) — not in Claritas, not in scope.
- Ticket SLA tracking / escalation matrix — not in Claritas's latest, not in scope.
- WebSocket-based live chat (instead of polling) — Claritas uses polling; keep parity.
- Multi-tenant analytics (HQ-level "how many tickets across all tenants") — should NOT exist; tenant isolation is mandatory.
- Subscription-tier gating for tickets — decision was "universal." If Amos later wants to gate, add `plan:core.tickets` middleware on the route group + add `core.tickets` to every plan's `features` array in `config/plans.php` then carve out one plan that doesn't include it. Trivial.
- Stripe integration changes — none. Tickets are not metered or billed.

---

End of plan.
