<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Birthday-card idempotency flag — prevents the everyMinute cron from
 * sending the same employee a second e-card on the same calendar year.
 * SendBirthdayWishes sets this to the current year after dispatch.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        if (Schema::hasColumn('employees', 'birthday_email_sent_year')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->smallInteger('birthday_email_sent_year')->nullable()->after('date_of_birth');
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        if (!Schema::hasColumn('employees', 'birthday_email_sent_year')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('birthday_email_sent_year');
        });
    }
};
