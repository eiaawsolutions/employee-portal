<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Auth tenancy retrofit.
 *
 *  - Adds users.tenant_id (FK to tenants.id, ON DELETE CASCADE).
 *  - Backfills NULL tenant_id rows to a placeholder "Default Workspace" tenant
 *    (slug 'default') so existing Claritas users (if any rows exist on this PG
 *    deployment) keep working. On a brand-new SaaS deployment, no rows = no-op.
 *  - Drops the global users.work_email unique constraint (Laravel auto-generated
 *    `users_work_email_unique`) and replaces with a composite (tenant_id, work_email)
 *    unique. Two different tenants can now both have admin@example.com.
 *  - Enables Postgres RLS on the users table with a policy that constrains
 *    SELECT/UPDATE/DELETE to the resolved current tenant (set by the
 *    ResolveTenant middleware via SET LOCAL app.tenant_id).
 *  - Installs a session-variable helper function eiaaw_current_tenant_id() that
 *    every RLS policy in the project will use, so the policy SQL stays terse
 *    and the failure mode (no tenant set) returns 0 rather than NULL — which
 *    makes the policy fail-closed instead of fail-open.
 *
 * Postgres-only. The Claritas MySQL deployment is single-tenant by design and
 * doesn't need any of this; this migration is a complete no-op there.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // ── 1. Helper function used by every RLS policy in the project ───────
        // Returns the current request's tenant_id from the Postgres session
        // variable that ResolveTenant middleware sets, or 0 when unset
        // (so RLS fails closed: WHERE tenant_id = 0 matches nothing).
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION eiaaw_current_tenant_id() RETURNS bigint
            LANGUAGE plpgsql STABLE AS $$
            BEGIN
                RETURN COALESCE(
                    NULLIF(current_setting('app.tenant_id', true), '')::bigint,
                    0
                );
            END;
            $$;
        SQL);

        // ── 2. Add users.tenant_id (nullable initially for backfill) ─────────
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            $table->index('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // ── 3. Backfill: ensure a "default" tenant exists for legacy rows ────
        // This branch only does work if there are existing users with NULL tenant_id
        // (i.e. you ran this on a Postgres DB that already had pre-SaaS users).
        // On a fresh SaaS deployment with zero users, both queries are no-ops.
        $orphanCount = (int) DB::table('users')->whereNull('tenant_id')->count();
        if ($orphanCount > 0) {
            $defaultTenantId = DB::table('tenants')
                ->where('slug', 'default')
                ->value('id');

            if (!$defaultTenantId) {
                $defaultTenantId = DB::table('tenants')->insertGetId([
                    'slug' => 'default',
                    'name' => 'Default Workspace',
                    'plan' => 'enterprise',  // unrestricted access for legacy users
                    'plan_seats' => $orphanCount,
                    'status' => 'active',
                    'country_code' => 'MY',
                    'billing_currency' => 'MYR',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('users')
                ->whereNull('tenant_id')
                ->update(['tenant_id' => $defaultTenantId]);
        }

        // ── 4. Now that no NULLs remain, enforce NOT NULL ────────────────────
        DB::statement('ALTER TABLE "users" ALTER COLUMN "tenant_id" SET NOT NULL');

        // ── 5. Swap the unique constraint: (work_email) → (tenant_id, work_email) ─
        // The baseline schema emits unique indexes (CREATE UNIQUE INDEX), not unique
        // CONSTRAINTs (ALTER TABLE ADD CONSTRAINT UNIQUE). Laravel's dropUnique()
        // tries to DROP CONSTRAINT and fails on the index. Use raw DROP INDEX.
        DB::statement('DROP INDEX IF EXISTS users_work_email_unique');
        Schema::table('users', function (Blueprint $table) {
            $table->unique(['tenant_id', 'work_email'], 'users_tenant_id_work_email_unique');
        });

        // ── 6. Enable RLS on users with the standard tenant policy ────────────
        // Note: the table OWNER bypasses RLS unless we also FORCE it. We do
        // FORCE so even our own connection (which is the table owner on Railway
        // managed PG) enforces the policy.
        DB::statement('ALTER TABLE "users" ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE "users" FORCE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY users_tenant_isolation ON "users"
                USING (tenant_id = eiaaw_current_tenant_id())
                WITH CHECK (tenant_id = eiaaw_current_tenant_id());
        SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP POLICY IF EXISTS users_tenant_isolation ON "users"');
        DB::statement('ALTER TABLE "users" DISABLE ROW LEVEL SECURITY');

        DB::statement('DROP INDEX IF EXISTS users_tenant_id_work_email_unique');
        DB::statement('CREATE UNIQUE INDEX users_work_email_unique ON "users" ("work_email")');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        DB::statement('DROP FUNCTION IF EXISTS eiaaw_current_tenant_id()');
    }
};
