<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * password_reset_tokens — tenant scoping.
 *
 * The default Laravel schema makes email the PK, which assumes a single
 * tenant per app. With per-tenant emails (composite users.tenant_id+work_email
 * unique applied in Session 3), two tenants both resetting admin@example.com
 * would collide on this table.
 *
 * Fix:
 *   - Add tenant_id (NOT NULL after backfill from any existing rows)
 *   - Drop email PK, add (tenant_id, email) composite PK
 *   - Enable Postgres RLS so the password broker SELECT/DELETE/INSERT only
 *     sees the current tenant's tokens
 *
 * The password broker is overridden in App\Providers\AuthServiceProvider
 * so its DatabaseTokenRepository injects tenant_id into INSERT/SELECT/DELETE.
 *
 * No backfill source on a fresh SaaS deploy (table is transient — tokens
 * expire after 60 minutes per config/auth.php). Existing rows on a migrated
 * Claritas would be deleted (orphan-delete pattern from TenancyMigration).
 *
 * Postgres-only.
 */
return new class extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // 1. Add tenant_id, nullable initially
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('email');
            $table->index('tenant_id');
        });

        // 2. Delete orphans (no row should be NULL on a fresh deploy)
        DB::table('password_reset_tokens')->whereNull('tenant_id')->delete();

        // 3. NOT NULL + FK to tenants
        DB::statement('ALTER TABLE "password_reset_tokens" ALTER COLUMN "tenant_id" SET NOT NULL');
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // 4. Drop the old email-only PK and add composite (tenant_id, email)
        DB::statement('ALTER TABLE "password_reset_tokens" DROP CONSTRAINT password_reset_tokens_pkey');
        DB::statement('ALTER TABLE "password_reset_tokens" ADD PRIMARY KEY (tenant_id, email)');

        // 5. RLS — uses the standard helper installed in 2026_05_02_000001
        DB::statement('ALTER TABLE "password_reset_tokens" ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE "password_reset_tokens" FORCE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY password_reset_tokens_tenant_isolation ON "password_reset_tokens"
                USING (tenant_id = eiaaw_current_tenant_id())
                WITH CHECK (tenant_id = eiaaw_current_tenant_id())
        SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP POLICY IF EXISTS password_reset_tokens_tenant_isolation ON "password_reset_tokens"');
        DB::statement('ALTER TABLE "password_reset_tokens" DISABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE "password_reset_tokens" DROP CONSTRAINT password_reset_tokens_pkey');
        DB::statement('ALTER TABLE "password_reset_tokens" ADD PRIMARY KEY (email)');
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
