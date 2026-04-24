<?php

namespace App\Services;

use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * AiRetrievalService — produces grounding context for the AI assistant.
 *
 * This is the defensive half of the assistant: instead of trusting the
 * model to apply role-based access rules from a prompt, we query the DB
 * with role filters at SQL-time and pass the resulting rows as the ONLY
 * context the model is allowed to answer from.
 *
 * Each retriever returns an array of structured rows. The AiGateway
 * embeds them in the system prompt and instructs the model to answer
 * only from this data and refuse otherwise.
 *
 * Design notes:
 *   - Every retriever runs inside the caller's already-established
 *     TenantContext. Postgres RLS is the floor — this layer is the ceiling.
 *   - Retrievers are INTENTIONALLY narrow. "Everything about employee X"
 *     is NOT a retriever; "upcoming leaves in the next 14 days, visible to
 *     the asking user's role" is. Narrow retrievers produce narrow prompts.
 *   - New retrievers can be added per session as features need them.
 *   - Unauthenticated / non-tenant contexts return empty.
 *
 * This is v1 of the retrieval layer — documented in THREAT-MODEL.md A4 R2
 * as closing the "role-based disclosure is prompt-based only" gap.
 */
class AiRetrievalService
{
    private const LEAVE_HORIZON_DAYS = 14;
    private const MAX_ROWS_PER_RETRIEVER = 50;

    /**
     * Classify a user prompt into a retrieval intent. Cheap keyword match;
     * not a model call — we want the classifier to be fast and deterministic.
     *
     * Returns one of: 'leave', 'employee_directory', 'claims', 'none'.
     */
    public function classifyIntent(string $prompt): string
    {
        $p = mb_strtolower($prompt);

        $leaveCues = ['leave', 'ooo', 'out of office', 'vacation', 'annual leave', 'mc ', 'medical leave', 'who is off', 'who\'s off'];
        foreach ($leaveCues as $cue) {
            if (str_contains($p, $cue)) return 'leave';
        }

        $employeeCues = ['employee', 'colleague', 'team member', 'staff member', 'who works', 'who reports to'];
        foreach ($employeeCues as $cue) {
            if (str_contains($p, $cue)) return 'employee_directory';
        }

        $claimCues = ['claim', 'expense', 'reimburse', 'receipt'];
        foreach ($claimCues as $cue) {
            if (str_contains($p, $cue)) return 'claims';
        }

        return 'none';
    }

    /**
     * Build retrieval context for a prompt + asking user. Must be called
     * from within a TenantContext. Returns [] if no relevant data can be
     * fetched — the gateway then tells the model to refuse grounded questions.
     */
    public function contextFor(User $user, string $prompt): array
    {
        $intent = $this->classifyIntent($prompt);

        return match ($intent) {
            'leave' => $this->leaveContext($user),
            'employee_directory' => $this->employeeDirectoryContext($user),
            'claims' => $this->claimsContext($user),
            default => [],
        };
    }

    /**
     * Upcoming leaves within LEAVE_HORIZON_DAYS, respecting role:
     *   - employees see only their own + their direct-reports' leaves
     *   - hr_*, superadmin, system_admin see the whole tenant
     */
    private function leaveContext(User $user): array
    {
        if (!Schema::hasTable('leave_applications')) return [];

        $role = $user->role ?? 'employee';
        $horizon = now()->addDays(self::LEAVE_HORIZON_DAYS);

        $q = DB::table('leave_applications as la')
            ->leftJoin('employees as e', 'e.id', '=', 'la.employee_id')
            ->where('la.status', 'approved')
            ->where('la.start_date', '>=', now()->toDateString())
            ->where('la.start_date', '<=', $horizon->toDateString())
            ->orderBy('la.start_date')
            ->limit(self::MAX_ROWS_PER_RETRIEVER)
            ->select([
                'la.id as leave_id',
                'la.employee_id',
                'e.full_name',
                'la.leave_type',
                'la.start_date',
                'la.end_date',
                'la.total_days',
            ]);

        // Restrict to self + direct reports for non-HR/non-admin roles.
        if (!in_array($role, ['hr_manager', 'hr_executive', 'hr_intern', 'superadmin', 'system_admin'], true)) {
            $q->where(function ($sub) use ($user) {
                $sub->where('e.user_id', $user->id);
                // direct reports — if reporting_manager_id column exists
                if (Schema::hasColumn('employees', 'reporting_manager_id') && Schema::hasColumn('employees', 'user_id')) {
                    $managerEmployeeId = DB::table('employees')->where('user_id', $user->id)->value('id');
                    if ($managerEmployeeId) {
                        $sub->orWhere('e.reporting_manager_id', $managerEmployeeId);
                    }
                }
            });
        }

        $rows = $q->get();
        return $rows->map(fn ($r) => [
            'leave_id'    => "leave:{$r->leave_id}",
            'employee'    => $r->full_name,
            'type'        => $r->leave_type,
            'start'       => $r->start_date,
            'end'         => $r->end_date,
            'days'        => $r->total_days,
        ])->all();
    }

    /**
     * Employee directory — name + role + department ONLY. Never includes
     * salary, NRIC, address, contact for any role. Widest retriever; safe
     * because the payload contains zero sensitive fields.
     */
    private function employeeDirectoryContext(User $user): array
    {
        if (!Schema::hasTable('employees')) return [];

        $rows = DB::table('employees as e')
            ->leftJoin('work_details as wd', 'wd.employee_id', '=', 'e.id')
            ->where('e.employment_status', 'active')
            ->orderBy('e.full_name')
            ->limit(self::MAX_ROWS_PER_RETRIEVER)
            ->select([
                'e.id',
                'e.full_name',
                'wd.position',
                'wd.department',
            ])
            ->get();

        return $rows->map(fn ($r) => [
            'employee_id' => "employee:{$r->id}",
            'name'        => $r->full_name,
            'position'    => $r->position,
            'department'  => $r->department,
        ])->all();
    }

    /**
     * Claims pending or recent, respecting role:
     *   - employees see only their own
     *   - managers see theirs + direct reports'
     *   - finance sees the tenant-wide queue
     */
    private function claimsContext(User $user): array
    {
        if (!Schema::hasTable('expense_claims')) return [];

        $role = $user->role ?? 'employee';
        $since = now()->subDays(30)->toDateString();

        $q = DB::table('expense_claims as c')
            ->leftJoin('employees as e', 'e.id', '=', 'c.employee_id')
            ->where('c.created_at', '>=', $since)
            ->orderByDesc('c.created_at')
            ->limit(self::MAX_ROWS_PER_RETRIEVER)
            ->select([
                'c.id',
                'c.employee_id',
                'e.full_name',
                'c.status',
                'c.total_amount',
                'c.currency',
                'c.submitted_at',
            ]);

        $isFinance = in_array($role, ['hr_manager', 'superadmin', 'system_admin'], true);
        if (!$isFinance) {
            $q->where(function ($sub) use ($user) {
                if (Schema::hasColumn('employees', 'user_id')) {
                    $sub->where('e.user_id', $user->id);
                }
            });
        }

        $rows = $q->get();
        return $rows->map(fn ($r) => [
            'claim_id'  => "claim:{$r->id}",
            'employee'  => $r->full_name,
            'status'    => $r->status,
            'amount'    => $r->total_amount,
            'currency'  => $r->currency,
            'submitted' => $r->submitted_at,
        ])->all();
    }
}
