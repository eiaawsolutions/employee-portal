<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cashier 16 added Stripe meter support. Postgres-only because the parent
 * subscription_items table only exists on the SaaS Postgres deployment.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('subscription_items', function (Blueprint $table) {
            $table->string('meter_id')->nullable()->after('stripe_price');
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('subscription_items', function (Blueprint $table) {
            $table->dropColumn('meter_id');
        });
    }
};
