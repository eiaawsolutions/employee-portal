<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    protected $fillable = [
        'pay_run_id', 'employee_id', 'payslip_number',
        'basic_salary', 'total_earnings', 'total_deductions', 'net_pay',
        'epf_employee', 'socso_employee', 'eis_employee', 'pcb_amount',
        'epf_employer', 'socso_employer', 'eis_employer', 'hrdf_amount',
        'unpaid_leave_days', 'unpaid_leave_amount',
        'overtime_hours', 'overtime_amount',
        'claim_reimbursement',
        'status',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'epf_employee' => 'decimal:2',
        'socso_employee' => 'decimal:2',
        'eis_employee' => 'decimal:2',
        'pcb_amount' => 'decimal:2',
        'epf_employer' => 'decimal:2',
        'socso_employer' => 'decimal:2',
        'eis_employer' => 'decimal:2',
        'hrdf_amount' => 'decimal:2',
        'unpaid_leave_days' => 'decimal:1',
        'unpaid_leave_amount' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'claim_reimbursement' => 'decimal:2',
    ];

    // ── Accessors (alias DB columns for Blade views) ───────────────────
    public function getPcbAttribute(): float   { return (float) $this->pcb_amount; }
    public function getUnpaidLeaveAttribute(): float { return (float) $this->unpaid_leave_amount; }
    public function getOvertimeAttribute(): float    { return (float) $this->overtime_amount; }
    public function getHrdfAttribute(): float        { return (float) $this->hrdf_amount; }

    public function payRun(): BelongsTo
    {
        return $this->belongsTo(PayRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayslipItem::class);
    }

    /**
     * Calculate Malaysian statutory contributions.
     * Uses EPF category from Employee if available.
     * Reads rates from PayrollConfig; falls back to statutory defaults.
     * References: KWSP Act 1991, PERKESO Act 1969, EIS Act 2017, Income Tax Act 1967.
     */
    public function calculateStatutory(): void
    {
        // Statutory base excludes expense claim reimbursements (non-taxable)
        $gross = (float) $this->total_earnings - (float) ($this->claim_reimbursement ?? 0);
        $employee = $this->employee ?? Employee::find($this->employee_id);
        $epfCategory = $employee->epf_category ?? '1';
        $isResident = $employee->is_resident ?? true;
        $age = $employee->date_of_birth ? $employee->date_of_birth->age : 30;
        $isForeign = $epfCategory === '3';

        $cfg = PayrollConfig::forCompany();

        // ── EPF (KWSP Act 1991, Third Schedule) ────────────────────────
        if ($isForeign) {
            // Category 3: Foreign workers — voluntary
            $foreignEeRate = (float) ($cfg->epf_foreign_employee_rate ?? 0) / 100;
            $this->epf_employee = round($gross * $foreignEeRate, 2);
            $this->epf_employer = round((float) ($cfg->epf_foreign_employer_flat ?? 5), 2);
        } elseif ($epfCategory === '2' || $age >= 60) {
            // Category 2: Senior / disabled / pensionable
            $this->epf_employee = round($gross * ((float) ($cfg->epf_employee_rate_senior ?? 5.50) / 100), 2);
            $this->epf_employer = round($gross * ((float) ($cfg->epf_employer_rate_senior ?? 6.50) / 100), 2);
        } else {
            // Category 1: Malaysian <60 years
            $this->epf_employee = round($gross * ((float) ($cfg->epf_employee_rate ?? 11) / 100), 2);
            $threshold = (float) ($cfg->epf_employer_salary_threshold ?? 5000);
            $erRate = $gross <= $threshold
                ? (float) ($cfg->epf_employer_rate ?? 13) / 100
                : (float) ($cfg->epf_employer_rate_high ?? 12) / 100;
            $this->epf_employer = round($gross * $erRate, 2);
        }

        // ── SOCSO (Employees' Social Security Act 1969) ────────────────
        $socsoCeiling = (float) ($cfg->socso_wage_ceiling ?? 6000);
        if ($isForeign) {
            // FWCS — employer only, no employee portion
            $this->socso_employee = 0;
            $fwcsWage = min($gross, $socsoCeiling);
            $this->socso_employer = round($fwcsWage * ((float) ($cfg->socso_foreign_employer_rate ?? 1.25) / 100), 2);
        } else {
            $socsoWage = min($gross, $socsoCeiling);
            $this->socso_employee = round($socsoWage * ((float) ($cfg->socso_employee_rate ?? 0.50) / 100), 2);
            $this->socso_employer = round($socsoWage * ((float) ($cfg->socso_employer_rate ?? 1.75) / 100), 2);
        }

        // ── EIS (Employment Insurance System Act 2017) ─────────────────
        $eisExemptForeign = (bool) ($cfg->eis_foreign_exempt ?? true);
        if ($isForeign && $eisExemptForeign) {
            // s.5 EIS Act 2017 — foreign workers exempt
            $this->eis_employee = 0;
            $this->eis_employer = 0;
        } else {
            $eisCeiling = (float) ($cfg->eis_wage_ceiling ?? 6000);
            $eisWage = min($gross, $eisCeiling);
            $eisRate = (float) ($cfg->eis_rate ?? 0.20) / 100;
            $this->eis_employee = round($eisWage * $eisRate, 2);
            $this->eis_employer = round($eisWage * $eisRate, 2);
        }

        // ── HRDF (employer only — 1% for companies ≥10 employees) ──────
        $hrdfEnabled = (bool) ($cfg->hrdf_enabled ?? true);
        $this->hrdf_amount = $hrdfEnabled
            ? round($gross * ((float) ($cfg->hrdf_rate ?? 1) / 100), 2)
            : 0;

        // ── PCB / MTD (Income Tax Act 1967) ────────────────────────────
        if (!$isResident) {
            $pcbRate = (float) ($cfg->pcb_nonresident_rate ?? 30) / 100;
            $this->pcb_amount = round($gross * $pcbRate, 2);
        } else {
            $this->pcb_amount = $this->estimateMonthlyPcb($gross);
        }

        // ── Total employee deductions & net pay ────────────────────────
        $this->total_deductions = $this->epf_employee + $this->socso_employee
            + $this->eis_employee + $this->pcb_amount + (float) $this->unpaid_leave_amount;
        $this->net_pay = $this->total_earnings - $this->total_deductions;
    }

    /**
     * Simplified monthly PCB estimate for tax residents.
     * Based on 2024 Malaysian income tax brackets applied monthly.
     * Assumes single, no reliefs beyond personal (RM9,000/yr = RM750/mth).
     */
    private function estimateMonthlyPcb(float $monthlyGross): float
    {
        // Approximate chargeable = gross − EPF employee − personal relief
        $monthlyRelief = 750; // RM9,000 / 12
        $chargeable = max(0, $monthlyGross - $this->epf_employee - $monthlyRelief);
        $annual = $chargeable * 12;

        // Malaysian tax brackets (2024 YA)
        $brackets = [
            [5000,   0],     // 0–5,000: 0%
            [15000,  0.01],  // 5,001–20,000: 1%
            [15000,  0.03],  // 20,001–35,000: 3%
            [15000,  0.06],  // 35,001–50,000: 6% (rebate zeroes below RM35K)
            [20000,  0.11],  // 50,001–70,000: 11%
            [30000,  0.19],  // 70,001–100,000: 19%
            [150000, 0.25],  // 100,001–250,000: 25%
            [150000, 0.25],  // 250,001–400,000: 25%
            [200000, 0.26],  // 400,001–600,000: 26%
            [400000, 0.28],  // 600,001–1,000,000: 28%
            [PHP_INT_MAX, 0.30], // >1,000,000: 30%
        ];

        $annualTax = 0;
        $remaining = $annual;
        foreach ($brackets as [$slice, $rate]) {
            if ($remaining <= 0) break;
            $taxable = min($remaining, $slice);
            $annualTax += $taxable * $rate;
            $remaining -= $taxable;
        }

        // Tax rebate: RM400 if chargeable ≤ RM35,000
        if ($annual <= 35000) {
            $annualTax = max(0, $annualTax - 400);
        }

        return round($annualTax / 12, 2);
    }
}
