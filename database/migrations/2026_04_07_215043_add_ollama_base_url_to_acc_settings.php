<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acc_settings', function (Blueprint $table) {
            $table->string('ollama_base_url', 255)->nullable()->after('ai_model');
        });
    }

    public function down(): void
    {
        Schema::table('acc_settings', function (Blueprint $table) {
            $table->dropColumn('ollama_base_url');
        });
    }
};
