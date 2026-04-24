<?php

namespace App\Models\Accounting;

use App\Models\User;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AiInvoiceScan extends Model
{
    use BelongsToTenant;

    protected $table = 'acc_ai_invoice_scans';

    protected $fillable = [
        'tenant_id', 'company', 'file_path', 'file_name', 'file_type', 'status',
        'extracted_data', 'confidence_score', 'error_message',
        'bill_id', 'reviewed_by', 'reviewed_at', 'created_by',
    ];

    protected $casts = [
        'extracted_data'   => 'array',
        'confidence_score' => 'decimal:2',
        'reviewed_at'      => 'datetime',
    ];

    public function bill()           { return $this->belongsTo(Bill::class, 'bill_id'); }
    public function reviewedByUser() { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function createdByUser()  { return $this->belongsTo(User::class, 'created_by'); }
}
