<?php

namespace App\Console\Commands;

use App\Mail\BirthdayWishMail;
use App\Models\Employee;
use App\Models\Tenant;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Iterates every active tenant once per tick and sends a birthday e-card
 * to each tenant's active employees whose birthday is today. Feb 29 babies
 * receive theirs on Feb 28 in non-leap years.
 *
 * Idempotent via employees.birthday_email_sent_year — re-runs in the same
 * calendar year are no-ops.
 *
 * Postgres syntax: EXTRACT(MONTH FROM date_of_birth) / EXTRACT(DAY FROM ...)
 * instead of MySQL MONTH()/DAY().
 */
class SendBirthdayWishes extends Command
{
    protected $signature = 'birthdays:send-wishes';

    protected $description = 'Send a birthday e-card to active employees whose birthday is today (across all tenants).';

    public function handle(): int
    {
        $today = Carbon::today();
        $totalSent = 0;
        $totalFailed = 0;

        TenantContext::forEach(function (Tenant $tenant) use ($today, &$totalSent, &$totalFailed) {
            [$sent, $failed] = $this->processTenant($today);
            if ($sent > 0 || $failed > 0) {
                $this->line("→ Tenant [{$tenant->id}] {$tenant->slug} — sent: {$sent}, failed: {$failed}");
            }
            $totalSent += $sent;
            $totalFailed += $failed;
        });

        $this->info("Done. Total sent: {$totalSent}, failed: {$totalFailed}.");

        return self::SUCCESS;
    }

    /** @return array{0:int,1:int}  [sent, failed] */
    private function processTenant(Carbon $today): array
    {
        $month = $today->month;
        $day = $today->day;
        $year = $today->year;

        $query = Employee::whereNull('active_until')
            ->whereNotNull('company_email')
            ->where('company_email', '!=', '')
            ->whereNotNull('date_of_birth')
            ->where(function ($q) use ($year) {
                $q->whereNull('birthday_email_sent_year')
                    ->orWhere('birthday_email_sent_year', '!=', $year);
            })
            ->where(function ($q) use ($month, $day, $today) {
                $q->where(function ($q2) use ($month, $day) {
                    $q2->whereRaw('EXTRACT(MONTH FROM date_of_birth) = ?', [$month])
                        ->whereRaw('EXTRACT(DAY FROM date_of_birth) = ?', [$day]);
                });
                if ($month === 2 && $day === 28 && ! $today->isLeapYear()) {
                    $q->orWhere(function ($q2) {
                        $q2->whereRaw('EXTRACT(MONTH FROM date_of_birth) = ?', [2])
                            ->whereRaw('EXTRACT(DAY FROM date_of_birth) = ?', [29]);
                    });
                }
            });

        $employees = $query->get();

        $sent = 0;
        $failed = 0;
        foreach ($employees as $emp) {
            try {
                Mail::to($emp->company_email)->send(new BirthdayWishMail($emp));
                $emp->update(['birthday_email_sent_year' => $year]);
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning("BirthdayWishMail failed for employee #{$emp->id} ({$emp->company_email}): ".$e->getMessage());
            }
        }

        return [$sent, $failed];
    }
}
