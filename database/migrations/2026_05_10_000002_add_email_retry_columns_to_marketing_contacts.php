<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add retry/failure-tracking columns to marketing_contacts.
 *
 * The contact form is a revenue-critical surface (every row is a potential
 * customer). We never want to lose one to a transient SMTP failure. After
 * this migration:
 *   - emailed_at      — set when sales notification was successfully sent
 *   - email_attempts  — count of attempts (initial + retries from cron sweep)
 *   - email_failed_at — last failure timestamp; cleared on success
 *   - email_last_error — short reason for the most recent failure (debugging)
 *
 * The marketing:retry-pending-emails command sweeps rows where
 * emailed_at IS NULL AND email_attempts < 5 every 10 minutes.
 */
return new class extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('marketing_contacts', function (Blueprint $table) {
            $table->unsignedSmallInteger('email_attempts')->default(0)->after('emailed_at');
            $table->timestamp('email_failed_at')->nullable()->after('email_attempts');
            $table->string('email_last_error', 240)->nullable()->after('email_failed_at');

            // Index for the retry sweep — covers the predicate
            //   WHERE emailed_at IS NULL AND email_attempts < 5
            $table->index(['emailed_at', 'email_attempts'], 'mc_retry_sweep_idx');
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('marketing_contacts', function (Blueprint $table) {
            $table->dropIndex('mc_retry_sweep_idx');
            $table->dropColumn(['email_attempts', 'email_failed_at', 'email_last_error']);
        });
    }
};
