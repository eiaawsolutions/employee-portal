<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AssetAssignment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'onboarding_id', 'employee_id', 'asset_inventory_id',
        'assigned_date', 'returned_date', 'status',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'returned_date' => 'date',
    ];

    public function onboarding() { return $this->belongsTo(Onboarding::class); }
    public function employee()   { return $this->belongsTo(Employee::class); }
    public function asset()      { return $this->belongsTo(AssetInventory::class, 'asset_inventory_id'); }
}