<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BelongsToTenant — apply to every tenant-scoped Eloquent model.
 *
 * Effects:
 *  - Adds the global TenantScope (auto-filters queries to current tenant)
 *  - Auto-fills tenant_id on create
 *  - Provides a tenant() relationship
 *
 * Postgres RLS is the defense-in-depth layer if app code forgets to scope.
 * See ResolveTenant middleware for the per-request RLS variable injection.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (!$model->tenant_id && app()->bound('current_tenant')) {
                $model->tenant_id = app('current_tenant')->id;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
