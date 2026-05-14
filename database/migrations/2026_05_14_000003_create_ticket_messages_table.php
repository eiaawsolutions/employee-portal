<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ticket_messages — chat thread on a ticket.
 *
 * Legacy single-attachment columns (attachment_path / attachment_original_name
 * / attachment_mime) are kept for backward compat with views that render them
 * directly. New messages prefer ticket_message_attachments for multi-file.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('user_id')->comment('Sender');
            $table->text('message')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_original_name')->nullable();
            $table->string('attachment_mime', 120)->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'ticket_id', 'id']);

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        DB::statement('ALTER TABLE "ticket_messages" ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE "ticket_messages" FORCE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY ticket_messages_tenant_isolation ON "ticket_messages"
                USING (tenant_id = eiaaw_current_tenant_id())
                WITH CHECK (tenant_id = eiaaw_current_tenant_id())
        SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP POLICY IF EXISTS ticket_messages_tenant_isolation ON "ticket_messages"');
        DB::statement('ALTER TABLE "ticket_messages" DISABLE ROW LEVEL SECURITY');
        Schema::dropIfExists('ticket_messages');
    }
};
