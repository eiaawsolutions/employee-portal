<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Laravel database-channel notifications table. UUID id + morphs notifiable.
 *
 * Tenant-scoped via tenant_id + Postgres RLS. Notifiable here is always a
 * User (Laravel notifications carry data only, not relationships), and the
 * User is tenant-scoped, so notifications inherit the tenant boundary.
 * Adding tenant_id explicitly makes the RLS policy uniform with every
 * other tenant table.
 *
 * Postgres-only — matches the rest of the EP SaaS schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id');
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['notifiable_id', 'notifiable_type', 'read_at']);

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        DB::statement('ALTER TABLE "notifications" ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE "notifications" FORCE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY notifications_tenant_isolation ON "notifications"
                USING (tenant_id = eiaaw_current_tenant_id())
                WITH CHECK (tenant_id = eiaaw_current_tenant_id())
        SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP POLICY IF EXISTS notifications_tenant_isolation ON "notifications"');
        DB::statement('ALTER TABLE "notifications" DISABLE ROW LEVEL SECURITY');
        Schema::dropIfExists('notifications');
    }
};
