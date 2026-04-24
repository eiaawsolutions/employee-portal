<?php

namespace App\Providers;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;

/**
 * WorkEmailUserProvider — authenticates against the User.work_email column.
 *
 * EIAAW Workforce SaaS extension: when a tenant has been resolved by the
 * ResolveTenant middleware (i.e. login is happening at a tenant subdomain
 * like acme.ep.eiaawsolutions.com), credentials are scoped to that tenant
 * via the users.tenant_id foreign key. This prevents email collisions
 * across tenants from causing cross-tenant authentication.
 *
 * In single-tenant mode (no tenant resolved — e.g. running the legacy
 * Claritas portal on its own deployment), the scoping is a no-op and the
 * provider behaves exactly like the original.
 *
 * The schema change that adds `users.tenant_id` ships in Session 3 along
 * with the per-table tenancy retrofit. Until then, this code path is
 * forward-compatible: when tenant_id doesn't yet exist on users, the
 * `where(tenant_id, ...)` clause is gated by Schema::hasColumn().
 */
class WorkEmailUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (isset($credentials['email'])) {
            $credentials['work_email'] = $credentials['email'];
            unset($credentials['email']);
        }

        if ($tenantId = $this->currentTenantIdIfScopable()) {
            $credentials['tenant_id'] = $tenantId;
        }

        return parent::retrieveByCredentials($credentials);
    }

    public function retrieveById($identifier): ?Authenticatable
    {
        $user = parent::retrieveById($identifier);

        if (!$user) {
            return null;
        }

        // If we have a current tenant AND the user has a tenant_id mismatch,
        // refuse to hydrate — protects against session fixation across tenants.
        if (($tenantId = $this->currentTenantIdIfScopable())
            && property_exists($user, 'tenant_id')
            && $user->tenant_id
            && $user->tenant_id !== $tenantId) {
            return null;
        }

        return $user;
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $plain = $credentials['password'] ?? '';

        return Hash::check($plain, $user->getAuthPassword());
    }

    /**
     * Return the current tenant ID only if (a) a tenant is bound AND (b) the
     * users table actually has a tenant_id column. The column-existence guard
     * keeps this code path safe before the Session 3 retrofit ships.
     */
    private function currentTenantIdIfScopable(): ?int
    {
        if (!app()->bound('current_tenant')) {
            return null;
        }

        // Lightweight per-process cache of "does the users table have tenant_id?"
        // Avoids hitting information_schema on every login attempt.
        static $hasColumn = null;
        if ($hasColumn === null) {
            try {
                $hasColumn = \Illuminate\Support\Facades\Schema::hasColumn('users', 'tenant_id');
            } catch (\Throwable) {
                $hasColumn = false;
            }
        }

        if (!$hasColumn) {
            return null;
        }

        return app('current_tenant')->id;
    }
}
