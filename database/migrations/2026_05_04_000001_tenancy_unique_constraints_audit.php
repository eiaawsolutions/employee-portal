<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Session 5 — schema-uniqueness audit fixup.
 *
 * The Claritas single-tenant schema has globally-unique constraints on
 * "code" / "number" / "name" columns that need to become (tenant_id, *)
 * composite for SaaS multi-tenancy.
 *
 * Categories:
 *   A. Already-safe — index includes a column whose tenant_id is enforced
 *      via FK chain (e.g. attendance_records.employee_id → employees.tenant_id).
 *      Postgres RLS on the FK target gives us the per-tenant uniqueness
 *      transitively. NO change.
 *
 *   B. Has a "company" string column — Claritas's pre-tenant attempt at
 *      scoping. Replace `company` with `tenant_id` in the index.
 *
 *   C. Globally unique business identifier — should be (tenant_id, *) composite.
 *
 *   D. INTENTIONALLY global — security tokens (acknowledgement_token,
 *      consent_token) and ISO currency codes. NO change. These are looked up
 *      by URL/email outside any tenant context, so global uniqueness is
 *      a feature.
 *
 * Postgres-only.
 */
return new class extends Migration {
    /**
     * Each entry: [table, old_index_name, new_index_columns]
     * The migration drops the old index and creates a new one with the new columns.
     */
    private array $compositeFixes = [
        // ── B. Was (company, *) — replace company with tenant_id ───────────
        ['acc_bills',                 'acc_bills_company_bill_number_unique',                ['tenant_id', 'bill_number']],
        ['acc_chart_of_accounts',     'acc_chart_of_accounts_company_account_code_unique',   ['tenant_id', 'account_code']],
        ['acc_credit_notes',          'acc_credit_notes_company_credit_note_number_unique',  ['tenant_id', 'credit_note_number']],
        ['acc_customer_payments',     'acc_customer_payments_company_payment_number_unique', ['tenant_id', 'payment_number']],
        ['acc_customers',             'acc_customers_company_customer_code_unique',          ['tenant_id', 'customer_code']],
        ['acc_fixed_assets',          'acc_fixed_assets_company_asset_code_unique',          ['tenant_id', 'asset_code']],
        ['acc_journal_entries',       'acc_journal_entries_company_entry_number_unique',     ['tenant_id', 'entry_number']],
        ['acc_purchase_orders',       'acc_purchase_orders_company_po_number_unique',        ['tenant_id', 'po_number']],
        ['acc_sales_invoices',        'acc_sales_invoices_company_invoice_number_unique',    ['tenant_id', 'invoice_number']],
        ['acc_settings',              'acc_settings_company_unique',                         ['tenant_id']],
        ['acc_tax_codes',             'acc_tax_codes_company_code_unique',                   ['tenant_id', 'code']],
        ['acc_vendor_payments',       'acc_vendor_payments_company_payment_number_unique',   ['tenant_id', 'payment_number']],
        ['acc_vendors',               'acc_vendors_company_vendor_code_unique',              ['tenant_id', 'vendor_code']],
        ['pay_runs',                  'pay_runs_company_year_month_unique',                  ['tenant_id', 'year', 'month']],
        ['payroll_configs',           'payroll_configs_company_unique',                      ['tenant_id']],
        ['public_holidays',           'public_holidays_company_date_unique',                 ['tenant_id', 'date']],

        // ── C. Was globally unique — make (tenant_id, *) composite ─────────
        ['asset_inventories',         'asset_inventories_asset_tag_unique',                  ['tenant_id', 'asset_tag']],
        ['expense_categories',        'expense_categories_code_unique',                      ['tenant_id', 'code']],
        ['expense_claims',            'expense_claims_claim_number_unique',                  ['tenant_id', 'claim_number']],
        ['leave_types',               'leave_types_code_unique',                             ['tenant_id', 'code']],
        ['payroll_items',             'payroll_items_code_unique',                           ['tenant_id', 'code']],
        ['payslips',                  'payslips_payslip_number_unique',                      ['tenant_id', 'payslip_number']],
        ['pay_runs',                  'pay_runs_reference_unique',                           ['tenant_id', 'reference']],
        ['aarfs',                     'aarfs_aarf_reference_unique',                         ['tenant_id', 'aarf_reference']],

        // ── employee_child_registrations: was UNIQUE(employee_id) — already
        //     safe (employee_id is per-tenant via FK), but we make it explicit
        //     by adding tenant_id for clarity + perf.
        ['employee_child_registrations', 'employee_child_registrations_employee_id_unique', ['tenant_id', 'employee_id']],
    ];

    /**
     * Indexes intentionally LEFT global. Documented here so a future audit
     * doesn't accidentally "fix" them.
     *
     *   acc_currencies.code           — ISO currency codes ('MYR', 'USD') are
     *                                   intrinsically global.
     *   aarfs.acknowledgement_token   — URL token for public email-link ack.
     *                                   Looked up without tenant context.
     *   employee_edit_logs.consent_token  — same pattern.
     *   onboarding_edit_logs.consent_token — same pattern.
     *
     * Indexes ALREADY safe (no change needed):
     *   attendance_records (employee_id, date)
     *   ea_forms (employee_id, year)
     *   leave_balances (employee_id, leave_type_id, year)
     *   payslips (pay_run_id, employee_id)
     *   user_permissions (user_id, resource)
     *   acc_budget_lines (budget_id, account_id, fiscal_period_id)
     *
     * These rely on transitive uniqueness via FK chain to per-tenant parent rows.
     */

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->compositeFixes as [$table, $oldIndex, $newColumns]) {
            DB::statement("DROP INDEX IF EXISTS \"{$oldIndex}\"");

            $newIndexName = $table . '_' . implode('_', $newColumns) . '_unique';
            $colList = '"' . implode('", "', $newColumns) . '"';

            DB::statement("CREATE UNIQUE INDEX \"{$newIndexName}\" ON \"{$table}\" ({$colList})");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (array_reverse($this->compositeFixes) as [$table, $oldIndex, $newColumns]) {
            $newIndexName = $table . '_' . implode('_', $newColumns) . '_unique';
            DB::statement("DROP INDEX IF EXISTS \"{$newIndexName}\"");
            // We don't restore the old (broken) globally-unique constraints on rollback —
            // doing so could fail if multiple tenants now have colliding values.
        }
    }
};
