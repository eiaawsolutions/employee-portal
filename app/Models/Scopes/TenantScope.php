<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * TenantScope — global scope auto-applied by the BelongsToTenant trait.
 *
 * Constrains every Eloquent query to the current tenant. If no tenant is
 * resolved (e.g. in a console command not yet tenant-aware), the scope is
 * a no-op — but Postgres RLS will still block cross-tenant SELECT/UPDATE/DELETE
 * because the session variable app.tenant_id is unset.
 *
 * To intentionally bypass for super-admin / cross-tenant operations:
 *   Model::withoutGlobalScope(TenantScope::class)->get()
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (!app()->bound('current_tenant')) {
            return;
        }

        $tenantId = app('current_tenant')->id;
        $builder->where($model->getTable() . '.tenant_id', $tenantId);
    }
}
