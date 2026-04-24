<?php

use App\Support\TenancyMigration;

/**
 * Session 4 — Batch retrofit: Accounting module (36 tables).
 *
 * The accounting module is the largest single block. Every acc_* table is
 * tenant-scoped because financial data is the most sensitive — leakage here
 * would be catastrophic.
 *
 * Order rationale (parent → child):
 *   1. Master/config tables with no parent (chart of accounts, tax codes,
 *      fiscal years, currencies, customers, vendors, bank accounts, settings)
 *   2. First-level transactional tables that depend only on master data
 *      (journal entries, sales invoices, bills, purchase orders, fixed assets,
 *      bank transactions, recurring templates, audit trail)
 *   3. Line items and allocations (children of transactional)
 *   4. Reconciliations, payments, transfers (children of bank accounts)
 *   5. Budgets + tax returns + asset depreciation (mid-level transactional)
 *   6. Their line items and entries
 *   7. AI assist tables (chat sessions, chat messages, invoice scans)
 *
 * On a fresh PG SaaS deploy every acc_* table is empty; orphan-delete on
 * step 3 is a no-op. Per-tenant accounting config (chart of accounts, tax
 * codes, fiscal year) gets seeded by the tenant onboarding flow in Wk2.
 */
return new class extends TenancyMigration {
    protected array $tables = [
        // ── 1. Master/config tables — no parent backfill ───────────────────
        ['table' => 'acc_currencies',                    'parent_via' => null,                       'parent_table' => null,                       'parent_pk' => null],
        ['table' => 'acc_chart_of_accounts',             'parent_via' => null,                       'parent_table' => null,                       'parent_pk' => null],
        ['table' => 'acc_tax_codes',                     'parent_via' => null,                       'parent_table' => null,                       'parent_pk' => null],
        ['table' => 'acc_fiscal_years',                  'parent_via' => null,                       'parent_table' => null,                       'parent_pk' => null],
        ['table' => 'acc_fiscal_periods',                'parent_via' => 'fiscal_year_id',           'parent_table' => 'acc_fiscal_years',         'parent_pk' => 'id'],
        ['table' => 'acc_customers',                     'parent_via' => null,                       'parent_table' => null,                       'parent_pk' => null],
        ['table' => 'acc_vendors',                       'parent_via' => null,                       'parent_table' => null,                       'parent_pk' => null],
        ['table' => 'acc_bank_accounts',                 'parent_via' => 'chart_of_account_id',      'parent_table' => 'acc_chart_of_accounts',    'parent_pk' => 'id'],
        ['table' => 'acc_settings',                      'parent_via' => null,                       'parent_table' => null,                       'parent_pk' => null],
        ['table' => 'acc_fixed_asset_categories',        'parent_via' => 'asset_account_id',         'parent_table' => 'acc_chart_of_accounts',    'parent_pk' => 'id'],

        // ── 2. First-level transactional ────────────────────────────────────
        ['table' => 'acc_journal_entries',               'parent_via' => 'created_by',               'parent_table' => 'users',                    'parent_pk' => 'id'],
        ['table' => 'acc_sales_invoices',                'parent_via' => 'customer_id',              'parent_table' => 'acc_customers',            'parent_pk' => 'id'],
        ['table' => 'acc_bills',                         'parent_via' => 'vendor_id',                'parent_table' => 'acc_vendors',              'parent_pk' => 'id'],
        ['table' => 'acc_purchase_orders',               'parent_via' => 'vendor_id',                'parent_table' => 'acc_vendors',              'parent_pk' => 'id'],
        ['table' => 'acc_fixed_assets',                  'parent_via' => 'category_id',              'parent_table' => 'acc_fixed_asset_categories','parent_pk' => 'id'],
        ['table' => 'acc_bank_transactions',             'parent_via' => 'bank_account_id',          'parent_table' => 'acc_bank_accounts',        'parent_pk' => 'id'],
        ['table' => 'acc_recurring_templates',           'parent_via' => 'created_by',               'parent_table' => 'users',                    'parent_pk' => 'id'],
        ['table' => 'acc_audit_trail',                   'parent_via' => 'user_id',                  'parent_table' => 'users',                    'parent_pk' => 'id'],
        ['table' => 'acc_tax_returns',                   'parent_via' => 'created_by',               'parent_table' => 'users',                    'parent_pk' => 'id'],
        ['table' => 'acc_credit_notes',                  'parent_via' => 'customer_id',              'parent_table' => 'acc_customers',            'parent_pk' => 'id'],
        ['table' => 'acc_budgets',                       'parent_via' => 'fiscal_year_id',           'parent_table' => 'acc_fiscal_years',         'parent_pk' => 'id'],
        ['table' => 'acc_bank_reconciliations',          'parent_via' => 'bank_account_id',          'parent_table' => 'acc_bank_accounts',        'parent_pk' => 'id'],
        ['table' => 'acc_bank_transfers',                'parent_via' => 'from_bank_account_id',     'parent_table' => 'acc_bank_accounts',        'parent_pk' => 'id'],

        // ── 3. Payments (children of customers/vendors + bank accounts) ────
        ['table' => 'acc_customer_payments',             'parent_via' => 'customer_id',              'parent_table' => 'acc_customers',            'parent_pk' => 'id'],
        ['table' => 'acc_vendor_payments',               'parent_via' => 'vendor_id',                'parent_table' => 'acc_vendors',              'parent_pk' => 'id'],

        // ── 4. Line items + allocations + entries (deepest children) ───────
        ['table' => 'acc_journal_entry_lines',           'parent_via' => 'journal_entry_id',         'parent_table' => 'acc_journal_entries',      'parent_pk' => 'id'],
        ['table' => 'acc_sales_invoice_items',           'parent_via' => 'sales_invoice_id',         'parent_table' => 'acc_sales_invoices',       'parent_pk' => 'id'],
        ['table' => 'acc_bill_items',                    'parent_via' => 'bill_id',                  'parent_table' => 'acc_bills',                'parent_pk' => 'id'],
        ['table' => 'acc_purchase_order_items',          'parent_via' => 'purchase_order_id',        'parent_table' => 'acc_purchase_orders',      'parent_pk' => 'id'],
        ['table' => 'acc_credit_note_items',             'parent_via' => 'credit_note_id',           'parent_table' => 'acc_credit_notes',         'parent_pk' => 'id'],
        ['table' => 'acc_customer_payment_allocations',  'parent_via' => 'customer_payment_id',      'parent_table' => 'acc_customer_payments',    'parent_pk' => 'id'],
        ['table' => 'acc_vendor_payment_allocations',    'parent_via' => 'vendor_payment_id',        'parent_table' => 'acc_vendor_payments',      'parent_pk' => 'id'],
        ['table' => 'acc_budget_lines',                  'parent_via' => 'budget_id',                'parent_table' => 'acc_budgets',              'parent_pk' => 'id'],
        ['table' => 'acc_tax_return_lines',              'parent_via' => 'tax_return_id',            'parent_table' => 'acc_tax_returns',          'parent_pk' => 'id'],
        ['table' => 'acc_asset_depreciation_entries',    'parent_via' => 'fixed_asset_id',           'parent_table' => 'acc_fixed_assets',         'parent_pk' => 'id'],

        // ── 5. AI assist (chat + invoice scan) ─────────────────────────────
        ['table' => 'acc_ai_chat_sessions',              'parent_via' => 'user_id',                  'parent_table' => 'users',                    'parent_pk' => 'id'],
        ['table' => 'acc_ai_chat_messages',              'parent_via' => 'session_id',               'parent_table' => 'acc_ai_chat_sessions',     'parent_pk' => 'id'],
        ['table' => 'acc_ai_invoice_scans',              'parent_via' => 'created_by',               'parent_table' => 'users',                    'parent_pk' => 'id'],
    ];
};
