# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Initial setup
composer run setup           # composer install + migrate + npm install + build

# Development (runs server, queue, logs, and Vite concurrently)
composer run dev

# Build frontend assets
npm run build
npm run dev

# Testing
composer run test            # config:clear + phpunit
php artisan test             # run all tests
php artisan test --filter=TestName  # run a single test

# Database
php artisan migrate
php artisan migrate:fresh --seed

# Linting / formatting
./vendor/bin/pint            # Laravel Pint (PSR-12)
```

## Architecture Overview

This is a **multi-role HR platform** built on Laravel 12 with Blade + Tailwind CSS v4. It covers the full employee lifecycle plus several parallel modules:
- **Core:** Onboarding / Offboarding / Employee records
- **HR Operations:** Leave management, payroll & payslips, attendance tracking, expense claims (eClaim), EA forms
- **IT Operations:** Asset inventory, provisioning, AARF acknowledgements
- **Finance:** Accounting module (Chart of Accounts, AR/AP, GL, invoices, purchase orders, budgeting, tax returns) under `app/Http/Controllers/Accounting/`
- **Admin:** Company management, knowledge base, system overview/reports, role/permission assignment

### Roles & Access
Role groups with granular sub-roles:
- **HR** (`hr_manager`, `hr_executive`, `hr_intern`) — employee lifecycle; only `hr_manager` can edit records, download contracts, and access restricted documents
- **IT** (`it_manager`, `it_executive`, `it_intern`) — asset inventory, provisioning, offboarding IT tasks; view-only on employee/offboarding records
- **SuperAdmin** (`superadmin`) — company management, role assignment; effectively has HR Manager permissions
- **User** (`employee`) — self-service profile/account management
- `system_admin` — internal admin role; treated like HR Manager in most views

`User` model has coarse helpers (`isHr()`, `isIt()`, `isSuperadmin()`) and fine-grained capability methods (`canEditOnboarding()`, `canViewAssets()`, `canEditAarf()`, etc.) — always prefer these over raw role string comparisons. In Blade views, local `$canEdit` / `$canViewContracts` variables are derived from these helpers and used to gate UI elements.

Route middleware enforces role access. Check `routes/web.php` and `app/Providers/AuthServiceProvider.php` for authorization gates/policies.

### Authentication
Uses a **custom authentication provider** (`WorkEmailUserProvider`) that authenticates against the employee's work email instead of a personal email. Configured in `config/auth.php` as `work_email_eloquent` provider. Password reset expiry is 60 minutes, timeout is 3 hours.

### Employee Lifecycle Flow
```
OnboardingInvite → register/set-password → Employee (active) → Offboarding
```
- `Onboarding` model tracks the pre-hire onboarding process
- `Employee` model is the central entity; related data lives in separate models (PersonalDetail, WorkDetail, EmployeeEducationHistory, EmployeeSpouseDetail, EmployeeEmergencyContact, EmployeeChildRegistration, EmployeeContract)
- `EmployeeHistory` records lifecycle events
- `Offboarding` model tracks the exit process

### IT Asset Flow
```
AssetInventory → AssetAssignment (to employee) → AssetProvisioning → return/DisposedAsset
```
`Aarf` (Annual Asset Record Form) links assets to employees for acknowledgement via tokenized email links.

### Key Models
- `Employee` — central entity, has many relationships to all employee sub-tables
- `User` — auth model; linked 1:1 to `Employee`
- `Onboarding` / `Offboarding` — process tracking with status enums
- `ItTask` — IT work items assigned during onboarding/offboarding
- `AssetInventory` — master asset records; `AssetAssignment` links them to employees

### Scheduled Commands (`routes/console.php`)
- `employees:activate` — every minute; activates employees on start date + sends welcome email, flushes `invite_staging_json` via `populateFromOnboarding()`
- `offboarding:notify` — every minute; time-based offboarding email reminders
- `leave:remind-managers` / `claims:remind` — daily at 9 AM
- `security:audit-report` — hourly
- `log:verify-integrity` — daily at 3 AM via `LogIntegrity` service
- `backup:run` — daily at 2 AM (full encrypted backup) + database snapshots every 6 hours; 30-day retention
- `RefreshSystemMetadata` — hourly; caches dashboard/knowledge-base metadata (1-hour TTL) via `SystemMetadataService`

### Security Architecture
- **`EnforceSingleSession`** middleware — prevents concurrent logins by rotating session tokens; kicks prior session when a new login occurs
- **`SecurityAuditMiddleware`** — logs 403s and rate anomalies to `SecurityAuditLog` model; plugs into `ThreatDetector` service for real-time threat analysis
- **`SecurityHeaders`** / **`ForceHttps`** — additional hardening middleware (controlled via `FORCE_HTTPS` env var)
- **`SecureFileController`** — serves private files with a `DIRECTORY_PERMISSIONS` map that enforces per-directory role checks; guards against path traversal
- **File validation in `AppServiceProvider`:** `valid_file_content` rule checks magic bytes against declared MIME type; `sanitize_image` rule strips EXIF/metadata via `ImageSanitizer` service
- Upload rate-limiting: `throttle:uploads` (10 uploads/minute per user/IP)

### File Storage
Two disks: `local` (private: `storage/app/private`, served via `SecureFileController`) and `public` (public: `storage/app/public`). Sensitive files (NRIC, contracts, certificates) go to the private disk and are served with role-gated access checks.

### Mail
24 Mailable classes in `app/Mail/`, each with a corresponding Blade template in `resources/views/emails/`. The default sender is `hr@claritas.com` (configured via `MAIL_FROM_ADDRESS`).

Notable mail classes:
- `OnboardingEditNotificationMail` — plain notification sent when HR edits an onboarding record (no acknowledgement required)
- `EmployeeConsentRequestMail` / `ConsentRequestMail` — full re-acknowledgement flow with token link, used for **employee listing and profile** edits only
- `WelcomeNewHire` — sent by `ActivateEmployees` command on start date
- `SuspiciousActivityAlert` / `SecurityAuditMail` — triggered by `ThreatDetector`
- `ClaimApprovedMail`, `ClaimSubmittedMail`, `ClaimReminderMail` — eClaim workflow
- `LeaveApplicationNotifyMail`, `LeaveApprovalNotifyMail`, `PendingLeaveReminderMail` — leave workflow
- `EaFormReadyMail` — payroll EA form notification

### Frontend
- Blade templates under `resources/views/` organized by role (`hr/`, `it/`, `user/`, `superadmin/`) plus `accounting/` and `reports/`
- Shared layout at `resources/views/layouts/app.blade.php`
- Tailwind CSS v4 via `@tailwindcss/vite` plugin — no `tailwind.config.js`; config lives in `resources/css/app.css`
- No JS framework; Alpine.js or vanilla JS where needed

### Testing
- PHPUnit with two suites: `Unit` (`tests/Unit/`) and `Feature` (`tests/Feature/`)
- Tests use SQLite in-memory database (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`)
- Array drivers for cache and mail in test environment

