<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cashier subscription_items — child of subscriptions.
 *
 * Tenant scoping is INHERITED via subscription_id → subscriptions.tenant_id.
 * The RLS policy joins through the parent rather than duplicating tenant_id
 * on this leaf table.
 *
 * Postgres-only.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::create('subscription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_id')->unique();
            $table->string('stripe_product');
            $table->string('stripe_price');
            $table->integer('quantity')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'stripe_price']);
        });

        // RLS: inherit tenant scope from the parent subscription.
        // The subquery only returns rows where the subscription belongs to
        // the current tenant — Postgres optimizer turns this into an efficient
        // index-only join.
        DB::statement('ALTER TABLE "subscription_items" ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE "subscription_items" FORCE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY subscription_items_tenant_isolation ON "subscription_items"
                USING (
                    subscription_id IN (
                        SELECT id FROM subscriptions WHERE tenant_id = eiaaw_current_tenant_id()
                    )
                )
                WITH CHECK (
                    subscription_id IN (
                        SELECT id FROM subscriptions WHERE tenant_id = eiaaw_current_tenant_id()
                    )
                )
        SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP POLICY IF EXISTS subscription_items_tenant_isolation ON "subscription_items"');
        Schema::dropIfExists('subscription_items');
    }
};
