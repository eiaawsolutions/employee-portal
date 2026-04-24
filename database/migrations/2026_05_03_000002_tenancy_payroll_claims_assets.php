<?php

use App\Support\TenancyMigration;

/**
 * Session 4 — Batch retrofit: Payroll + Claims + Assets.
 *
 * Order:
 *   - Payroll master (config + items) → salaries → pay runs → payslips → adjustments
 *   - Claims master (categories + policy) → claims → claim items
 *   - Asset master (inventories) → assignments / provisionings / disposed / EA forms
 *   - EA forms (per employee × year)
 */
return new class extends TenancyMigration {
    protected array $tables = [
        // ── Payroll master/config (tenant-scoped config) ───────────────────
        ['table' => 'payroll_configs',               'parent_via' => null,                 'parent_table' => null,             'parent_pk' => null],
        ['table' => 'payroll_items',                 'parent_via' => null,                 'parent_table' => null,             'parent_pk' => null],
        ['table' => 'payroll_regulatory_alerts',     'parent_via' => null,                 'parent_table' => null,             'parent_pk' => null],

        // ── Per-employee salary records ────────────────────────────────────
        ['table' => 'employee_salaries',             'parent_via' => 'employee_id',        'parent_table' => 'employees',      'parent_pk' => 'id'],
        ['table' => 'employee_salary_items',         'parent_via' => 'employee_salary_id', 'parent_table' => 'employee_salaries','parent_pk' => 'id'],
        ['table' => 'salary_adjustments',            'parent_via' => 'employee_id',        'parent_table' => 'employees',      'parent_pk' => 'id'],

        // ── Pay runs and payslips ──────────────────────────────────────────
        ['table' => 'pay_runs',                      'parent_via' => 'created_by',         'parent_table' => 'users',          'parent_pk' => 'id'],
        ['table' => 'payslips',                      'parent_via' => 'employee_id',        'parent_table' => 'employees',      'parent_pk' => 'id'],
        ['table' => 'payslip_items',                 'parent_via' => 'payslip_id',         'parent_table' => 'payslips',       'parent_pk' => 'id'],

        // ── EA forms (Borang EA / CP.8D) ───────────────────────────────────
        ['table' => 'ea_forms',                      'parent_via' => 'employee_id',        'parent_table' => 'employees',      'parent_pk' => 'id'],

        // ── Claims master/config ───────────────────────────────────────────
        ['table' => 'expense_categories',            'parent_via' => null,                 'parent_table' => null,             'parent_pk' => null],
        ['table' => 'expense_claim_policies',        'parent_via' => null,                 'parent_table' => null,             'parent_pk' => null],

        // ── Claims transactional ───────────────────────────────────────────
        ['table' => 'expense_claims',                'parent_via' => 'employee_id',        'parent_table' => 'employees',      'parent_pk' => 'id'],
        ['table' => 'expense_claim_items',           'parent_via' => 'expense_claim_id',   'parent_table' => 'expense_claims', 'parent_pk' => 'id'],

        // ── IT Asset master ────────────────────────────────────────────────
        ['table' => 'asset_inventories',             'parent_via' => null,                 'parent_table' => null,             'parent_pk' => null],

        // ── Asset assignments + lifecycle ──────────────────────────────────
        ['table' => 'asset_assignments',             'parent_via' => 'asset_inventory_id', 'parent_table' => 'asset_inventories','parent_pk' => 'id'],
        ['table' => 'asset_provisionings',           'parent_via' => 'onboarding_id',      'parent_table' => 'onboardings',    'parent_pk' => 'id'],
        ['table' => 'dispose_assets',                'parent_via' => 'asset_inventory_id', 'parent_table' => 'asset_inventories','parent_pk' => 'id'],
    ];
};
