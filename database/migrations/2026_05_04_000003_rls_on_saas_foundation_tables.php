<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Apply RLS to the 4 SaaS-foundation tables created in Session 2 BEFORE the
 * tenancy retrofit pattern was established. They each have a tenant_id column
 * but were missing ENABLE ROW LEVEL SECURITY + policy.
 *
 * Tables affected:
 *   tenant_users          (pivot: tenant × user with role)
 *   ai_conversations      (per-tenant LLM transcript log)
 *   ai_usage_daily        (per-tenant token-cost daily roll-up)
 *   subscription_events   (Stripe webhook log)
 *
 * The tenants table itself does NOT get RLS — it's looked up by ResolveTenant
 * middleware BEFORE any tenant context exists (chicken-and-egg). Cross-tenant
 * read of the tenants list is gated at the application layer (admin-only).
 *
 * Postgres-only.
 */
return new class extends Migration {
    private array $tables = [
        'tenant_users',
        'ai_conversations',
        'ai_usage_daily',
        'subscription_events',
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tables as $table) {
            DB::statement("ALTER TABLE \"{$table}\" ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE \"{$table}\" FORCE ROW LEVEL SECURITY");
            DB::statement(<<<SQL
                CREATE POLICY {$table}_tenant_isolation ON "{$table}"
                    USING (tenant_id = eiaaw_current_tenant_id())
                    WITH CHECK (tenant_id = eiaaw_current_tenant_id())
            SQL);
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (array_reverse($this->tables) as $table) {
            DB::statement("DROP POLICY IF EXISTS {$table}_tenant_isolation ON \"{$table}\"");
            DB::statement("ALTER TABLE \"{$table}\" DISABLE ROW LEVEL SECURITY");
        }
    }
};
