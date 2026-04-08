<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_inventories', function (Blueprint $table) {
            $table->string('company_supplied_to')->nullable()->after('company_name');
        });
    }

    public function down(): void
    {
        Schema::table('asset_inventories', function (Blueprint $table) {
            $table->dropColumn('company_supplied_to');
        });
    }
};
