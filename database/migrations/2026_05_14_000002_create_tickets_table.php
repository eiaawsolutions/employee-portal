<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tickets table — the help-desk core. Consolidates what Claritas evolved
 * across 5 migrations (create + expand_departments + last_reminder +
 * status_lifecycle_redesign + assigned_at) into one Postgres-native schema.
 *
 * Multi-company routing (service_company_id / source_company_id) is
 * intentionally NOT included — see docs/PLAN-CLARITAS-REPLICATION.md §0.
 * Tickets are tenant-scoped; within a tenant they're owned by one company.
 *
 * Status lifecycle:
 *   Open         — raised, no PIC
 *   In Progress  — PIC assigned
 *   Pending      — idle 24h+ (auto-set by tickets:remind-stale)
 *   Resolved     — terminal, marked solved
 *   Closed       — terminal, manually closed without resolution
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('ticket_number', 20);
            $table->unsignedBigInteger('user_id')->comment('Creator');
            $table->unsignedBigInteger('company_id')->nullable()->comment('Raiser company');
            $table->unsignedBigInteger('assigned_to')->nullable()->comment('PIC');
            $table->timestamp('assigned_at')->nullable();
            $table->string('department', 50);
            $table->string('priority', 10)->default('Medium');
            $table->string('status', 20)->default('Open');
            $table->string('subject', 255);
            $table->text('description');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'department', 'status']);
            $table->index(['tenant_id', 'user_id']);
            $table->index('assigned_to');
            $table->index('assigned_at');

            // ticket_number is unique within a tenant (TIC-2026-0001 per tenant).
            $table->unique(['tenant_id', 'ticket_number']);

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
        });

        // CHECK constraints replace native ENUM — easier to evolve without
        // ALTER TYPE rituals. Values match Ticket::PRIORITIES / STATUSES.
        DB::statement(<<<'SQL'
            ALTER TABLE "tickets" ADD CONSTRAINT tickets_priority_check
                CHECK (priority IN ('Low', 'Medium', 'High', 'Urgent'))
        SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE "tickets" ADD CONSTRAINT tickets_status_check
                CHECK (status IN ('Open', 'In Progress', 'Pending', 'Resolved', 'Closed'))
        SQL);

        DB::statement('ALTER TABLE "tickets" ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE "tickets" FORCE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY tickets_tenant_isolation ON "tickets"
                USING (tenant_id = eiaaw_current_tenant_id())
                WITH CHECK (tenant_id = eiaaw_current_tenant_id())
        SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP POLICY IF EXISTS tickets_tenant_isolation ON "tickets"');
        DB::statement('ALTER TABLE "tickets" DISABLE ROW LEVEL SECURITY');
        Schema::dropIfExists('tickets');
    }
};
