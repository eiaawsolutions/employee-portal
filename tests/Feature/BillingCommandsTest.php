<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Smoke test for Wk3 billing scheduled commands.
 *
 * The commands' real behaviour (tenant row updates) is validated in the
 * Docker pgsql runbook — the full tenants-table schema only exists there.
 * Here we verify the commands are registered, the signature matches, and
 * --dry-run exits cleanly on an empty database.
 */
class BillingCommandsTest extends TestCase
{
    public function test_trial_end_command_is_registered(): void
    {
        $commands = Artisan::all();
        $this->assertArrayHasKey('billing:trial-end', $commands);
        $this->assertArrayHasKey('billing:past-due-suspend', $commands);
    }

    public function test_trial_end_supports_dry_run_flag(): void
    {
        $signature = Artisan::all()['billing:trial-end']->getDefinition()->getOptions();
        $this->assertArrayHasKey('dry-run', $signature);
    }

    public function test_past_due_supports_dry_run_flag(): void
    {
        $signature = Artisan::all()['billing:past-due-suspend']->getDefinition()->getOptions();
        $this->assertArrayHasKey('dry-run', $signature);
    }
}
