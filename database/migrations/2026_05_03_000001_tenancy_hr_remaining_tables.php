<?php

use App\Support\TenancyMigration;

/**
 * Session 4 — Batch retrofit: HR remaining + Leave + Attendance + IT tables.
 *
 * Order matters: parents before children. We respect the FK dependency graph
 * so backfill works in a single forward pass.
 *
 *   - Companies: per-tenant master data; no parent backfill (fresh tables empty
 *     on SaaS deploy; in real Claritas migration, would assign all to default tenant).
 *   - HR child records of employees (children, contracts, edit logs, history).
 *   - Onboarding edit logs link via onboarding_id.
 *   - IT tasks link to onboardings or offboardings.
 *   - Offboardings link to employees.
 *   - Aarfs link to employees (employee_id).
 *   - Announcements have only created_by → users.
 *   - Leave/Attendance master + transactional tables.
 *
 * Tables WITHOUT a backfill source (config-style) get parent_via=null. Their
 * existing rows on a fresh PG deploy = 0, so the orphan-delete is harmless.
 * On a future Claritas migration we'd seed defaults per tenant explicitly.
 */
return new class extends TenancyMigration {
    protected array $tables = [
        // ── Companies + announcements + permissions + audit logs ────────────
        ['table' => 'companies',                     'parent_via' => null,           'parent_table' => null,        'parent_pk' => null],
        ['table' => 'announcements',                 'parent_via' => 'created_by',   'parent_table' => 'users',     'parent_pk' => 'id'],
        ['table' => 'user_permissions',              'parent_via' => 'user_id',      'parent_table' => 'users',     'parent_pk' => 'id'],
        ['table' => 'security_audit_logs',           'parent_via' => null,           'parent_table' => null,        'parent_pk' => null],

        // ── Employee history + child records ───────────────────────────────
        ['table' => 'employee_histories',            'parent_via' => null,           'parent_table' => null,        'parent_pk' => null],
        ['table' => 'employee_child_registrations',  'parent_via' => 'employee_id',  'parent_table' => 'employees', 'parent_pk' => 'id'],
        ['table' => 'employee_contracts',            'parent_via' => 'employee_id',  'parent_table' => 'employees', 'parent_pk' => 'id'],
        ['table' => 'employee_edit_logs',            'parent_via' => 'employee_id',  'parent_table' => 'employees', 'parent_pk' => 'id'],
        ['table' => 'onboarding_edit_logs',          'parent_via' => 'onboarding_id','parent_table' => 'onboardings','parent_pk' => 'id'],

        // ── Offboarding + AARF + IT tasks ──────────────────────────────────
        ['table' => 'offboardings',                  'parent_via' => 'employee_id',  'parent_table' => 'employees', 'parent_pk' => 'id'],
        ['table' => 'aarfs',                         'parent_via' => 'employee_id',  'parent_table' => 'employees', 'parent_pk' => 'id'],
        ['table' => 'it_tasks',                      'parent_via' => 'onboarding_id','parent_table' => 'onboardings','parent_pk' => 'id'],

        // ── Leave master tables (per-tenant config) ────────────────────────
        ['table' => 'leave_types',                   'parent_via' => null,           'parent_table' => null,        'parent_pk' => null],
        ['table' => 'leave_entitlements',            'parent_via' => 'leave_type_id','parent_table' => 'leave_types','parent_pk' => 'id'],
        ['table' => 'public_holidays',               'parent_via' => null,           'parent_table' => null,        'parent_pk' => null],

        // ── Leave transactions ─────────────────────────────────────────────
        ['table' => 'leave_applications',            'parent_via' => 'employee_id',  'parent_table' => 'employees', 'parent_pk' => 'id'],
        ['table' => 'leave_balances',                'parent_via' => 'employee_id',  'parent_table' => 'employees', 'parent_pk' => 'id'],

        // ── Attendance ─────────────────────────────────────────────────────
        ['table' => 'work_schedules',                'parent_via' => null,           'parent_table' => null,        'parent_pk' => null],
        ['table' => 'attendance_records',            'parent_via' => 'employee_id',  'parent_table' => 'employees', 'parent_pk' => 'id'],
        ['table' => 'overtime_requests',             'parent_via' => 'employee_id',  'parent_table' => 'employees', 'parent_pk' => 'id'],
    ];
};
