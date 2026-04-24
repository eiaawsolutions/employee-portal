<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Smoke tests for the audit:export command registration + signature.
 *
 * Row-producing behaviour is validated in the Docker pgsql runbook
 * where a seeded tenant with real security_audit_logs / ai_conversations /
 * subscription_events exists. Here we only verify the command is wired
 * and exposes the flags we advertised.
 */
class AuditExportCommandTest extends TestCase
{
    public function test_audit_export_command_is_registered(): void
    {
        $this->assertArrayHasKey('audit:export', Artisan::all());
    }

    public function test_audit_export_exposes_expected_options(): void
    {
        $options = Artisan::all()['audit:export']->getDefinition()->getOptions();

        foreach (['tenant', 'all', 'from', 'to', 'disk', 'dry-run'] as $expected) {
            $this->assertArrayHasKey($expected, $options, "expected --{$expected} on audit:export");
        }
    }
}
