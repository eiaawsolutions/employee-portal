<?php

namespace App\Support;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TenancyMigration — base for batch migrations that add tenant_id + Postgres RLS
 * to a list of existing tables.
 *
 * Subclasses define $tables as an array of specs:
 *   [
 *       'table' => 'foo',
 *       'parent_via' => 'foo_owner_id' | null,   // null = no backfill source
 *       'parent_table' => 'users' | null,
 *       'parent_pk' => 'id' | null,
 *   ]
 *
 * For each spec the migration:
 *   1. Adds tenant_id (nullable initially) + index
 *   2. Backfills tenant_id from the parent table when parent_via is set
 *   3. Deletes any rows still NULL after backfill (orphans)
 *   4. Sets NOT NULL + adds FK to tenants(id) ON DELETE CASCADE
 *   5. Enables + FORCE row-level security
 *   6. Creates the standard tenant-isolation policy that uses
 *      the eiaaw_current_tenant_id() helper installed in
 *      2026_05_02_000001_tenancy_users_and_helpers.
 *
 * On MySQL this is a no-op (RLS doesn't exist; the SaaS schema is
 * Postgres-only).
 */
abstract class TenancyMigration extends Migration
{
    /** @var array<int, array{table:string, parent_via:?string, parent_table:?string, parent_pk:?string}> */
    protected array $tables = [];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tables as $spec) {
            $this->retrofit(
                $spec['table'],
                $spec['parent_via'],
                $spec['parent_table'],
                $spec['parent_pk'],
            );
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (array_reverse($this->tables) as $spec) {
            $this->unwind($spec['table']);
        }
    }

    protected function retrofit(
        string $table,
        ?string $parentVia,
        ?string $parentTable,
        ?string $parentPk,
    ): void {
        Schema::table($table, function (Blueprint $t) {
            $t->unsignedBigInteger('tenant_id')->nullable()->after('id');
            $t->index('tenant_id');
        });

        if ($parentVia && $parentTable && $parentPk) {
            DB::statement(<<<SQL
                UPDATE "{$table}" AS t
                   SET tenant_id = p.tenant_id
                  FROM "{$parentTable}" AS p
                 WHERE t.{$parentVia} = p.{$parentPk}
                   AND t.tenant_id IS NULL
            SQL);
        }

        $orphans = DB::table($table)->whereNull('tenant_id')->count();
        if ($orphans > 0) {
            DB::table($table)->whereNull('tenant_id')->delete();
        }

        DB::statement("ALTER TABLE \"{$table}\" ALTER COLUMN \"tenant_id\" SET NOT NULL");
        Schema::table($table, function (Blueprint $t) {
            $t->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        DB::statement("ALTER TABLE \"{$table}\" ENABLE ROW LEVEL SECURITY");
        DB::statement("ALTER TABLE \"{$table}\" FORCE ROW LEVEL SECURITY");
        DB::statement(<<<SQL
            CREATE POLICY {$table}_tenant_isolation ON "{$table}"
                USING (tenant_id = eiaaw_current_tenant_id())
                WITH CHECK (tenant_id = eiaaw_current_tenant_id())
        SQL);
    }

    protected function unwind(string $table): void
    {
        DB::statement("DROP POLICY IF EXISTS {$table}_tenant_isolation ON \"{$table}\"");
        DB::statement("ALTER TABLE \"{$table}\" DISABLE ROW LEVEL SECURITY");
        Schema::table($table, function (Blueprint $t) {
            $t->dropForeign(['tenant_id']);
            $t->dropIndex(['tenant_id']);
            $t->dropColumn('tenant_id');
        });
    }
}
