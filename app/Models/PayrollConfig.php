<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollConfig extends Model
{
    protected $fillable = [
        'company',
        'epf_employee_rate', 'epf_employer_rate', 'epf_employer_rate_high', 'epf_employer_salary_threshold',
        'epf_employee_rate_senior', 'epf_employer_rate_senior',
        'epf_foreign_employee_rate', 'epf_foreign_employer_flat',
        'socso_employee_rate', 'socso_employer_rate', 'socso_wage_ceiling',
        'socso_foreign_employer_rate',
        'eis_rate', 'eis_wage_ceiling', 'eis_foreign_exempt',
        'pcb_nonresident_rate',
        'minimum_wage', 'minimum_wage_effective_date',
        'hrdf_rate', 'hrdf_enabled',
        'default_working_days',
        'bank_name', 'bank_account_number',
        'lhdn_employer_no', 'epf_employer_no', 'socso_employer_no', 'eis_employer_no',
    ];

    protected $casts = [
        'epf_employee_rate' => 'decimal:2',
        'epf_employer_rate' => 'decimal:2',
        'epf_employer_rate_high' => 'decimal:2',
        'epf_employer_salary_threshold' => 'decimal:2',
        'epf_employee_rate_senior' => 'decimal:2',
        'epf_employer_rate_senior' => 'decimal:2',
        'epf_foreign_employee_rate' => 'decimal:2',
        'epf_foreign_employer_flat' => 'decimal:2',
        'socso_employee_rate' => 'decimal:4',
        'socso_employer_rate' => 'decimal:4',
        'socso_wage_ceiling' => 'decimal:2',
        'socso_foreign_employer_rate' => 'decimal:4',
        'eis_rate' => 'decimal:4',
        'eis_wage_ceiling' => 'decimal:2',
        'eis_foreign_exempt' => 'boolean',
        'pcb_nonresident_rate' => 'decimal:2',
        'minimum_wage' => 'decimal:2',
        'minimum_wage_effective_date' => 'date',
        'hrdf_rate' => 'decimal:2',
        'hrdf_enabled' => 'boolean',
    ];

    public static function forCompany(?string $company = null): self
    {
        return static::where('company', $company)->first()
            ?? static::whereNull('company')->first()
            ?? new static();
    }
}
