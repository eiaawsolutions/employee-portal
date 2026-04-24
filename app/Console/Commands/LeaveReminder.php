<?php

namespace App\Console\Commands;

use App\Mail\PendingLeaveReminderMail;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LeaveReminder extends Command
{
    protected $signature   = 'leave:remind-managers';
    protected $description = 'Send daily reminders to managers with pending leave requests — iterates every active tenant.';

    public function handle(): void
    {
        $this->info('Checking for pending leave requests across all tenants...');

        $totalSent = 0;
        $totalSkipped = 0;

        TenantContext::forEach(function (Tenant $tenant) use (&$totalSent, &$totalSkipped) {
            [$sent, $skipped] = $this->processTenant($tenant);
            $totalSent += $sent;
            $totalSkipped += $skipped;
        });

        $this->info("Done. Total sent: {$totalSent}, skipped: {$totalSkipped}");
    }

    /**
     * @return array{0:int,1:int}  [sent, skipped]
     */
    private function processTenant(Tenant $tenant): array
    {
        $managersWithPending = Employee::whereNull('active_until')
            ->whereHas('directReports', function ($q) {
                $q->whereHas('leaveApplications', fn ($lq) => $lq->where('status', 'pending'));
            })
            ->with('user')
            ->get();

        if ($managersWithPending->isEmpty()) {
            return [0, 0];
        }

        $this->line("→ Tenant [{$tenant->id}] {$tenant->slug} — {$managersWithPending->count()} manager(s) with pending requests");

        $sent = 0;
        $skipped = 0;

        foreach ($managersWithPending as $manager) {
            $managerEmail = $manager->user?->work_email;

            if (!$managerEmail) {
                $this->warn("   skipped #{$manager->id} ({$manager->full_name}) — no work email.");
                $skipped++;
                continue;
            }

            $directReportIds = Employee::where('manager_id', $manager->id)->pluck('id');
            $pendingApplications = LeaveApplication::with(['employee', 'leaveType'])
                ->whereIn('employee_id', $directReportIds)
                ->where('status', 'pending')
                ->orderBy('created_at')
                ->get();

            if ($pendingApplications->isEmpty()) {
                continue;
            }

            try {
                Mail::to($managerEmail)->send(new PendingLeaveReminderMail($manager, $pendingApplications));
                $sent++;
                $this->line("   sent → {$manager->full_name} ({$managerEmail}) — {$pendingApplications->count()} pending");
            } catch (\Exception $e) {
                Log::warning("Failed to send leave reminder to manager #{$manager->id} (tenant {$tenant->id}): " . $e->getMessage());
                $this->error("   FAILED → {$manager->full_name} — {$e->getMessage()}");
                $skipped++;
            }
        }

        return [$sent, $skipped];
    }
}