### Database
- MySQL in production/local (`claritas_onboarding`), SQLite in-memory for tests
- 44 migrations; the first 4 (prefixed `2024_01_`) define the core schema, subsequent `2026_03_` migrations are incremental enhancements
- Timezone: `Asia/Kuala_Lumpur` (set in `config/app.php`)

### Onboarding Staging JSON (`invite_staging_json`)
Sections F–I (Education, Spouse, Emergency Contacts, Children) submitted by a new hire via the public invite form are stored as a JSON blob in `personal_details.invite_staging_json`. They are **not** immediately written to their relationship tables. The `ActivateEmployees` command calls `populateFromOnboarding()` on the employee's start date to flush this staging data into the proper tables (`employee_education_histories`, `employee_spouse_details`, etc.).

- When displaying Sections G/H/I for an employee whose relationship tables are still empty, `resources/views/partials/employee-extra-sections-view.blade.php` reads from `invite_staging_json` as a fallback.
- When HR edits an onboarding record and only changes Sections B or C (Work/Asset), `buildStagingJson()` returns the existing JSON unchanged to prevent wiping staging data.

### Consent & Edit Log Flows (two distinct flows)
| Context | Log model | Flow |
|---|---|---|
| Onboarding record edits (pre-hire) | `OnboardingEditLog` | Notification email only — `consent_required = false`, no token |
| Employee listing / profile edits | `EmployeeEditLog` | Full re-acknowledgement: token generated, expiry set, consent link emailed |

Only Sections A, F, G, H, I trigger any email for onboarding edits. The employee consent flow is always triggered for relevant edits in `EmployeeController`.

### Multi-file Storage Patterns
- **NRIC/Passport:** `personal_details.nric_file_paths` (JSON array). In employee records, mirrored to `employees.nric_file_paths`. Keep/remove controlled via `nric_keep_paths[]` hidden inputs on edit forms.
- **Education certificates:** `employee_education_histories.certificate_paths` (JSON array, max 5). Legacy single-file column `certificate_path` is kept as the first entry for backwards compatibility. Keep/remove controlled via `edu_cert_keep[i][]` hidden inputs; new files use the DataTransfer API to attach File objects to a hidden `<input type="file">`.

### IT vs HR Offboarding Views
There are two separate view paths for offboarding detail:
- `hr.offboarding.show` — accessed by HR staff via `hr.offboarding.index`
- `it.offboarding-show` — accessed by IT staff via `it.offboarding.index`

Both views display Sections F–I via `partials.employee-extra-sections-view`. The IT view is read-only and locks contract/handbook/orientation documents with an "HR only" badge.

### Key Services (`app/Services/`)
- `SystemMetadataService` — aggregates data for executive dashboards and knowledge base; results cached 1 hour
- `ThreatDetector` — tracks login failures, rate anomalies, unauthorized access; configurable detection windows
- `LogIntegrity` — verifies security audit log chain integrity (HMAC chaining via `LOG_INTEGRITY_KEY`)
- `ImageSanitizer` — strips EXIF metadata from uploaded images
- `AccountingService` / `AiAccountingService` — accounting module business logic and AI invoice scanning

### Pending Route Change
`web.php.routes-to-add.txt` documents a planned registration route split. The routes in `routes/web.php` already reflect this update — the `.txt` file can be ignored.
