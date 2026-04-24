<?php

namespace App\Auth;

use Illuminate\Auth\Passwords\PasswordBrokerManager;

/**
 * Replaces Laravel's default PasswordBrokerManager so the database token
 * repository becomes our tenant-aware subclass.
 *
 * Bound in AuthServiceProvider::register() via $this->app->singleton('auth.password').
 *
 * Why subclass instead of using a hook: the framework's createTokenRepository
 * is not extensible for the 'database' driver — only the 'cache' driver has
 * an extend point. The cleanest override is to swap the entire manager.
 */
class TenantPasswordBrokerManager extends PasswordBrokerManager
{
    protected function createTokenRepository(array $config)
    {
        $key = $this->app['config']['app.key'];

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        // Cache driver path: keep framework default behavior (no tenant
        // wrapping needed — cache stores are typically per-tenant via cache
        // key prefix anyway, separately configured).
        if (isset($config['driver']) && $config['driver'] === 'cache') {
            return parent::createTokenRepository($config);
        }

        // Default: tenant-aware database repository.
        return new TenantAwarePasswordTokenRepository(
            $this->app['db']->connection($config['connection'] ?? null),
            $this->app['hash'],
            $config['table'],
            $key,
            ($config['expire'] ?? 60) * 60,
            $config['throttle'] ?? 0,
        );
    }
}
