<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cashier 16 customer columns on the tenants table.
 *
 * Cashier expects:
 *   - stripe_id        (the Stripe customer ID)
 *   - pm_type          (payment method type, e.g. 'card')
 *   - pm_last_four     (last 4 digits of the card)
 *   - trial_ends_at    (already on tenants from Session 2)
 *
 * Session 2's tenants table has stripe_customer_id (logical name we kept for
 * future-readability). We KEEP that column as the canonical identifier and
 * ALSO add Cashier's `stripe_id` as an alias — wired via a model accessor in
 * Tenant.php so reads/writes stay consistent.
 *
 * Postgres-only.
 */
return new class extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            // Cashier-required columns (stripe_id is the Stripe customer reference)
            if (!Schema::hasColumn('tenants', 'stripe_id')) {
                $table->string('stripe_id')->nullable()->after('stripe_customer_id');
                $table->index('stripe_id');
            }
            $table->string('pm_type')->nullable()->after('stripe_id');
            $table->string('pm_last_four', 4)->nullable()->after('pm_type');
            // trial_ends_at already exists from Session 2 — Cashier reads it as-is.
        });

        // Backfill stripe_id from any pre-existing stripe_customer_id values
        // (no-op on a fresh deploy).
        DB::statement('UPDATE "tenants" SET stripe_id = stripe_customer_id WHERE stripe_customer_id IS NOT NULL AND stripe_id IS NULL');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['stripe_id']);
            $table->dropColumn(['stripe_id', 'pm_type', 'pm_last_four']);
        });
    }
};
