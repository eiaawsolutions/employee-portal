<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class EmployeeChildRegistration extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'employee_id',
        'cat_a_100','cat_a_50',
        'cat_b_100','cat_b_50',
        'cat_c_100','cat_c_50',
        'cat_d_100','cat_d_50',
        'cat_e_100','cat_e_50',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
