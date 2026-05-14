<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ticket_edit_logs — audit trail for the Edit Department action on tickets.
 * Surfaced on the ticket detail page (gated to superadmin / system_admin).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::create('ticket_edit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('edited_by_user_id');
            $table->json('changes');  // Postgres maps json() to jsonb implicitly via Laravel grammar
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'ticket_id', 'created_at']);

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('edited_by_user_id')->references('id')->on('users')->onDelete('cascade');
        });

        DB::statement('ALTER TABLE "ticket_edit_logs" ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE "ticket_edit_logs" FORCE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY ticket_edit_logs_tenant_isolation ON "ticket_edit_logs"
                USING (tenant_id = eiaaw_current_tenant_id())
                WITH CHECK (tenant_id = eiaaw_current_tenant_id())
        SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP POLICY IF EXISTS ticket_edit_logs_tenant_isolation ON "ticket_edit_logs"');
        DB::statement('ALTER TABLE "ticket_edit_logs" DISABLE ROW LEVEL SECURITY');
        Schema::dropIfExists('ticket_edit_logs');
    }
};
