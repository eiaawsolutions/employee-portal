<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add `canceled_at` to tenants.
 *
 * `status = 'canceled'` tracks the cancellation state; `canceled_at` records
 * WHEN so the deletion job (billing:delete-canceled) can apply the 30-day
 * read-only grace promised in the Terms of Service before hard-deleting
 * tenant data.
 *
 * Not to be confused with SoftDeletes' `deleted_at` — that flag only hides
 * the row from queries. `canceled_at` is the Stripe subscription termination
 * timestamp; hard deletion (the SoftDeletes path) happens LATER, after grace.
 *
 * Postgres-only — live MySQL Claritas is untouched.
 */
return new class extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'canceled_at')) {
                $table->timestamp('canceled_at')->nullable()->after('suspended_at');
                $table->index('canceled_at');
            }
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'canceled_at')) {
                $table->dropIndex(['canceled_at']);
                $table->dropColumn('canceled_at');
            }
        });
    }
};
