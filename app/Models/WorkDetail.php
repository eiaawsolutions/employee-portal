<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'onboarding_id', 'employee_number', 'employee_status', 'staff_status',
        'employment_type', 'designation', 'company', 'office_location',
        'reporting_manager', 'reporting_manager_email', 'start_date', 'exit_date',
        'last_salary_date', 'confirmation_date', 'company_email', 'google_id',
        'department', 'role',
    ];

    protected $casts = [
        'start_date'        => 'date',
        'exit_date'         => 'date',
        'last_salary_date'  => 'date',
        'confirmation_date' => 'date',
    ];

    public function onboarding() { return $this->belongsTo(Onboarding::class); }
}
