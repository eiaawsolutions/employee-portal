<?php

/**
 * EIAAW Workforce — product config.
 */
return [

    'product_name' => 'EIAAW Workforce',
    'tagline' => 'AI · Human Partnerships',

    'marketing_host' => env('APP_MARKETING_HOST', 'ep.eiaawsolutions.com'),
    'tenant_domain'  => env('APP_TENANT_DOMAIN', 'eiaawsolutions.com'),

    'support_email' => env('SUPPORT_EMAIL', 'hello@eiaawsolutions.com'),
    'sales_email'   => env('SALES_EMAIL', 'sales@eiaawsolutions.com'),
    'company_legal' => 'EIAAW Solutions Sdn. Bhd.',

    /*
     * Reserved tenant slugs.
     *
     * With wildcard DNS (*.eiaawsolutions.com → Railway) every signup
     * provisions instantly with zero infra calls. The trade-off is that
     * the wildcard catches EVERY undefined subdomain, so we must reject
     * slugs that would collide with platform infrastructure, security-
     * sensitive names that could be used to phish operators or staff,
     * and EIAAW-internal identifiers.
     *
     * This is the single source of truth — Tenant::isSlugAvailable() and
     * SignupController both read from here. To extend, add to the array
     * (no code change). To allow a previously-reserved slug, remove from
     * the array AND verify no real subdomain is wired up at Cloudflare.
     *
     * Categories:
     *   - infra  : matches existing CNAMEs in the eiaawsolutions.com zone
     *              or common subdomain conventions (mail, www, etc.)
     *   - eiaaw  : EIAAW-internal product/tenant identifiers
     *   - auth   : security-sensitive names that could be used for phishing
     *   - platform : standard SaaS hostnames a customer might confuse with
     *                first-party EIAAW pages (admin, billing, support, …)
     */
    'reserved_slugs' => [
        // infra (must match existing CNAMEs + standard hostnames)
        'app', 'admin', 'api', 'www', 'mail', 'static', 'assets', 'cdn',
        'ep', 'ads', 'sa',

        // eiaaw internals
        'eiaaw', 'eiaaw-hq', 'eiaaw-admin', 'workforce', 'system', 'hq',

        // auth & security-sensitive (anti-phishing)
        'auth', 'oauth', 'sso', 'saml', 'oidc', 'login', 'signin',
        'signup', 'signout', 'logout', 'register', 'verify', 'webhook',
        'webhooks', 'security', 'admin-portal',

        // platform pages a customer would not own
        'help', 'support', 'status', 'docs', 'documentation', 'blog',
        'about', 'pricing', 'features', 'legal', 'terms', 'privacy',
        'contact', 'sales', 'partners', 'jobs', 'careers',
        'dashboard', 'billing', 'account', 'profile', 'settings',
        'console', 'changelog',

        // ops + monitoring + dev
        'staging', 'dev', 'test', 'qa', 'beta', 'alpha', 'preview',
        'sandbox', 'demo', 'monitor', 'monitoring', 'metrics', 'logs',
        'health', 'ping', 'up',
    ],

    'brand' => [
        'primary' => '#1FA896',
        'primary_dark' => '#11766A',
        'bg' => '#FAF7F2',
        'bg_warm' => '#F3EDE0',
        'ink' => '#0F1A1D',
    ],

    /*
     * Pricing — USD per active employee per month (Session 11 rework).
     *
     * Module bundling:
     *   M1 Employee Journey  — onboarding, listing, offboarding
     *   M2 Asset Management  — inventory, AARF, IT offboarding
     *   M3 HRM               — leave, attendance, claims, payroll, EA forms
     *   M4 Finance           — full accounting
     *
     * Tier bundles:
     *   Starter     $6   — M1 only
     *   Growth      $14  — M1 + M2 + M4
     *   Scale       $29  — M1 + M2 + M3 + M4 (everything)
     *   Enterprise  Custom — Scale + SSO + dedicated DB + audit export + SLA
     *
     * Annual = monthly × 10 (2 months free). Stripe Price IDs populated via
     * `php artisan stripe:sync-prices --apply`; until then the pricing page
     * falls back to plain CTA.
     */
    'pricing' => [
        'annual_months_free' => 2,
        'trial_days' => 14,
        'currency' => [
            'code' => 'USD',
            'symbol' => '$',
            'label' => 'US Dollar',
        ],
        'tiers' => [
            'starter' => [
                'name' => 'Starter',
                'tagline' => 'Formalise your employee records. Onboarding, listing, and offboarding — nothing more, nothing less.',
                'monthly_usd' => 6,
                'stripe_prices' => [
                    'monthly' => env('STRIPE_PRICE_STARTER_USD_MONTHLY'),
                    'annual'  => env('STRIPE_PRICE_STARTER_USD_ANNUAL'),
                ],
                'headcount_label' => 'Module 1 — Employee Journey',
                'featured' => false,
                'cta' => ['label' => 'Start 14-day trial', 'route' => 'signup.form', 'plan' => 'starter'],
                'modules_included' => ['M1 Employee Journey'],
                'features' => [
                    'Employee records with document vault',
                    'Structured onboarding (invite → register → probation)',
                    'Offboarding workflow + clearance checklist',
                    'Org chart & reporting lines',
                    'Role-based access + 2FA',
                    'AI · Human Partnerships assistant (200 messages/mo)',
                    'Email support — 1 business day response',
                ],
                'excluded' => [
                    'Asset inventory (Growth tier)',
                    'Accounting (Growth tier)',
                    'Leave, payroll & attendance (Scale tier)',
                ],
            ],
            'growth' => [
                'name' => 'Growth',
                'tagline' => 'Employee records plus IT assets plus full accounting — one backbone for operations and finance.',
                'monthly_usd' => 14,
                'stripe_prices' => [
                    'monthly' => env('STRIPE_PRICE_GROWTH_USD_MONTHLY'),
                    'annual'  => env('STRIPE_PRICE_GROWTH_USD_ANNUAL'),
                ],
                'headcount_label' => 'Modules 1 + 2 + 4',
                'featured' => true,
                'badge' => 'Most popular',
                'cta' => ['label' => 'Start 14-day trial', 'route' => 'signup.form', 'plan' => 'growth'],
                'modules_included' => ['M1 Employee Journey', 'M2 Asset Management', 'M4 Finance'],
                'features' => [
                    'Everything in Starter',
                    'IT asset inventory with AARF acknowledgement',
                    'IT offboarding checklist generated from assigned assets',
                    'Software licence seat tracking & expiry alerts',
                    'Full accounting — Chart of Accounts, GL, AR/AP, budgets, tax returns',
                    'AI invoice scanning & auto-reconciliation',
                    'AI assistant (1,000 messages/mo)',
                    'Priority email support — 4 business-hour response',
                ],
                'excluded' => [
                    'Leave, attendance, payroll & EA forms (Scale tier)',
                    'Expense claims workflow (Scale tier)',
                    'SSO (SAML / OIDC) (Enterprise tier)',
                ],
            ],
            'scale' => [
                'name' => 'Scale',
                'tagline' => 'The complete EIAAW Workforce platform — Employee Journey, Assets, HRM, and Finance on one backbone.',
                'monthly_usd' => 29,
                'stripe_prices' => [
                    'monthly' => env('STRIPE_PRICE_SCALE_USD_MONTHLY'),
                    'annual'  => env('STRIPE_PRICE_SCALE_USD_ANNUAL'),
                ],
                'headcount_label' => 'All four modules',
                'featured' => false,
                'cta' => ['label' => 'Start 14-day trial', 'route' => 'signup.form', 'plan' => 'scale'],
                'modules_included' => ['M1 Employee Journey', 'M2 Asset Management', 'M3 HRM', 'M4 Finance'],
                'features' => [
                    'Everything in Growth',
                    'Leave workflow — apply, approve, balances, entitlements',
                    'Attendance & timesheet',
                    'Expense claims (eClaim) with multi-step approvals',
                    'Advanced payroll — EPF, SOCSO, EIS, PCB',
                    'Payslip delivery & EA form export (LHDN-ready)',
                    'Claim → ledger auto-posting',
                    'Anomaly detection on claims, attendance, payroll',
                    'AI assistant (5,000 messages/mo, financial-close copilot)',
                    'Slack-channel support — 2 business-hour response',
                ],
                'excluded' => [
                    'SSO (SAML / OIDC) — Enterprise only',
                    'Dedicated database — Enterprise only',
                    'Audit log export to SIEM — Enterprise only',
                ],
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'tagline' => 'For groups, regulated industries, and sovereign-data needs. Everything in Scale plus enterprise controls.',
                'monthly_usd' => null,
                'stripe_prices' => ['monthly' => null, 'annual' => null],
                'price_label' => 'Custom',
                'headcount_label' => '500+ employees or regulated sector',
                'featured' => false,
                'cta' => ['label' => 'Contact sales', 'mailto' => true],
                'modules_included' => ['All four modules', 'Enterprise extensions'],
                'features' => [
                    'Everything in Scale',
                    'SAML 2.0 + OIDC SSO',
                    'SCIM 2.0 user provisioning',
                    'Dedicated Postgres database (isolated from shared pool)',
                    'Audit log export to your SIEM',
                    'Custom DPA, data residency, and BCP addenda',
                    'Named customer success manager',
                    'Annual invoicing · 99.9% uptime SLA',
                ],
                'excluded' => [],
            ],
        ],
    ],

];
