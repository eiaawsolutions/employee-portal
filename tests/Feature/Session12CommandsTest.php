<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Registration smoke for Session 12 commands.
 *
 * Behavioural validation (actual rate drift, actual backup restore) runs in
 * the Docker pgsql runbook + against a live sidecar. Here we lock the
 * command surface + options so a future edit can't silently delete them.
 */
class Session12CommandsTest extends TestCase
{
    public function test_verify_statutory_rates_registered(): void
    {
        $this->assertArrayHasKey('payroll:verify-statutory-rates', Artisan::all());
    }

    public function test_verify_statutory_rates_exposes_expected_options(): void
    {
        $opts = Artisan::all()['payroll:verify-statutory-rates']->getDefinition()->getOptions();
        foreach (['tenant', 'json'] as $expected) {
            $this->assertArrayHasKey($expected, $opts, "expected --{$expected}");
        }
    }

    public function test_backup_test_restore_registered(): void
    {
        $this->assertArrayHasKey('backup:test-restore', Artisan::all());
    }

    public function test_backup_test_restore_exposes_expected_options(): void
    {
        $opts = Artisan::all()['backup:test-restore']->getDefinition()->getOptions();
        foreach (['file', 'dry-run'] as $expected) {
            $this->assertArrayHasKey($expected, $opts, "expected --{$expected}");
        }
    }

    public function test_backup_test_restore_refuses_without_dsn(): void
    {
        // TEST_RESTORE_DSN unset in the test env → command should exit 1 with
        // a specific error, not crash.
        putenv('TEST_RESTORE_DSN');
        $exit = Artisan::call('backup:test-restore');
        $this->assertSame(1, $exit);
        $this->assertStringContainsString('TEST_RESTORE_DSN', Artisan::output());
    }
}
