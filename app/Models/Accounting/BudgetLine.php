<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class BudgetLine extends Model
{
    use BelongsToTenant;

    protected $table = 'acc_budget_lines';

    protected $fillable = ['tenant_id', 'budget_id', 'account_id', 'fiscal_period_id', 'amount'];

    protected $casts = ['amount' => 'decimal:2'];

    public function budget()       { return $this->belongsTo(Budget::class, 'budget_id'); }
    public function account()      { return $this->belongsTo(ChartOfAccount::class, 'account_id'); }
    public function fiscalPeriod() { return $this->belongsTo(FiscalPeriod::class, 'fiscal_period_id'); }
}
