<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ticket_message_attachments — per-message multi-file attachments.
 * Legacy single-attachment columns on ticket_messages are kept for old data;
 * new messages use this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::create('ticket_message_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('message_id');
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime', 120);
            $table->unsignedBigInteger('size');
            $table->boolean('is_image')->default(false);
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('message_id');

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('message_id')->references('id')->on('ticket_messages')->onDelete('cascade');
        });

        DB::statement('ALTER TABLE "ticket_message_attachments" ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE "ticket_message_attachments" FORCE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY ticket_message_attachments_tenant_isolation ON "ticket_message_attachments"
                USING (tenant_id = eiaaw_current_tenant_id())
                WITH CHECK (tenant_id = eiaaw_current_tenant_id())
        SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP POLICY IF EXISTS ticket_message_attachments_tenant_isolation ON "ticket_message_attachments"');
        DB::statement('ALTER TABLE "ticket_message_attachments" DISABLE ROW LEVEL SECURITY');
        Schema::dropIfExists('ticket_message_attachments');
    }
};
