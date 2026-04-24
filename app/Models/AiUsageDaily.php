<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AiUsageDaily extends Model
{
    use BelongsToTenant;

    protected $table = 'ai_usage_daily';

    protected $fillable = [
        'tenant_id', 'usage_date',
        'input_tokens', 'output_tokens', 'cache_read_tokens', 'cache_write_tokens',
        'cost_usd', 'request_count',
    ];

    protected $casts = [
        'usage_date' => 'date',
        'cost_usd' => 'decimal:6',
    ];
}
