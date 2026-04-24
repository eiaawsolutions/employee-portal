<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRegulatoryAlert extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'title', 'description', 'authority', 'reference_law', 'reference_url',
        'effective_date', 'announced_date', 'severity', 'status',
        'config_fields_affected',
        'acknowledged_by', 'acknowledged_at', 'notified_at',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'announced_date' => 'date',
        'acknowledged_at' => 'datetime',
        'notified_at' => 'datetime',
        'config_fields_affected' => 'array',
    ];

    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActionRequired($query)
    {
        return $query->whereIn('status', ['pending', 'acknowledged'])
            ->where('effective_date', '<=', now()->addMonths(3));
    }

    public function severityBadge(): string
    {
        return match ($this->severity) {
            'critical' => '<span class="badge bg-danger">Critical</span>',
            'warning' => '<span class="badge bg-warning text-dark">Warning</span>',
            default => '<span class="badge bg-info">Info</span>',
        };
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'implemented' => '<span class="badge bg-success">Implemented</span>',
            'acknowledged' => '<span class="badge bg-primary">Acknowledged</span>',
            default => '<span class="badge bg-secondary">Pending</span>',
        };
    }
}
