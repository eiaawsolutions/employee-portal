<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add receipt fingerprint for duplicate detection
        Schema::table('expense_claim_items', function (Blueprint $table) {
            $table->string('receipt_hash', 64)->nullable()->after('receipt_path')
                  ->comment('SHA-256 hash of receipt file for duplicate detection');
            $table->index('receipt_hash');
        });

        // Drop the unique constraint on employee+year+month to allow flexible date claims
        // Must add a plain index on employee_id first, since the FK uses the unique index as backing
        Schema::table('expense_claims', function (Blueprint $table) {
            $table->index('employee_id', 'expense_claims_employee_id_index');
            $table->dropUnique(['employee_id', 'year', 'month']);
            // Re-add as non-unique composite index for query performance
            $table->index(['employee_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::table('expense_claim_items', function (Blueprint $table) {
            $table->dropIndex(['receipt_hash']);
            $table->dropColumn('receipt_hash');
        });

        Schema::table('expense_claims', function (Blueprint $table) {
            $table->dropIndex(['employee_id', 'year', 'month']);
            $table->unique(['employee_id', 'year', 'month']);
            $table->dropIndex('expense_claims_employee_id_index');
        });
    }
};
