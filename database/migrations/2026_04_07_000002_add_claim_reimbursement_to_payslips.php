<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->decimal('claim_reimbursement', 12, 2)->default(0)->after('overtime_amount')
                ->comment('Approved expense claim reimbursement — excluded from statutory calculations');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn('claim_reimbursement');
        });
    }
};
