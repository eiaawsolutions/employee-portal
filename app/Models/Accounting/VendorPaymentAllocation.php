<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class VendorPaymentAllocation extends Model
{
    use BelongsToTenant;

    protected $table = 'acc_vendor_payment_allocations';

    protected $fillable = ['tenant_id', 'vendor_payment_id', 'bill_id', 'amount'];

    protected $casts = ['amount' => 'decimal:2'];

    public function payment() { return $this->belongsTo(VendorPayment::class, 'vendor_payment_id'); }
    public function bill()    { return $this->belongsTo(Bill::class, 'bill_id'); }
}
