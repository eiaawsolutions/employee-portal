<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Smoke tests for the launch:preflight command.
 *
 * The command invokes real API calls to Stripe and Anthropic when keys are
 * set — we do NOT want those calls happening in unit tests. We therefore
 * only verify:
 *   - The command is registered
 *   - It runs to completion in the test env (where most checks will fail
 *     because sqlite, no API keys, stub banner present — that's expected)
 *   - --json output is parseable
 */
class LaunchPreflightTest extends TestCase
{
    public function test_command_is_registered(): void
    {
        $this->assertArrayHasKey('launch:preflight', Artisan::all());
    }

    public function test_command_exposes_json_option(): void
    {
        $options = Artisan::all()['launch:preflight']->getDefinition()->getOptions();
        $this->assertArrayHasKey('json', $options);
    }

    public function test_command_runs_in_test_env_without_throwing(): void
    {
        // In the test environment we expect the command to FAIL (not pgsql,
        // no API keys, legal stub present) — but the command itself should
        // not throw. Individual check failures should be contained.
        $exit = Artisan::call('launch:preflight', ['--json' => true]);

        // Exit 1 is expected (checks failing). The dangerous outcome would
        // be exit > 1 (an unhandled exception propagating out).
        $this->assertContains($exit, [0, 1], 'preflight should exit 0 or 1, never a crash');

        $output = Artisan::output();
        $json = json_decode($output, true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('results', $json);
        $this->assertArrayHasKey('passed', $json);
        $this->assertArrayHasKey('failed', $json);

        // Each result is shaped {ok: bool, detail: string}
        foreach ($json['results'] as $name => $result) {
            $this->assertArrayHasKey('ok', $result, "check {$name} missing 'ok'");
            $this->assertArrayHasKey('detail', $result, "check {$name} missing 'detail'");
            $this->assertIsBool($result['ok']);
        }
    }

    public function test_legal_stubs_check_flags_stub_banner(): void
    {
        Artisan::call('launch:preflight', ['--json' => true]);
        $json = json_decode(Artisan::output(), true);

        $this->assertFalse(
            $json['results']['legal_stubs_replaced']['ok'],
            'legal_stubs_replaced should fail while _stub-banner.blade.php exists'
        );
        $this->assertStringContainsString(
            'stub',
            strtolower($json['results']['legal_stubs_replaced']['detail'])
        );
    }
}
