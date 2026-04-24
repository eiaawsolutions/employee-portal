<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use BelongsToTenant;

    protected $table = 'acc_currencies';

    protected $fillable = ['tenant_id', 'code', 'name', 'symbol', 'exchange_rate', 'is_base'];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'is_base'       => 'boolean',
    ];
}
