<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\PersonalDetail;
use Illuminate\Console\Command;

/**
 * One-time data fix: swap day ↔ month on date_of_birth where dates were
 * entered as dd/mm/yyyy but stored as mm/dd/yyyy (day and month swapped).
 *
 * Only affects ambiguous dates where both day ≤ 12 and month ≤ 12.
 * Dates where day > 12 are provably correct (can't be a valid month).
 *
 * Run with --dry-run first to preview changes without modifying data.
 */
class SwapDateOfBirth extends Command
{
    protected $signature = 'fix:swap-dob {--dry-run : Preview changes without saving}';
    protected $description = 'Swap day ↔ month on date_of_birth where dd/mm was stored as mm/dd';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN — no changes will be saved.');
        } else {
            if (!$this->confirm('This will swap day ↔ month on ALL ambiguous date_of_birth values. Continue?')) {
                return 0;
            }
        }

        $this->line('');

        // ── Employees ────────────────────────────────────────────────────
        $this->info('=== EMPLOYEES ===');
        $employees = Employee::whereNotNull('date_of_birth')
            ->whereRaw('DAY(date_of_birth) <= 12 AND MONTH(date_of_birth) <= 12')
            ->get();

        $empCount = 0;
        foreach ($employees as $emp) {
            $old = $emp->date_of_birth;
            $newDate = $old->copy()->setMonth($old->day)->setDay($old->month);

            // Skip symmetric dates (same after swap)
            if ($old->format('Y-m-d') === $newDate->format('Y-m-d')) {
                $this->line("  SKIP (symmetric) #{$emp->id} {$emp->full_name}: {$old->format('Y-m-d')}");
                continue;
            }

            $this->line("  #{$emp->id} {$emp->full_name}: {$old->format('Y-m-d')} → {$newDate->format('Y-m-d')}");

            if (!$dryRun) {
                $emp->update(['date_of_birth' => $newDate->format('Y-m-d')]);
            }
            $empCount++;
        }
        $this->info("  Employees affected: {$empCount}");

        $this->line('');

        // ── Personal Details (onboarding) ────────────────────────────────
        $this->info('=== PERSONAL DETAILS ===');
        $pds = PersonalDetail::whereNotNull('date_of_birth')
            ->whereRaw('DAY(date_of_birth) <= 12 AND MONTH(date_of_birth) <= 12')
            ->get();

        $pdCount = 0;
        foreach ($pds as $pd) {
            $old = $pd->date_of_birth;
            $newDate = $old->copy()->setMonth($old->day)->setDay($old->month);

            if ($old->format('Y-m-d') === $newDate->format('Y-m-d')) {
                $this->line("  SKIP (symmetric) #{$pd->id} {$pd->full_name}: {$old->format('Y-m-d')}");
                continue;
            }

            $this->line("  #{$pd->id} {$pd->full_name}: {$old->format('Y-m-d')} → {$newDate->format('Y-m-d')}");

            if (!$dryRun) {
                $pd->update(['date_of_birth' => $newDate->format('Y-m-d')]);
            }
            $pdCount++;
        }
        $this->info("  Personal details affected: {$pdCount}");

        $this->line('');
        if ($dryRun) {
            $this->warn('DRY RUN complete. Run without --dry-run to apply changes.');
        } else {
            $this->info("Done. Swapped {$empCount} employee(s) and {$pdCount} personal detail(s).");
        }

        return 0;
    }
}
