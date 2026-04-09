<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('kwsp_number', 100)->nullable()->after('phone');
            $table->string('tin_number', 100)->nullable()->after('kwsp_number');
            $table->string('socso_number', 100)->nullable()->after('tin_number');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['kwsp_number', 'tin_number', 'socso_number']);
        });
    }
};
