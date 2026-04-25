<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cashier subscriptions table — owned by tenants, not users.
 *
 * Modified from the published Cashier default to:
 *   - rename user_id → tenant_id (each tenant has its own subscription)
 *   - add tenant_id FK + standard RLS policy via the eiaaw_current_tenant_id() helper
 *
 * Cashier's Subscription model needs `Cashier::useUserModelKey('tenant_id')`
 * — set in App\Providers\CashierServiceProvider (created in Session 5).
 *
 * Postgres-only (the SaaS subscriptions table doesn't exist on legacy MySQL).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('stripe_id')->unique();
            $table->string('stripe_status');
            $table->string('stripe_price')->nullable();
            $table->integer('quantity')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'stripe_status']);
        });

        // RLS: subscriptions are tenant-scoped — only the tenant owning the
        // subscription can SELECT/UPDATE/DELETE its rows.
        DB::statement('ALTER TABLE "subscriptions" ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE "subscriptions" FORCE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY subscriptions_tenant_isolation ON "subscriptions"
                USING (tenant_id = eiaaw_current_tenant_id())
                WITH CHECK (tenant_id = eiaaw_current_tenant_id())
        SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP POLICY IF EXISTS subscriptions_tenant_isolation ON "subscriptions"');
        Schema::dropIfExists('subscriptions');
    }
};
