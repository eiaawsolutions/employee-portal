<?php

namespace Tests\Feature;

use App\Providers\SecretsServiceProvider;
use App\Services\Secrets\InfisicalResolver;
use Tests\TestCase;

/**
 * Regression: handles were resolved during register(), before the cache
 * service existed, so every lookup failed ("Target class [cache] does not
 * exist") and the raw secret:// handle was left as the Stripe key.
 */
class SecretsServiceProviderTest extends TestCase
{
    public function test_configured_handles_are_rewritten_to_resolved_values(): void
    {
        config([
            'secrets.infisical' => ['enabled' => true, 'client_id' => 'id', 'client_secret' => 's', 'project_id' => 'p'],
            'secrets.resolve' => ['cashier.secret', 'cashier.key'],
            'cashier.secret' => 'secret://eiaaw-all-projects/prod/STRIPE_SECRET',
            'cashier.key' => 'pk_plain_value_is_left_alone',
        ]);
        $this->app->instance(InfisicalResolver::class, new class extends InfisicalResolver {
            public function __construct() {}
            public function resolve(string $handle): string { return 'resolved:' . basename($handle); }
        });

        (new SecretsServiceProvider($this->app))->resolveConfiguredHandles();

        $this->assertSame('resolved:STRIPE_SECRET', config('cashier.secret'));
        $this->assertSame('pk_plain_value_is_left_alone', config('cashier.key'));
    }

    public function test_resolution_is_deferred_until_the_cache_service_exists(): void
    {
        $source = file_get_contents(app_path('Providers/SecretsServiceProvider.php'));

        $this->assertStringContainsString('->booting(', $source, 'handles must resolve in a booting callback, after the cache is registered');
    }
}
