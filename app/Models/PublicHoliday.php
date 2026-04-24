<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class PublicHoliday extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'company', 'name', 'date', 'year', 'is_recurring',
    ];

    protected $casts = [
        'date' => 'date',
        'is_recurring' => 'boolean',
    ];
}
