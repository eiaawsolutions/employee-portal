<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_details', function (Blueprint $table) {
            $table->string('employee_number', 50)->nullable()->after('onboarding_id');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('employee_number', 50)->nullable()->after('active_until');
        });
    }

    public function down(): void
    {
        Schema::table('work_details', function (Blueprint $table) {
            $table->dropColumn('employee_number');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('employee_number');
        });
    }
};
