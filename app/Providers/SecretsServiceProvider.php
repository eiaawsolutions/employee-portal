<?php

namespace App\Providers;

use App\Services\Secrets\InfisicalResolver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

/**
 * SecretsServiceProvider — resolves `secret://` handles in config paths
 * listed in config/secrets.php before any other provider boots.
 *
 * Registered FIRST in bootstrap/providers.php so CashierServiceProvider,
 * AiGateway, and other consumers see real values, not handles.
 *
 * Fail-open: if Infisical is unreachable or creds are absent, the provider
 * logs and continues. Consumers that actually need the secret will surface
 * their own specific errors — this provider does not nuke the boot.
 */
class SecretsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $infisicalConfig = config('secrets.infisical', []);

        $this->app->singleton(InfisicalResolver::class, function () use ($infisicalConfig) {
            return new InfisicalResolver($infisicalConfig);
        });

        if (! ($infisicalConfig['enabled'] ?? false)) {
            return;
        }

        if (empty($infisicalConfig['client_id']) || empty($infisicalConfig['client_secret']) || empty($infisicalConfig['project_id'])) {
            Log::debug('SecretsServiceProvider: skipped — Infisical creds incomplete.');
            return;
        }

        $resolver = $this->app->make(InfisicalResolver::class);
        $paths = config('secrets.resolve', []);

        foreach ($paths as $path) {
            $current = config($path);
            if (! is_string($current) || ! str_starts_with($current, 'secret://')) {
                continue;
            }
            try {
                $resolved = $resolver->resolve($current);
                config([$path => $resolved]);
            } catch (\Throwable $e) {
                // Fail-open. Downstream consumer raises its own error if
                // the secret was actually required.
                Log::error('SecretsServiceProvider: resolution failed, leaving handle in place.', [
                    'config_path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
