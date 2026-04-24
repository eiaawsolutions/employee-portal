<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * stripe:sync-prices — registration + safety-guard smoke tests.
 *
 * The command makes real Stripe API calls when STRIPE_SECRET is set; we
 * don't exercise that path here. We verify the command is wired correctly
 * and refuses to run against live keys without the explicit safety flag.
 */
class StripeSyncPricesTest extends TestCase
{
    public function test_command_is_registered(): void
    {
        $this->assertArrayHasKey('stripe:sync-prices', Artisan::all());
    }

    public function test_command_exposes_safety_options(): void
    {
        $options = Artisan::all()['stripe:sync-prices']->getDefinition()->getOptions();
        foreach (['dry-run', 'apply', 'i-know-this-is-live', 'emit-env'] as $expected) {
            $this->assertArrayHasKey($expected, $options, "expected --{$expected} on stripe:sync-prices");
        }
    }

    public function test_command_fails_when_stripe_secret_missing(): void
    {
        // Ensure no STRIPE_SECRET is set in the test env.
        putenv('STRIPE_SECRET');
        config(['services.stripe.secret' => null]);

        $exit = Artisan::call('stripe:sync-prices');
        $this->assertSame(1, $exit, 'command should fail without STRIPE_SECRET');

        $output = Artisan::output();
        $this->assertStringContainsString('STRIPE_SECRET', $output);
    }

    public function test_command_refuses_live_apply_without_safety_flag(): void
    {
        putenv('STRIPE_SECRET=sk_live_fakekey_for_test_only');

        $exit = Artisan::call('stripe:sync-prices', ['--apply' => true]);

        $this->assertSame(1, $exit, 'command should refuse --apply on a live key without the safety flag');
        $this->assertStringContainsString('LIVE', Artisan::output());

        putenv('STRIPE_SECRET');
    }
}
