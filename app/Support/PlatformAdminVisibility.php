<?php

namespace App\Support;

/**
 * PlatformAdminVisibility — the allowlist of what EIAAW HQ staff may see
 * about a tenant workspace, and the explicit denylist of what they may not.
 *
 * THIS FILE IS A LEGAL + OPERATIONAL CONTRACT, NOT A CONVENIENCE.
 *
 * Tenants are paying customers. Their employee data, payroll, leave reasons,
 * AI prompts, file uploads, and accounting records are theirs — not ours.
 * EIAAW staff need enough visibility to run a platform (billing, abuse,
 * support triage, capacity planning, churn signals) but no more.
 *
 * Every HQ-facing query MUST reference one of these arrays. If a new field
 * is needed for a platform feature, add it here with a justification — do
 * not silently widen access by writing the SQL inline.
 *
 * ─────────────────────────────────────────────────────────────────────────
 *  Allowed (metadata + aggregates only)
 * ─────────────────────────────────────────────────────────────────────────
 *  - Tenant identity:   id, slug, name, legal_name, country_code,
 *                       billing_currency, created_at, deleted_at
 *  - Plan + lifecycle:  plan, plan_seats, status, trial_ends_at,
 *                       suspended_at, suspension_reason, canceled_at
 *  - Billing state:     stripe_customer_id, stripe_subscription_id,
 *                       subscription_status, past_due_at, pm_type,
 *                       pm_last_four (last four of card — not full PAN)
 *  - Aggregate counts:  users count, employees count, AI requests count,
 *                       AI tokens sum, AI cost_usd sum, storage MB,
 *                       email volume (30d), DB row count, last-active date
 *  - User metadata:     name, work_email, role, tenant_role, last_login_at
 *                       (so support can confirm who the workspace owner is)
 *
 * ─────────────────────────────────────────────────────────────────────────
 *  Forbidden (tenant business data — never read at HQ)
 * ─────────────────────────────────────────────────────────────────────────
 *  - Employee records: full_name (when not a User), nric, passport, dob,
 *                      address, phone, salary, bank account, MyKad images,
 *                      contracts, certificates, education history,
 *                      spouse/children/emergency contact details
 *  - Payroll:          payslip line items, EA forms, statutory deductions,
 *                      bonuses, gross/net amounts per employee
 *  - Leave / claims:   reasons, attached medical certificates, receipts,
 *                      approval comments
 *  - AI conversations: prompt content, response content, attached files,
 *                      retrieved sources, session transcripts
 *  - Accounting:       invoices, journal entries, GL transactions, vendor
 *                      bills, customer ledger, tax filings
 *  - Files:            anything served by SecureFileController under
 *                      storage/app/private (NRIC, contracts, etc.)
 *  - Audit logs:       full SecurityAuditLog row content (event names and
 *                      counts only — never the `details` payload, which
 *                      may contain employee identifiers)
 *
 * ─────────────────────────────────────────────────────────────────────────
 *  Impersonation
 * ─────────────────────────────────────────────────────────────────────────
 *  HQ DOES NOT impersonate tenant users. There is no "log in as" feature.
 *  If a tenant requests support that needs eyes on their data, the workflow
 *  is: tenant grants explicit consent in-app -> creates a time-boxed support
 *  session token -> HQ uses that token to view inside their RLS scope, with
 *  every read written to the tenant's own audit log under a
 *  `support_session.*` event prefix. That feature is out of scope here and
 *  should never be added without legal review.
 */
class PlatformAdminVisibility
{
    /**
     * Tenant columns that may be SELECTed when listing/showing tenants at HQ.
     * Use as: `Tenant::query()->select(PlatformAdminVisibility::TENANT_FIELDS)`.
     */
    public const TENANT_FIELDS = [
        'id', 'slug', 'name', 'legal_name', 'country_code', 'billing_currency',
        'plan', 'plan_seats', 'status',
        'trial_ends_at', 'suspended_at', 'suspension_reason', 'canceled_at',
        'stripe_customer_id', 'stripe_subscription_id', 'subscription_status',
        'past_due_at', 'pm_type', 'pm_last_four',
        'sso_enabled',
        'created_at', 'updated_at', 'deleted_at',
    ];

    /**
     * User columns that may be SELECTed when listing tenant members at HQ.
     * Use as: `User::query()->select(PlatformAdminVisibility::USER_FIELDS)`.
     */
    public const USER_FIELDS = [
        'id', 'tenant_id', 'name', 'work_email', 'role', 'is_active',
        'last_login_at', 'created_at',
    ];

    /**
     * Aggregates that may be computed across a tenant. Each value is a
     * scalar (count, sum) — no per-row leakage.
     */
    public const ALLOWED_AGGREGATES = [
        'users_count', 'employees_count',
        'ai_requests_30d', 'ai_tokens_30d', 'ai_cost_usd_30d',
        'storage_mb', 'db_row_count_total', 'emails_sent_30d',
        'last_active_at',
    ];

    /**
     * Tables that HQ aggregations may COUNT but never SELECT row content
     * from. Used by the daily usage meter.
     */
    public const COUNTABLE_TENANT_TABLES = [
        'users', 'employees', 'onboardings', 'offboardings',
        'asset_inventories', 'leave_applications', 'expense_claims',
        'attendance_records', 'pay_runs', 'payslips',
        'ai_conversations',
    ];

    /**
     * Reject any callsite that tries to widen access. Defensive — if a
     * future controller writes `$query->addSelect(['notes'])` against a
     * tenant table, this guard makes it review-visible.
     */
    public static function assertFieldAllowed(string $table, string $field): void
    {
        $allowed = match ($table) {
            'tenants' => self::TENANT_FIELDS,
            'users'   => self::USER_FIELDS,
            default   => [],
        };

        if (!in_array($field, $allowed, true)) {
            throw new \LogicException(sprintf(
                'PlatformAdminVisibility: field "%s" on table "%s" is not in the HQ allowlist. ' .
                'If a new platform feature genuinely needs it, add it to PlatformAdminVisibility ' .
                'with a justification — do not bypass.',
                $field,
                $table,
            ));
        }
    }
}
