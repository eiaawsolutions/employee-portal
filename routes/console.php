<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('employees:activate')->everyMinute();
Schedule::command('offboarding:notify')->everyMinute();
Schedule::command('security:audit-report')->hourly();
Schedule::command('leave:remind-managers')->dailyAt('09:00');
Schedule::command('claims:remind')->dailyAt('09:00');
Schedule::command('sweep:pending-weekly')->weeklyOn(3, '00:00'); // Wednesday midnight

// Backup: daily encrypted full backup at 2 AM, retain 30 days
Schedule::command('backup:run --type=full --encrypt --keep=30')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/backup.log'));

// Backup: database-only snapshot every 6 hours for RPO minimization
Schedule::command('backup:run --type=database --encrypt --keep=7')
    ->everySixHours()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/backup.log'));

// Log integrity: verify the audit log chain daily at 3 AM
Schedule::command('log:verify-integrity')
    ->dailyAt('03:00')
    ->appendOutputTo(storage_path('logs/integrity-check.log'));

// System metadata: auto-refresh cached metadata for System Overview & Knowledge Base
Schedule::command('system:refresh-metadata')->hourly();

// Update checker: daily package update scan + security score refresh
Schedule::command('system:check-updates')->dailyAt('06:00');

// Billing (Wk3): trial-end auto-downgrade and past-due grace suspension.
// Run early morning, after the 02:00 backup window.
Schedule::command('billing:trial-end')
    ->dailyAt('02:15')
    ->appendOutputTo(storage_path('logs/billing.log'));

Schedule::command('billing:past-due-suspend')
    ->dailyAt('02:30')
    ->appendOutputTo(storage_path('logs/billing.log'));

// Tenant deletion pipeline (Session 11 A-grade close).
// Phase 1: 30d after cancellation — scrub PII + soft-delete.
// Phase 2: 90d after cancellation — hard-purge rows. Runs with --force in CI.
Schedule::command('billing:delete-canceled')
    ->dailyAt('03:00')
    ->appendOutputTo(storage_path('logs/billing.log'));

Schedule::command('billing:purge-canceled --force')
    ->dailyAt('03:30')
    ->appendOutputTo(storage_path('logs/billing.log'));

// Tenant usage meter — daily snapshot for the HQ Overview dashboard.
// Runs after billing rollups so the AI mirror is consistent. See
// app/Console/Commands/MeterTenantUsage.php and
// app/Support/PlatformAdminVisibility.php for the privacy contract.
Schedule::command('meter:tenant-usage')
    ->dailyAt('03:45')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/meter-tenant-usage.log'));

// Statutory rate drift check — runs weekly, fails CI/ops alerting on drift
// so payroll miscalculation is caught before a live run, not after.
Schedule::command('payroll:verify-statutory-rates')
    ->weeklyOn(1, '04:00')  // Monday 4am
    ->appendOutputTo(storage_path('logs/payroll-statutory.log'));

// Backup restoration test — Monday 05:00, proves DR works before the next
// business-hours incident. Skips if TEST_RESTORE_DSN is unset so non-production
// environments don't fail this step.
Schedule::command('backup:test-restore')
    ->weeklyOn(1, '05:00')
    ->appendOutputTo(storage_path('logs/backup-verify.log'))
    ->skip(fn () => empty(env('TEST_RESTORE_DSN')));

// Marketing leads — retry the sales@ notification email for any
// marketing_contacts rows that didn't deliver on first attempt. Every 10
// minutes so a transient SMTP blip never costs us a sales lead. Capped at
// 5 attempts per row; stalled rows escalate via Log::critical for human
// triage. See app/Services/MarketingLeadNotifier.php.
Schedule::command('marketing:retry-pending-emails')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/marketing-leads.log'));