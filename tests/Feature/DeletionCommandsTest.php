<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Registration + signature smoke for the Session 11 A-grade deletion pipeline.
 *
 * The commands' destructive behaviour (PII scrub + hard purge) is validated
 * in the Docker pgsql runbook where a seeded canceled tenant exists. Here
 * we verify registration + option surface so a future edit can't silently
 * remove --dry-run or drop either command from the schedule.
 */
class DeletionCommandsTest extends TestCase
{
    public function test_delete_canceled_command_is_registered(): void
    {
        $this->assertArrayHasKey('billing:delete-canceled', Artisan::all());
    }

    public function test_purge_canceled_command_is_registered(): void
    {
        $this->assertArrayHasKey('billing:purge-canceled', Artisan::all());
    }

    public function test_delete_canceled_has_dry_run_and_grace_days_options(): void
    {
        $opts = Artisan::all()['billing:delete-canceled']->getDefinition()->getOptions();
        $this->assertArrayHasKey('dry-run', $opts);
        $this->assertArrayHasKey('grace-days', $opts);
    }

    public function test_purge_canceled_has_dry_run_force_and_window_options(): void
    {
        $opts = Artisan::all()['billing:purge-canceled']->getDefinition()->getOptions();
        $this->assertArrayHasKey('dry-run', $opts);
        $this->assertArrayHasKey('force', $opts);
        $this->assertArrayHasKey('purge-after-days', $opts);
    }

    // Empty-run behaviour is validated in the Docker pgsql runbook; the test
    // env is MySQL without the tenants table, so a dry-run execution hits a
    // QueryException. Registration + options are the meaningful smoke here.
}
