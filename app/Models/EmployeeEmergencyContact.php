<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class EmployeeEmergencyContact extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'contact_order',
        'name',
        'tel_no',
        'relationship',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
