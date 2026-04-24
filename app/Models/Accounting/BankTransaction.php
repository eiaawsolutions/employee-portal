<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    use BelongsToTenant;

    protected $table = 'acc_bank_transactions';

    protected $fillable = [
        'tenant_id', 'bank_account_id', 'date', 'description', 'reference', 'debit', 'credit',
        'running_balance', 'is_reconciled', 'reconciliation_id', 'source_type', 'source_id',
    ];

    protected $casts = [
        'date'            => 'date',
        'debit'           => 'decimal:2',
        'credit'          => 'decimal:2',
        'running_balance' => 'decimal:2',
        'is_reconciled'   => 'boolean',
    ];

    public function bankAccount()    { return $this->belongsTo(BankAccount::class, 'bank_account_id'); }
    public function reconciliation() { return $this->belongsTo(BankReconciliation::class, 'reconciliation_id'); }
}
