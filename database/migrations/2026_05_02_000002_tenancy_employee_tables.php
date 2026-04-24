<?php

use App\Support\TenancyMigration;

/**
 * Employee module tenancy retrofit (Session 3).
 *
 * Adds tenant_id + RLS to the Employee record and its 5 directly-attached
 * child tables, plus onboardings (the parent of personal_details/work_details).
 *
 * Logic lives in the TenancyMigration base class — this file is only the
 * table list. See app/Support/TenancyMigration.php for the retrofit pattern.
 */
return new class extends TenancyMigration {
    protected array $tables = [
        // Onboardings has no app-layer parent on a fresh deploy. Empty in
        // SaaS, so backfill is a no-op.
        ['table' => 'onboardings',                  'parent_via' => null,            'parent_table' => null,         'parent_pk' => null],

        // Employee inherits from the linked auth user.
        ['table' => 'employees',                    'parent_via' => 'user_id',       'parent_table' => 'users',      'parent_pk' => 'id'],

        // Onboarding-attached personal/work details inherit from onboardings.
        ['table' => 'personal_details',             'parent_via' => 'onboarding_id', 'parent_table' => 'onboardings','parent_pk' => 'id'],
        ['table' => 'work_details',                 'parent_via' => 'onboarding_id', 'parent_table' => 'onboardings','parent_pk' => 'id'],

        // Employee child tables inherit from employees.
        ['table' => 'employee_spouse_details',      'parent_via' => 'employee_id',   'parent_table' => 'employees',  'parent_pk' => 'id'],
        ['table' => 'employee_emergency_contacts',  'parent_via' => 'employee_id',   'parent_table' => 'employees',  'parent_pk' => 'id'],
        ['table' => 'employee_education_histories', 'parent_via' => 'employee_id',   'parent_table' => 'employees',  'parent_pk' => 'id'],
    ];
};
