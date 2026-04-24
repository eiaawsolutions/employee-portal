<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * VerifyStatutoryRates — audits every tenant's payroll_configs against the
 * canonical Malaysian statutory schedule as of the command's LAST_VERIFIED
 * date below. Reports drift without auto-fixing (a rate change needs human
 * sign-off from the workspace owner + potentially counsel).
 *
 *   php artisan payroll:verify-statutory-rates
 *   php artisan payroll:verify-statutory-rates --tenant=acme
 *   php artisan payroll:verify-statutory-rates --json
 *
 * CANONICAL VALUES below are the source-of-truth our build is calibrated
 * against. When KWSP / PERKESO / LHDN / JTK publish a change, update the
 * CANONICAL array + bump LAST_VERIFIED + insert a new
 * `payroll_regulatory_alerts` row via migration.
 *
 * Exits 1 if any tenant drifts from canon — makes this safe to run in CI.
 */
class VerifyStatutoryRates extends Command
{
    protected $signature = 'payroll:verify-statutory-rates
        {--tenant= : Single tenant slug to check; default is all active tenants}
        {--json : Emit JSON report instead of human output}';

    protected $description = 'Verify every tenant payroll_config matches the canonical Malaysian statutory schedule.';

    /**
     * Canonical values sourced directly from KWSP / PERKESO / LHDN / JTK.
     * Last confirmed against the published schedules on the date below.
     * When this list changes, also append a row to payroll_regulatory_alerts
     * so existing tenants get a notification.
     */
    private const LAST_VERIFIED = '2026-04-25';

    private const CANONICAL = [
        // EPF — KWSP Act 1991 (Act 452), Third Schedule
        'epf_employer_rate'              => ['expected' => 13.00,  'authority' => 'KWSP', 'note' => 'Employer rate for salaries ≤ RM5,000'],
        'epf_employer_rate_high'         => ['expected' => 12.00,  'authority' => 'KWSP', 'note' => 'Employer rate for salaries > RM5,000'],
        'epf_employer_salary_threshold'  => ['expected' => 5000.00,'authority' => 'KWSP', 'note' => 'Threshold between tiered employer rates'],
        'epf_foreign_employer_flat'      => ['expected' => 5.00,   'authority' => 'KWSP', 'note' => 'Voluntary flat contribution for foreign workers'],

        // SOCSO — Employees\' Social Security Act 1969 (Act 4), Third Schedule
        'socso_wage_ceiling'             => ['expected' => 6000.00,'authority' => 'PERKESO', 'note' => 'Wage ceiling — raised from RM5K on 1 Oct 2024'],

        // EIS — Employment Insurance System Act 2017 (Act 800)
        'eis_wage_ceiling'               => ['expected' => 6000.00,'authority' => 'PERKESO', 'note' => 'Wage ceiling — raised from RM5K on 1 Oct 2024'],

        // PCB — Income Tax Act 1967 (ITA 1967), s.109B
        'pcb_nonresident_rate'           => ['expected' => 30.00,  'authority' => 'LHDN', 'note' => 'Flat withholding rate for non-resident employees'],

        // Minimum Wage — National Wages Consultative Council Act 2011
        'minimum_wage'                   => ['expected' => 1700.00,'authority' => 'JTK', 'note' => 'Minimum Wages Order 2025 — effective 1 Feb 2025'],
    ];

    public function handle(): int
    {
        if (!Schema::hasTable('payroll_configs')) {
            $this->warn('payroll_configs table not present — this deployment does not include the HR module');
            return self::SUCCESS;
        }

        $tenants = $this->resolveTenants();
        if ($tenants->isEmpty()) {
            $this->error('No tenants matched.');
            return self::FAILURE;
        }

        $this->info(sprintf(
            'Verifying statutory rates against canon dated %s · %d tenant(s)',
            self::LAST_VERIFIED,
            $tenants->count()
        ));

        $report = [];
        $driftCount = 0;

        foreach ($tenants as $tenant) {
            $tenantDrift = TenantContext::run($tenant, function () use ($tenant) {
                return $this->checkTenantConfig($tenant);
            });

            if (!empty($tenantDrift['drift'])) {
                $driftCount += count($tenantDrift['drift']);
            }
            $report[$tenant->slug] = $tenantDrift;
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'last_verified' => self::LAST_VERIFIED,
                'tenant_count' => $tenants->count(),
                'drift_count' => $driftCount,
                'report' => $report,
            ], JSON_PRETTY_PRINT));
        } else {
            $this->renderHuman($report);
        }

        return $driftCount === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function resolveTenants()
    {
        if ($slug = $this->option('tenant')) {
            return Tenant::where('slug', $slug)->get();
        }
        return Tenant::where('status', Tenant::STATUS_ACTIVE)->orderBy('id')->get();
    }

    private function checkTenantConfig(Tenant $tenant): array
    {
        $config = DB::table('payroll_configs')->first();
        if (!$config) {
            return ['status' => 'no_config', 'drift' => []];
        }

        $drift = [];
        foreach (self::CANONICAL as $field => $canon) {
            if (!property_exists($config, $field)) {
                $drift[$field] = [
                    'expected' => $canon['expected'],
                    'actual' => null,
                    'note' => "column missing — schema drift, reinstall payroll module",
                    'authority' => $canon['authority'],
                ];
                continue;
            }
            $actual = (float) $config->$field;
            if (abs($actual - $canon['expected']) > 0.001) {
                $drift[$field] = [
                    'expected' => $canon['expected'],
                    'actual' => $actual,
                    'note' => $canon['note'],
                    'authority' => $canon['authority'],
                ];
            }
        }

        return [
            'status' => empty($drift) ? 'ok' : 'drift',
            'drift' => $drift,
        ];
    }

    private function renderHuman(array $report): void
    {
        foreach ($report as $slug => $data) {
            if ($data['status'] === 'no_config') {
                $this->line("  <fg=yellow>⚬</> {$slug} · no payroll_configs row (tenant hasn't configured payroll)");
                continue;
            }
            if ($data['status'] === 'ok') {
                $this->line("  <fg=green>✓</> {$slug} · all rates match canon");
                continue;
            }

            $this->line("  <fg=red>✗</> {$slug} · " . count($data['drift']) . " rate(s) drifted:");
            foreach ($data['drift'] as $field => $d) {
                $actual = $d['actual'] ?? 'MISSING';
                $this->line("      {$field}: expected {$d['expected']} ({$d['authority']}) · actual {$actual}");
                $this->line("        → {$d['note']}");
            }
        }
    }
}
