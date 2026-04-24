<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * SubscriptionEvent — Stripe webhook idempotency log.
 *
 * Intentionally NOT BelongsToTenant: webhooks resolve the tenant from the
 * payload's customer ID, and we want platform-admin to see the full event
 * stream across tenants for billing audits. Postgres RLS on this table allows
 * the application connection to write and read all rows; tenant-admin UI
 * (Wk3) will filter by tenant_id at query time.
 */
class SubscriptionEvent extends Model
{
    protected $fillable = [
        'tenant_id', 'stripe_event_id', 'event_type',
        'payload', 'processed_at', 'processing_error',
    ];

    protected $casts = [
        'payload'      => 'array',
        'processed_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
