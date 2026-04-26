<?php

/**
 * EIAAW Workforce — Plan & feature catalog.
 *
 * Four-module bundling (Session 11):
 *   M1 Employee Journey  — onboarding, employee listing, offboarding, org chart
 *   M2 Asset Management  — asset inventory, AARF, IT offboarding workflow
 *   M3 HRM               — leave, attendance, claims, payroll, payslips, EA forms
 *   M4 Finance           — full accounting (CoA, GL, AR/AP, tax, fixed assets, budgets)
 *
 *   Starter     — M1                     — USD 6 / active employee / month
 *   Growth      — M1 + M2 + M3           — USD 14
 *   Scale       — M1 + M2 + M3 + M4      — USD 29
 *   Enterprise  — Scale + SSO + dedicated DB + audit export  — Custom
 *
 * Tenant::hasFeature($key) reads from here. Middleware `plan:it.assets`,
 * `plan:finance.accounting`, `plan:hr.payroll`, etc. gate routes.
 *
 * Pricing is USD-only. Billing currency on the tenant record is used by
 * Cashier for invoice presentation; MYR was dropped in Session 11.
 * Min 5 employees. Annual billing = 2 months free (×10).
 */
return [

    'starter' => [
        'name' => 'Starter',
        'price_usd_monthly' => 6,
        'min_seats' => 5,
        'ai_budget_usd' => (float) env('AI_BUDGET_STARTER_USD', 5),
        'modules' => ['M1'],
        'features' => [
            // M1 — Employee Journey
            'core.employees',
            'core.onboarding',
            'core.offboarding',
            'admin.single_user',
            'admin.multi_user',
            'ai.basic',
        ],
    ],

    'growth' => [
        'name' => 'Growth',
        'price_usd_monthly' => 14,
        'min_seats' => 5,
        'ai_budget_usd' => (float) env('AI_BUDGET_GROWTH_USD', 15),
        'modules' => ['M1', 'M2', 'M3'],
        'features' => [
            // Inherits Starter (M1)
            'core.employees',
            'core.onboarding',
            'core.offboarding',
            'admin.single_user',
            'admin.multi_user',
            // M2 — Asset Management
            'it.assets',
            'it.aarf',
            'it.offboarding',
            // M3 — HRM
            'core.leave',
            'core.attendance',
            'hr.eclaim',
            'hr.payroll',
            'hr.payslips',
            'hr.ea_forms',
            'ai.basic',
        ],
    ],

    'scale' => [
        'name' => 'Scale',
        'price_usd_monthly' => 29,
        'min_seats' => 5,
        'ai_budget_usd' => (float) env('AI_BUDGET_SCALE_USD', 40),
        'modules' => ['M1', 'M2', 'M3', 'M4'],
        'features' => [
            // Inherits Growth (M1 + M2 + M3)
            'core.employees',
            'core.onboarding',
            'core.offboarding',
            'admin.single_user',
            'admin.multi_user',
            'it.assets',
            'it.aarf',
            'it.offboarding',
            'core.leave',
            'core.attendance',
            'hr.eclaim',
            'hr.payroll',
            'hr.payslips',
            'hr.ea_forms',
            // M4 — Finance
            'finance.accounting',
            'admin.knowledge_base',
            'ai.advanced',
        ],
    ],

    'enterprise' => [
        'name' => 'Enterprise',
        'price_usd_monthly' => null,  // custom
        'min_seats' => 50,
        'ai_budget_usd' => (float) env('AI_BUDGET_ENTERPRISE_USD', 200),
        'modules' => ['M1', 'M2', 'M3', 'M4', 'Enterprise extensions'],
        'features' => [
            // Inherits Scale — full four modules
            'core.employees',
            'core.onboarding',
            'core.offboarding',
            'admin.single_user',
            'admin.multi_user',
            'it.assets',
            'it.aarf',
            'it.offboarding',
            'finance.accounting',
            'core.leave',
            'core.attendance',
            'hr.eclaim',
            'hr.payroll',
            'hr.payslips',
            'hr.ea_forms',
            'admin.knowledge_base',
            // Enterprise-only extensions
            'auth.sso_saml',
            'auth.sso_oidc',
            'audit.export',
            'infra.dedicated_db',
            'support.sla',
            'ai.unlimited',
        ],
    ],

];
