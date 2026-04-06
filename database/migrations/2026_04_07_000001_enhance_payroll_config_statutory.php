<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Add missing foreigner & senior fields to payroll_configs ────
        Schema::table('payroll_configs', function (Blueprint $table) {
            // EPF employer rate tier (>RM5,000 salary gets lower rate)
            $table->decimal('epf_employer_rate_high', 5, 2)->default(12.00)->after('epf_employer_rate');
            $table->decimal('epf_employer_salary_threshold', 10, 2)->default(5000.00)->after('epf_employer_rate_high');

            // Foreigner EPF (voluntary; employer pays RM5 flat by default)
            $table->decimal('epf_foreign_employee_rate', 5, 2)->default(0.00)->after('epf_employer_rate_senior');
            $table->decimal('epf_foreign_employer_flat', 10, 2)->default(5.00)->after('epf_foreign_employee_rate');

            // Foreigner SOCSO — Foreign Worker Compensation Scheme (FWCS)
            // Employer-only, rate per Third Schedule Act 4
            $table->decimal('socso_foreign_employer_rate', 5, 4)->default(1.25)->after('socso_wage_ceiling');

            // EIS exemption for foreign workers (s.5, EIS Act 2017)
            $table->boolean('eis_foreign_exempt')->default(true)->after('eis_wage_ceiling');

            // PCB non-resident flat rate (Income Tax Act 1967, s.109B)
            $table->decimal('pcb_nonresident_rate', 5, 2)->default(30.00)->after('eis_foreign_exempt');

            // Minimum wage (Employment Act 1955, Minimum Wages Order)
            $table->decimal('minimum_wage', 10, 2)->default(1700.00)->after('pcb_nonresident_rate');
            $table->date('minimum_wage_effective_date')->nullable()->after('minimum_wage');
        });

        // ── Regulatory alerts table ────────────────────────────────────
        Schema::create('payroll_regulatory_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('authority', 50); // KWSP, PERKESO, LHDN, JTK, etc.
            $table->string('reference_law')->nullable(); // e.g. "KWSP Act 1991 s.43"
            $table->string('reference_url', 500)->nullable();
            $table->date('effective_date');
            $table->date('announced_date')->nullable();
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->enum('status', ['pending', 'acknowledged', 'implemented'])->default('pending');
            $table->text('config_fields_affected')->nullable(); // JSON list of affected config fields
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
        });

        // ── Seed known Malaysian regulatory changes ────────────────────
        $now = now();
        \DB::table('payroll_regulatory_alerts')->insert([
            [
                'title' => 'SOCSO & EIS Wage Ceiling Increase to RM6,000',
                'description' => 'Effective 1 Oct 2024, PERKESO enforces a new wage ceiling from RM5,000 to RM6,000/month for both SOCSO (Act 4) and EIS (Act 800). Employers must update contribution calculations accordingly.',
                'authority' => 'PERKESO',
                'reference_law' => 'Employees\' Social Security Act 1969 (Act 4), Third Schedule; EIS Act 2017 (Act 800), Second Schedule',
                'reference_url' => 'https://www.perkeso.gov.my/en/rate-of-contribution.html',
                'effective_date' => '2024-10-01',
                'announced_date' => '2024-09-01',
                'severity' => 'critical',
                'status' => 'pending',
                'config_fields_affected' => json_encode(['socso_wage_ceiling', 'eis_wage_ceiling']),
                'acknowledged_by' => null,
                'acknowledged_at' => null,
                'notified_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Minimum Wage Increase to RM1,700',
                'description' => 'The Minimum Wages Order 2025 raises the national minimum wage from RM1,500 to RM1,700/month effective 1 Feb 2025. Applies to all employers regardless of the number of employees.',
                'authority' => 'JTK',
                'reference_law' => 'National Wages Consultative Council Act 2011, Minimum Wages Order 2025',
                'reference_url' => null,
                'effective_date' => '2025-02-01',
                'announced_date' => '2024-10-25',
                'severity' => 'critical',
                'status' => 'pending',
                'config_fields_affected' => json_encode(['minimum_wage']),
                'acknowledged_by' => null,
                'acknowledged_at' => null,
                'notified_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'EPF Employer Rate Tiered Structure',
                'description' => 'EPF employer contribution rate is 13% for employees earning ≤RM5,000/month and 12% for those earning >RM5,000/month. Verify your configuration reflects the correct thresholds.',
                'authority' => 'KWSP',
                'reference_law' => 'Employees Provident Fund Act 1991 (Act 452), Third Schedule',
                'reference_url' => 'https://www.kwsp.gov.my/employer/contribution',
                'effective_date' => '2024-01-01',
                'announced_date' => '2023-10-13',
                'severity' => 'warning',
                'status' => 'pending',
                'config_fields_affected' => json_encode(['epf_employer_rate', 'epf_employer_rate_high', 'epf_employer_salary_threshold']),
                'acknowledged_by' => null,
                'acknowledged_at' => null,
                'notified_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Foreign Worker EPF — Voluntary Contribution',
                'description' => 'Foreign workers are not mandated to contribute to EPF (Category 3). However, employers may voluntarily contribute RM5/month per foreign worker under KWSP guidelines. EIS (Act 800) does not apply to foreign workers.',
                'authority' => 'KWSP',
                'reference_law' => 'Employees Provident Fund Act 1991 (Act 452), s.2 — definition of "employee"',
                'reference_url' => 'https://www.kwsp.gov.my/employer/contribution',
                'effective_date' => '2024-01-01',
                'announced_date' => null,
                'severity' => 'info',
                'status' => 'pending',
                'config_fields_affected' => json_encode(['epf_foreign_employee_rate', 'epf_foreign_employer_flat']),
                'acknowledged_by' => null,
                'acknowledged_at' => null,
                'notified_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Foreign Worker Compensation Scheme (FWCS)',
                'description' => 'Foreign workers are covered under the FWCS administered by PERKESO, not the standard SOCSO scheme. The employer pays the full contribution; there is no employee portion. Rates follow the Third Schedule of Act 4.',
                'authority' => 'PERKESO',
                'reference_law' => 'Employees\' Social Security Act 1969 (Act 4), Third Schedule — Foreign Workers',
                'reference_url' => 'https://www.perkeso.gov.my/en/our-services/protection/foreign-worker.html',
                'effective_date' => '2019-01-01',
                'announced_date' => null,
                'severity' => 'info',
                'status' => 'pending',
                'config_fields_affected' => json_encode(['socso_foreign_employer_rate']),
                'acknowledged_by' => null,
                'acknowledged_at' => null,
                'notified_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'PCB Non-Resident Tax Rate — 30% Flat',
                'description' => 'Non-resident employees (including most foreign workers) are subject to a flat 30% withholding tax (MTD/PCB) on employment income under s.109B of the Income Tax Act 1967. No personal reliefs apply.',
                'authority' => 'LHDN',
                'reference_law' => 'Income Tax Act 1967, s.109B',
                'reference_url' => 'https://ez.hasil.gov.my',
                'effective_date' => '2024-01-01',
                'announced_date' => null,
                'severity' => 'info',
                'status' => 'pending',
                'config_fields_affected' => json_encode(['pcb_nonresident_rate']),
                'acknowledged_by' => null,
                'acknowledged_at' => null,
                'notified_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // ── Update default wage ceilings to RM6,000 ───────────────────
        \DB::table('payroll_configs')
            ->where('socso_wage_ceiling', 5000.00)
            ->update(['socso_wage_ceiling' => 6000.00]);
        \DB::table('payroll_configs')
            ->where('eis_wage_ceiling', 5000.00)
            ->update(['eis_wage_ceiling' => 6000.00]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_regulatory_alerts');

        Schema::table('payroll_configs', function (Blueprint $table) {
            $table->dropColumn([
                'epf_employer_rate_high',
                'epf_employer_salary_threshold',
                'epf_foreign_employee_rate',
                'epf_foreign_employer_flat',
                'socso_foreign_employer_rate',
                'eis_foreign_exempt',
                'pcb_nonresident_rate',
                'minimum_wage',
                'minimum_wage_effective_date',
            ]);
        });
    }
};
