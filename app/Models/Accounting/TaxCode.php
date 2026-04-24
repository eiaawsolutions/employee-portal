<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TaxCode extends Model
{
    use BelongsToTenant;

    protected $table = 'acc_tax_codes';

    protected $fillable = [
        'tenant_id', 'company', 'code', 'name', 'rate', 'type', 'is_default', 'is_active',
        'purchase_account_id', 'sales_account_id',
    ];

    protected $casts = [
        'rate'       => 'decimal:3',
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    public function purchaseAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'purchase_account_id');
    }

    public function salesAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'sales_account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
