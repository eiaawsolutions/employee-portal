<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AiConversation extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'user_id', 'role', 'model', 'content',
        'tool_calls', 'tool_results',
        'input_tokens', 'output_tokens', 'cache_read_tokens', 'cache_write_tokens',
        'cost_usd', 'latency_ms', 'session_id',
    ];

    protected $casts = [
        'tool_calls' => 'array',
        'tool_results' => 'array',
        'cost_usd' => 'decimal:6',
    ];
}
