<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Daily per-tenant aggregate snapshot. Populated by the
 * `meter:tenant-usage` artisan command. Read-only at runtime — never
 * written from web requests. See app/Support/PlatformAdminVisibility.php
 * for the privacy contract.
 *
 * NOT tenant-scoped (no BelongsToTenant trait): this is a platform-level
 * table read only by EIAAW HQ staff.
 */
class TenantUsageDaily extends Model
{
    protected $table = 'tenant_usage_daily';

    protected $fillable = [
        'tenant_id', 'usage_date',
        'users_count', 'employees_count', 'db_row_count_total',
        'storage_mb', 'emails_sent_30d',
        'ai_requests_30d', 'ai_tokens_30d', 'ai_cost_usd_30d',
        'last_active_at',
    ];

    protected $casts = [
        'usage_date'      => 'date',
        'last_active_at'  => 'datetime',
        'ai_cost_usd_30d' => 'decimal:6',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
