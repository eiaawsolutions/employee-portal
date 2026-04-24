<?php

namespace App\Console\Commands;

use App\Http\Controllers\OnboardingController;
use App\Models\Employee;
use App\Models\Offboarding;
use App\Models\Onboarding;
use App\Models\Tenant;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ActivateEmployees extends Command
{
    protected $signature   = 'employees:activate';
    protected $description = 'Activate employees on start_date and offboard on exit_date — iterates every active tenant.';

    public function handle(): void
    {
        $today = Carbon::today();
        $this->info("Running employee lifecycle check for: {$today->toDateString()}");

        $tenantsTotalWelcome    = 0;
        $tenantsTotalActivated  = 0;
        $tenantsTotalOffboarded = 0;
        $tenantsProcessed       = 0;

        TenantContext::forEach(function (Tenant $tenant) use (
            $today,
            &$tenantsTotalWelcome,
            &$tenantsTotalActivated,
            &$tenantsTotalOffboarded,
            &$tenantsProcessed,
        ) {
            $tenantsProcessed++;
            $this->line("→ Tenant [{$tenant->id}] {$tenant->slug}");

            [$welcomed, $activated, $offboarded] = $this->processTenant($today);

            $tenantsTotalWelcome    += $welcomed;
            $tenantsTotalActivated  += $activated;
            $tenantsTotalOffboarded += $offboarded;
        });

        $this->info("Done. Tenants processed: {$tenantsProcessed}. Welcome emails attempted: {$tenantsTotalWelcome}. New employee records: {$tenantsTotalActivated}. Offboarded: {$tenantsTotalOffboarded}.");
    }

    /**
     * Per-tenant work. Runs inside TenantContext, so all model queries are
     * automatically scoped to the current tenant by the global scope + RLS.
     *
     * @return array{0:int,1:int,2:int} [welcomed, activated, offboarded]
     */
    private function processTenant(Carbon $today): array
    {
        $controller = app(OnboardingController::class);

        // ── 1. WELCOME EMAIL: send to any onboarding whose start_date == today
        $toWelcome = Onboarding::with(['personalDetail', 'workDetail', 'employee'])
            ->where('welcome_email_sent', false)
            ->whereHas('workDetail', fn ($q) => $q->whereDate('start_date', $today))
            ->get();

        if ($toWelcome->isEmpty()) {
            $this->line('   no onboardings starting today.');
        } else {
            $this->line("   found {$toWelcome->count()} onboarding(s) needing welcome email.");
        }

        $welcomed   = 0;
        $activated  = 0;

        foreach ($toWelcome as $ob) {
            $startDate = $ob->workDetail?->start_date?->toDateString();
            if (!$startDate) continue;

            $employee = Employee::firstOrCreate(
                ['onboarding_id' => $ob->id],
                ['active_from'   => $startDate]
            );

            if ($employee->wasRecentlyCreated || empty($employee->full_name)) {
                $employee->populateFromOnboarding();
                $activated++;
            }

            $sent = $controller->sendWelcomeEmail($ob);
            $welcomed++;

            $this->line('   welcome ' . ($sent ? 'SENT ✓' : 'FAILED ✗')
                . ' → ' . ($ob->personalDetail?->full_name ?? 'Unknown')
                . ' (' . ($ob->workDetail?->company_email ?? $ob->personalDetail?->personal_email ?? 'no email') . ')');

            if ($ob->workDetail?->exit_date) {
                Offboarding::createFromEmployee($employee);
            }
        }

        // ── 2. OFFBOARD at 23:59 only ────────────────────────────────────────
        $offboarded = 0;
        $now = Carbon::now();

        if ($now->format('H:i') >= '23:59') {
            $exiting = Employee::whereNotNull('exit_date')
                ->whereDate('exit_date', $today)
                ->whereNull('active_until')
                ->get();

            foreach ($exiting as $emp) {
                Offboarding::createFromEmployee($emp);

                $emp->update([
                    'active_until'      => $today,
                    'employment_status' => in_array($emp->employment_status, ['resigned', 'terminated', 'contract_ended'])
                        ? $emp->employment_status
                        : 'resigned',
                ]);

                if ($emp->user_id) {
                    \App\Models\User::where('id', $emp->user_id)->update(['is_active' => false]);
                }

                $this->line("   offboarded: {$emp->full_name} (exit: {$today->toDateString()})");
                $offboarded++;
            }
        }

        return [$welcomed, $activated, $offboarded];
    }
}
