<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DiagnoseSpouseSave extends Command
{
    protected $signature = 'diagnose:spouse-save {employee_name?}';
    protected $description = 'Diagnose why spouse records may not be saving. Reports schema state, migration status, and recent spouse records.';

    public function handle(): int
    {
        $this->info('=== Spouse Save Diagnostics ===');
        $this->line('');

        $this->info('[1/5] PHP input limits');
        $this->line('  max_input_vars: ' . ini_get('max_input_vars'));
        $this->line('  post_max_size:  ' . ini_get('post_max_size'));
        $this->line('  memory_limit:   ' . ini_get('memory_limit'));
        $this->line('');

        $this->info('[2/5] Migration status');
        $migrations = DB::table('migrations')->where('migration', 'like', '%spouse%')->pluck('migration')->toArray();
        foreach ($migrations as $m) {
            $this->line('  RAN: ' . $m);
        }
        $needsMigration = !in_array('2026_03_27_200000_allow_multiple_spouses', $migrations);
        if ($needsMigration) {
            $this->error('  MISSING: 2026_03_27_200000_allow_multiple_spouses — UNIQUE constraint may still block additional spouses');
        } else {
            $this->info('  OK: allow_multiple_spouses migration has run');
        }
        $this->line('');

        $this->info('[3/5] Table schema — indexes on employee_spouse_details');
        $indexes = DB::select("SHOW INDEX FROM employee_spouse_details");
        $hasUniqueOnEmployeeId = false;
        foreach ($indexes as $idx) {
            $unique = $idx->Non_unique == 0 ? 'UNIQUE' : 'index';
            $this->line("  {$unique}: {$idx->Key_name} ({$idx->Column_name})");
            if ($idx->Column_name === 'employee_id' && $idx->Non_unique == 0 && $idx->Key_name !== 'PRIMARY') {
                $hasUniqueOnEmployeeId = true;
            }
        }
        if ($hasUniqueOnEmployeeId) {
            $this->error('  PROBLEM: employee_id has a UNIQUE constraint — only one spouse per employee will succeed');
            $this->warn('  FIX: run `php artisan migrate` to apply 2026_03_27_200000_allow_multiple_spouses');
        } else {
            $this->info('  OK: no unique constraint on employee_id');
        }
        $this->line('');

        $this->info('[4/5] Employee + spouse lookup');
        $name = $this->argument('employee_name');
        if ($name) {
            $emp = \App\Models\Employee::where('full_name', 'LIKE', "%{$name}%")->first();
            if (!$emp) {
                $this->warn("  No employee matching '{$name}'");
            } else {
                $this->line("  Employee: #{$emp->id} {$emp->full_name}");
                $this->line("  Marital status: " . ($emp->marital_status ?? '(null)'));
                $this->line("  Onboarding ID:  " . ($emp->onboarding_id ?? '(none)'));
                $this->line("  Spouse rows in DB: " . $emp->spouseDetails()->count());
                foreach ($emp->spouseDetails as $sp) {
                    $this->line("    - #{$sp->id} {$sp->name} tel={$sp->tel_no} created={$sp->created_at}");
                }
                $staging = $emp->onboarding?->personalDetail?->invite_staging_json;
                if ($staging) {
                    $data = json_decode($staging, true) ?: [];
                    $stagingSpouses = $data['spouses'] ?? [];
                    $this->line("  Staging JSON spouses: " . count($stagingSpouses));
                    foreach ($stagingSpouses as $i => $sp) {
                        $this->line("    - [{$i}] " . ($sp['name'] ?? '(no name)') . " tel=" . ($sp['tel_no'] ?? ''));
                    }
                }
            }
        } else {
            $this->line('  (skipped — pass employee name to inspect)');
        }
        $this->line('');

        $this->info('[5/5] Recent employees.update spouse log lines');
        $logPath = storage_path('logs/laravel.log');
        if (!file_exists($logPath)) {
            $this->warn('  No laravel.log at ' . $logPath);
        } else {
            $cmd = 'grep -a "employees.update spouse\|employees.spouse.update" ' . escapeshellarg($logPath) . ' | tail -30';
            $out = shell_exec($cmd);
            if (trim($out ?? '') === '') {
                $this->line('  No recent spouse-save log lines (nothing logged yet, or deploy predates logging commit)');
            } else {
                $this->line($out);
            }
        }
        $this->line('');

        if ($needsMigration || $hasUniqueOnEmployeeId) {
            $this->error('ACTION REQUIRED: run `php artisan migrate` to fix the schema');
            return self::FAILURE;
        }
        $this->info('Diagnostics complete — schema looks healthy.');
        return self::SUCCESS;
    }
}
