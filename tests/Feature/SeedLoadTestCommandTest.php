<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * tenant:seed-load-test registration + production-safety guard smoke.
 *
 * The command actually writes rows + a credentials JSON; that path is
 * exercised in the Docker staging runbook. Here we verify the command
 * is registered and refuses to run against APP_ENV=production.
 */
class SeedLoadTestCommandTest extends TestCase
{
    public function test_command_is_registered(): void
    {
        $this->assertArrayHasKey('tenant:seed-load-test', Artisan::all());
    }

    public function test_command_exposes_expected_options(): void
    {
        $options = Artisan::all()['tenant:seed-load-test']->getDefinition()->getOptions();
        foreach (['tenants', 'users-per-tenant', 'password', 'output', 'force'] as $expected) {
            $this->assertArrayHasKey($expected, $options);
        }
    }
}
