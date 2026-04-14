<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_details', function (Blueprint $table) {
            $table->date('confirmation_date')->nullable()->after('last_salary_date');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->date('confirmation_date')->nullable()->after('last_salary_date');
        });
    }

    public function down(): void
    {
        Schema::table('work_details', function (Blueprint $table) {
            $table->dropColumn('confirmation_date');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('confirmation_date');
        });
    }
};
