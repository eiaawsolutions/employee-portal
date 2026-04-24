<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add `past_due_at` to tenants.
 *
 * Session 5's Stripe webhook flips `subscription_status = past_due` on a
 * `invoice.payment_failed` event but doesn't record WHEN the transition
 * happened. Session 7's PastDueSuspension scheduled command needs that
 * timestamp to apply the 3-day grace period before suspending.
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
            if (!Schema::hasColumn('tenants', 'past_due_at')) {
                $table->timestamp('past_due_at')->nullable()->after('subscription_status');
                $table->index('past_due_at');
            }
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'past_due_at')) {
                $table->dropIndex(['past_due_at']);
                $table->dropColumn('past_due_at');
            }
        });
    }
};
