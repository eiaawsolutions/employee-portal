<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Disable RLS on subscription_events.
 *
 * This table is a Stripe webhook log. Webhooks arrive from Stripe with no
 * tenant context (we resolve the tenant from the payload customer ID), so a
 * tenant-isolation policy would block the webhook handler from writing
 * before it knows which tenant the event belongs to.
 *
 * The table stays tenant-tagged (tenant_id column is preserved) for
 * downstream filtering — e.g., tenant admin "billing history" UI does
 * SubscriptionEvent::where('tenant_id', current_tenant_id) explicitly.
 *
 * Cross-tenant access is restricted at the controller level (only platform
 * admins can read the global feed; tenant admins see filtered queries).
 *
 * Postgres-only.
 */
return new class extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP POLICY IF EXISTS subscription_events_tenant_isolation ON "subscription_events"');
        DB::statement('ALTER TABLE "subscription_events" DISABLE ROW LEVEL SECURITY');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE "subscription_events" ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE "subscription_events" FORCE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY subscription_events_tenant_isolation ON "subscription_events"
                USING (tenant_id = eiaaw_current_tenant_id())
                WITH CHECK (tenant_id = eiaaw_current_tenant_id())
        SQL);
    }
};
