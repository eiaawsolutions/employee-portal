<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollItem extends Model
{
    protected $fillable = [
        'company', 'name', 'code', 'type', 'default_amount',
        'is_statutory', 'is_recurring', 'is_active',
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'is_statutory' => 'boolean',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeEarnings($query)
    {
        return $query->where('type', 'earning');
    }

    public function scopeDeductions($query)
    {
        return $query->where('type', 'deduction');
    }
}
