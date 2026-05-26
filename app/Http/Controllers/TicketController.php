<?php

namespace App\Http\Controllers;

use App\Mail\TicketAssignedMail;
use App\Mail\TicketCreatedMail;
use App\Mail\TicketResolvedMail;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketEditLog;
use App\Models\User;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketRaisedNotification;
use App\Notifications\TicketResolvedNotification;
use App\Notifications\TicketUnassignedNotification;
use App\Services\AttachmentProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/**
 * TicketController — adapted from Claritas (commits 308e0d2..ae438e7).
 *
 * Multi-company cluster routing was dropped: a tenant's ticket pool is
 * tenant-scoped, and within a tenant every dept's team handles every
 * ticket in that dept. See docs/PLAN-CLARITAS-REPLICATION.md §0.
 *
 * Postgres adaptations:
 *   - TIMESTAMPDIFF(MINUTE, a, b) → EXTRACT(EPOCH FROM (b - a)) / 60
 *   - FIELD(col, list...) → CASE col WHEN ... END
 *   - LIKE → ILIKE not needed here (exact match on ticket_number prefix).
 */
class TicketController extends Controller
{
    // Status ordering used by ORDER BY: active first, archived last. Mirrors
    // Claritas's FIELD() expression in a Postgres-compatible CASE.
    private const STATUS_SORT_CASE = <<<'SQL'
        CASE status
            WHEN 'Open'        THEN 1
            WHEN 'In Progress' THEN 2
            WHEN 'Pending'     THEN 3
            WHEN 'Resolved'    THEN 4
            WHEN 'Closed'      THEN 5
            ELSE 9
        END
    SQL;

    // ── Self-Service: tickets the user has raised + assigned to them ──────
    // Tab scope:
    //   active   = tickets the user RAISED, status in ACTIVE_STATUSES
    //   assigned = tickets the user is PIC of, status in ACTIVE_STATUSES
    //   archived = tickets the user RAISED or is PIC of, status in ARCHIVED_STATUSES
    public function index(Request $request)
    {
        $user = Auth::user();

        $scope = $request->query('scope', 'active');
        if (! in_array($scope, ['active', 'assigned', 'archived'], true)) {
            $scope = 'active';
        }

        // Each count must match exactly what its tab renders below — otherwise
        // a PIC's Resolved ticket would inflate the Assigned-to-Me badge while
        // sitting silently on the Archived tab.
        $counts = [
            'active' => Ticket::where('user_id', $user->id)
                ->whereIn('status', Ticket::ACTIVE_STATUSES)->count(),
            'assigned' => Ticket::where('assigned_to', $user->id)
                ->whereIn('status', Ticket::ACTIVE_STATUSES)->count(),
            'archived' => Ticket::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('assigned_to', $user->id);
            })->whereIn('status', Ticket::ARCHIVED_STATUSES)->count(),
        ];

        $query = Ticket::with(['creator', 'assignee', 'company'])
            ->orderBy('company_id')
            ->orderBy('department')
            ->orderByRaw(self::STATUS_SORT_CASE)
            ->orderByDesc('created_at');

        if ($scope === 'assigned') {
            // Active tickets the user is PIC of. Terminal ones move to Archived
            // (where the OR-clause picks them up) so this tab never mixes.
            $query->where('assigned_to', $user->id)
                ->whereIn('status', Ticket::ACTIVE_STATUSES);
            $statusOptions = Ticket::ACTIVE_STATUSES;
        } elseif ($scope === 'archived') {
            // Terminal tickets either RAISED or PIC'd by the user, so a PIC
            // retains visibility of their finished work after it leaves Assigned.
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('assigned_to', $user->id);
            })->whereIn('status', Ticket::ARCHIVED_STATUSES);
            $statusOptions = Ticket::ARCHIVED_STATUSES;
        } else {
            $query->where('user_id', $user->id)
                ->whereIn('status', Ticket::ACTIVE_STATUSES);
            $statusOptions = Ticket::ACTIVE_STATUSES;
        }

        if ($request->filled('status') && in_array($request->status, $statusOptions, true)) {
            $query->where('status', $request->status);
        }

        $tickets = $query->paginate(100)->withQueryString();

        // Pre-group: Company name → Department → Tickets
        $grouped = $tickets->getCollection()
            ->groupBy(fn ($t) => $t->company?->name ?: '— Unassigned Company —')
            ->map(fn ($byCompany) => $byCompany->groupBy('department'));

        $analytics = null;
        if ($scope === 'assigned') {
            $analytics = $this->buildPicAnalytics($user);
        }

        return view('tickets.index', [
            'tickets' => $tickets,
            'grouped' => $grouped,
            'scope' => $scope,
            'counts' => $counts,
            'statusOptions' => $statusOptions,
            'analytics' => $analytics,
        ]);
    }

    // ── Ticket Management: managers/PICs view ─────────────────────────────
    public function manage(Request $request)
    {
        $user = Auth::user();
        if (! $user->canAccessTicketManagement()) {
            abort(403);
        }

        $scope = $request->query('scope', 'all');
        if (! in_array($scope, ['all', 'assigned', 'archived'], true)) {
            $scope = 'all';
        }

        $base = function () use ($user, $request) {
            $q = Ticket::visibleTo($user);
            if ($request->filled('status') && in_array($request->status, Ticket::STATUSES, true)) {
                $q->where('status', $request->status);
            }
            if ($request->filled('department') && in_array($request->department, Ticket::DEPARTMENTS, true)) {
                $q->where('department', $request->department);
            }

            return $q;
        };

        $managedDepartments = Ticket::departmentsManagedBy($user);

        $counts = [
            'all' => $base()->whereIn('status', Ticket::ACTIVE_STATUSES)->count(),
            'assigned' => $base()->whereIn('status', Ticket::ACTIVE_STATUSES)->where('assigned_to', $user->id)->count(),
            'archived' => $base()->whereIn('status', Ticket::ARCHIVED_STATUSES)->count(),
        ];

        $query = $base()
            ->with(['creator.employee', 'assignee', 'company'])
            ->orderBy('company_id')
            ->orderBy('department')
            ->orderByRaw(self::STATUS_SORT_CASE)
            ->orderByDesc('created_at');

        if ($scope === 'archived') {
            $query->whereIn('status', Ticket::ARCHIVED_STATUSES);
        } else {
            $query->whereIn('status', Ticket::ACTIVE_STATUSES);
            if ($scope === 'assigned') {
                $query->where('assigned_to', $user->id);
            }
        }

        $tickets = $query->paginate(100)->withQueryString();

        $grouped = $tickets->getCollection()
            ->groupBy(fn ($t) => $t->company?->name ?: '— Unassigned Company —')
            ->map(fn ($byCompany) => $byCompany->groupBy('department'));

        // Superadmin: list each department's manager (one per dept) for the
        // routing column. Excludes superadmin/system_admin (system-wide) and
        // *_executive (support roles, not the manager themselves).
        $departmentManagers = [];
        if ($user->canViewAllTickets()) {
            $uniqueDepts = $tickets->getCollection()->pluck('department')->unique();
            $excludeRoles = ['superadmin', 'system_admin', 'hr_executive', 'it_executive', 'finance_executive'];
            foreach ($uniqueDepts as $dept) {
                $departmentManagers[$dept] = Ticket::picPoolForDepartment($dept, false)
                    ->whereNotIn('users.role', $excludeRoles)
                    ->select('users.id', 'users.name', 'users.role')
                    ->orderBy('users.name')
                    ->get();
            }
        }

        $analytics = null;
        if ($user->canViewAllTickets()) {
            $analytics = $this->buildAnalytics();
        } elseif (! empty($managedDepartments)) {
            $analytics = $this->buildManagerAnalytics($managedDepartments);
        }

        return view('tickets.manage', [
            'tickets' => $tickets,
            'grouped' => $grouped,
            'managedDepartments' => $managedDepartments,
            'scope' => $scope,
            'counts' => $counts,
            'departmentManagers' => $departmentManagers,
            'analytics' => $analytics,
        ]);
    }

    /** Superadmin analytics — tenant-wide. */
    private function buildAnalytics(): array
    {
        $byPriority = $this->countActiveByPriority(Ticket::query());

        $allCompanies = Company::orderBy('name')->get(['id', 'name']);
        $resolutionData = $this->computeResolutionStats(
            Ticket::query(),
            $allCompanies,
            null
        );

        return array_merge([
            'mode' => 'superadmin',
            'totalActive' => array_sum($byPriority),
            'byPriority' => $byPriority,
        ], $resolutionData);
    }

    /** Manager analytics — scoped to managed departments. */
    private function buildManagerAnalytics(array $managedDepartments): array
    {
        $card1Query = Ticket::whereIn('tickets.department', $managedDepartments);
        $byPriority = $this->countActiveByPriority($card1Query);

        $availableCompanies = Company::orderBy('name')->get(['id', 'name']);
        $resolutionData = $this->computeResolutionStats(
            Ticket::whereIn('tickets.department', $managedDepartments),
            $availableCompanies,
            $managedDepartments
        );

        return array_merge([
            'mode' => 'manager',
            'totalActive' => array_sum($byPriority),
            'byPriority' => $byPriority,
            'managedDepartments' => $managedDepartments,
        ], $resolutionData);
    }

    private function countActiveByPriority($baseQuery): array
    {
        $raw = (clone $baseQuery)
            ->whereIn('tickets.status', Ticket::ACTIVE_STATUSES)
            ->select('priority', DB::raw('COUNT(*) as cnt'))
            ->groupBy('priority')
            ->pluck('cnt', 'priority')
            ->toArray();

        $byPriority = [];
        foreach (Ticket::PRIORITIES as $p) {
            $byPriority[$p] = (int) ($raw[$p] ?? 0);
        }

        return $byPriority;
    }

    /**
     * Card 2 (PIC stats) + Card 3 (department health). Postgres syntax —
     * EXTRACT(EPOCH FROM (end - start)) / 60 replaces MySQL TIMESTAMPDIFF.
     */
    private function computeResolutionStats($baseQuery, $availableCompanies, ?array $deptList): array
    {
        $resolvedBase = (clone $baseQuery)
            ->where('tickets.status', 'Resolved')
            ->whereNotNull('tickets.resolved_at');

        // Postgres-native: epoch difference in minutes.
        $avgMinutesSql = 'AVG(EXTRACT(EPOCH FROM (resolved_at - COALESCE(assigned_at, created_at))) / 60)';

        // ── Card 2 — per (PIC, company) avg resolution time ──────────────
        $picRows = (clone $resolvedBase)
            ->whereNotNull('assigned_to')
            ->select(
                'assigned_to',
                'company_id',
                DB::raw('COUNT(*) as cnt'),
                DB::raw("{$avgMinutesSql} as avg_minutes")
            )
            ->groupBy('assigned_to', 'company_id')
            ->get();

        $picIds = $picRows->pluck('assigned_to')->unique()->values();
        $picNames = User::whereIn('id', $picIds)->pluck('name', 'id');

        $picStats = ['__all__' => []];
        foreach ($availableCompanies as $c) {
            $picStats[(string) $c->id] = [];
        }

        $picAllAccum = [];
        foreach ($picRows as $row) {
            $picId = (int) $row->assigned_to;
            $companyId = $row->company_id ? (string) $row->company_id : null;
            $cnt = (int) $row->cnt;
            $avgMinutes = (int) round((float) $row->avg_minutes);

            if ($companyId !== null && isset($picStats[$companyId])) {
                $picStats[$companyId][] = $this->buildPerfRow(
                    ['name' => $picNames[$picId] ?? 'Unknown'], $cnt, $avgMinutes
                );
            }

            $picAllAccum[$picId] ??= ['weightedSum' => 0, 'totalCount' => 0];
            $picAllAccum[$picId]['weightedSum'] += $avgMinutes * $cnt;
            $picAllAccum[$picId]['totalCount'] += $cnt;
        }

        foreach ($picAllAccum as $picId => $acc) {
            $combinedAvg = (int) round($acc['weightedSum'] / max(1, $acc['totalCount']));
            $picStats['__all__'][] = $this->buildPerfRow(
                ['name' => $picNames[$picId] ?? 'Unknown'], $acc['totalCount'], $combinedAvg
            );
        }

        foreach ($picStats as $key => $list) {
            usort($list, fn ($a, $b) => $a['avg_minutes'] <=> $b['avg_minutes']);
            $picStats[$key] = $list;
        }

        // ── Card 3 — per (department, company) avg + health tier ─────────
        $deptRows = (clone $resolvedBase)
            ->select(
                'department',
                'company_id',
                DB::raw('COUNT(*) as cnt'),
                DB::raw("{$avgMinutesSql} as avg_minutes")
            )
            ->groupBy('department', 'company_id')
            ->get();

        $deptList = $deptList ?: Ticket::DEPARTMENTS;

        // Seed every (company, dept) cell so the card always shows a complete
        // matrix. No cluster filter — every dept is conceptually available
        // for every sub-company within a tenant.
        $deptStats = ['__all__' => []];
        foreach ($availableCompanies as $c) {
            $deptStats[(string) $c->id] = [];
        }
        $emptyEntry = $this->buildPerfRow(['department' => null], 0, null);

        foreach ($deptList as $dept) {
            $entry = array_merge($emptyEntry, ['department' => $dept]);
            $deptStats['__all__'][$dept] = $entry;
            foreach ($availableCompanies as $c) {
                $deptStats[(string) $c->id][$dept] = $entry;
            }
        }

        $deptAllAccum = [];
        foreach ($deptRows as $row) {
            $dept = $row->department;
            if (! in_array($dept, $deptList, true)) {
                continue;
            }
            $companyId = $row->company_id ? (string) $row->company_id : null;
            $cnt = (int) $row->cnt;
            $avgMinutes = (int) round((float) $row->avg_minutes);

            if ($companyId !== null && isset($deptStats[$companyId][$dept])) {
                $deptStats[$companyId][$dept] = $this->buildPerfRow(
                    ['department' => $dept], $cnt, $avgMinutes
                );
            }

            $deptAllAccum[$dept] ??= ['weightedSum' => 0, 'totalCount' => 0];
            $deptAllAccum[$dept]['weightedSum'] += $avgMinutes * $cnt;
            $deptAllAccum[$dept]['totalCount'] += $cnt;
        }

        foreach ($deptAllAccum as $dept => $acc) {
            $combinedAvg = (int) round($acc['weightedSum'] / max(1, $acc['totalCount']));
            $deptStats['__all__'][$dept] = $this->buildPerfRow(
                ['department' => $dept], $acc['totalCount'], $combinedAvg
            );
        }

        $tierOrder = ['good' => 1, 'amber' => 2, 'poor' => 3, 'nodata' => 4];
        foreach ($deptStats as $key => $list) {
            $arr = array_values($list);
            usort($arr, function ($a, $b) use ($tierOrder) {
                $tierCmp = ($tierOrder[$a['tier']] ?? 9) <=> ($tierOrder[$b['tier']] ?? 9);
                if ($tierCmp !== 0) {
                    return $tierCmp;
                }

                return ($a['avg_minutes'] ?? PHP_INT_MAX) <=> ($b['avg_minutes'] ?? PHP_INT_MAX);
            });
            $deptStats[$key] = $arr;
        }

        $deptTierCounts = [];
        foreach ($deptStats as $key => $list) {
            $counts = ['good' => 0, 'amber' => 0, 'poor' => 0, 'nodata' => 0];
            foreach ($list as $entry) {
                $counts[$entry['tier']] = ($counts[$entry['tier']] ?? 0) + 1;
            }
            $deptTierCounts[$key] = $counts;
        }

        return [
            'picStats' => $picStats,
            'deptStats' => $deptStats,
            'deptTierCounts' => $deptTierCounts,
            'availableCompanies' => $availableCompanies->map(fn ($c) => ['id' => (string) $c->id, 'name' => $c->name])->values()->toArray(),
        ];
    }

    /** PIC view analytics (Assigned to Me tab) — scoped to one user. */
    private function buildPicAnalytics(User $user): array
    {
        $byPriority = $this->countActiveByPriority(
            Ticket::where('assigned_to', $user->id)
        );

        $myStats = Ticket::where('assigned_to', $user->id)
            ->where('status', 'Resolved')
            ->whereNotNull('resolved_at')
            ->selectRaw('COUNT(*) AS cnt, AVG(EXTRACT(EPOCH FROM (resolved_at - COALESCE(assigned_at, created_at))) / 60) AS avg_minutes')
            ->first();

        $myCount = (int) ($myStats->cnt ?? 0);
        $myAvgMinutes = $myCount > 0 ? (int) round((float) $myStats->avg_minutes) : null;

        $picStats = ['__all__' => []];
        if ($myCount > 0) {
            $picStats['__all__'][] = $this->buildPerfRow(
                ['name' => $user->name],
                $myCount,
                $myAvgMinutes
            );
        }

        $myDept = $user->employee?->department;
        $deptStats = ['__all__' => []];
        $deptTierCounts = ['__all__' => ['good' => 0, 'amber' => 0, 'poor' => 0, 'nodata' => 0]];

        if ($myDept) {
            $deptRow = Ticket::where('department', $myDept)
                ->where('status', 'Resolved')
                ->whereNotNull('resolved_at')
                ->selectRaw('COUNT(*) AS cnt, AVG(EXTRACT(EPOCH FROM (resolved_at - COALESCE(assigned_at, created_at))) / 60) AS avg_minutes')
                ->first();

            $deptCount = (int) ($deptRow->cnt ?? 0);
            $deptAvgMinutes = $deptCount > 0 ? (int) round((float) $deptRow->avg_minutes) : null;

            $entry = $this->buildPerfRow(
                ['department' => $myDept],
                $deptCount,
                $deptAvgMinutes
            );
            $deptStats['__all__'][] = $entry;
            $deptTierCounts['__all__'][$entry['tier']]++;
        }

        return [
            'mode' => 'pic',
            'totalActive' => array_sum($byPriority),
            'byPriority' => $byPriority,
            'picStats' => $picStats,
            'deptStats' => $deptStats,
            'deptTierCounts' => $deptTierCounts,
            'availableCompanies' => [],
        ];
    }

    private function buildPerfRow(array $base, int $count, ?int $avgMinutes): array
    {
        $widthPct = 0;
        if ($avgMinutes !== null && $avgMinutes > 0) {
            $widthPct = min(100, ($avgMinutes / Ticket::HEALTH_AMBER_MAX_MINUTES) * 100);
        }

        return array_merge($base, [
            'count' => $count,
            'avg_minutes' => $avgMinutes,
            'formatted' => $avgMinutes !== null ? Ticket::formatMinutes($avgMinutes) : '—',
            'tier' => Ticket::healthTier($avgMinutes),
            'width_pct' => $widthPct,
        ]);
    }

    // ── Create form ───────────────────────────────────────────────────────
    public function create()
    {
        $user = Auth::user();
        $userCompany = $user->employee?->company;

        $companies = Company::orderBy('name')->get(['id', 'name']);

        // Default the company_id to the user's own company when their employee
        // record has one; otherwise the first registered company.
        $autoCompanyId = null;
        if ($userCompany) {
            $autoCompanyId = $companies->firstWhere('name', $userCompany)?->id;
        }
        $defaultCompanyId = $autoCompanyId ?? $companies->first()?->id;

        return view('tickets.create', [
            'companies' => $companies,
            'autoCompanyId' => $autoCompanyId,
            'autoCompanyName' => $userCompany,
            'defaultCompanyId' => $defaultCompanyId,
            'priorities' => Ticket::PRIORITIES,
            'departmentSubjects' => Ticket::DEPARTMENT_SUBJECTS,
            'subjectToDepartments' => Ticket::subjectToDepartmentMap(),
            'keywordHints' => Ticket::SUBJECT_KEYWORD_HINTS,
            'departmentsAll' => Ticket::DEPARTMENTS,
        ]);
    }

    // ── Store new ticket ──────────────────────────────────────────────────
    public function store(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'subject' => 'required|string|max:255',
            'subject_other' => 'nullable|string|max:255',
            'description' => 'required|string|max:10000',
            'department' => 'required|in:'.implode(',', Ticket::DEPARTMENTS),
            'priority' => 'required|in:'.implode(',', Ticket::PRIORITIES),
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,gif,webp|valid_file_content',
        ]);

        // If the raiser has an employee record, force the ticket onto their
        // own company regardless of client-submitted company_id.
        $userCompany = $user->employee?->company;
        if ($userCompany) {
            $autoCompanyId = Company::where('name', $userCompany)->value('id');
            if ($autoCompanyId) {
                $data['company_id'] = $autoCompanyId;
            }
        }

        // Subject-driven department resolution. For standardised subjects:
        // re-derive department from the subject→dept map (defence in depth).
        // For "Other": trust the client-submitted department (set via keyword
        // inference or manual override).
        if ($data['subject'] !== 'Other') {
            $map = Ticket::subjectToDepartmentMap();
            if (! isset($map[$data['subject']])) {
                return back()
                    ->withErrors(['subject' => 'Selected subject is not recognised.'])
                    ->withInput();
            }
            $validDepts = $map[$data['subject']];
            if (! in_array($data['department'], $validDepts, true)) {
                if (count($validDepts) === 1) {
                    $data['department'] = $validDepts[0];
                } else {
                    return back()
                        ->withErrors(['department' => 'Please pick which department should handle this subject.'])
                        ->withInput();
                }
            }
        } else {
            $custom = trim($data['subject_other'] ?? '');
            if ($custom === '') {
                return back()
                    ->withErrors(['subject_other' => 'Please describe the subject when picking "Other".'])
                    ->withInput();
            }
        }

        $finalSubject = $data['subject'];
        if ($data['subject'] === 'Other') {
            $finalSubject = 'Other — '.trim($data['subject_other']);
        }

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'company_id' => $data['company_id'],
            'department' => $data['department'],
            'priority' => $data['priority'],
            'subject' => $finalSubject,
            'description' => $data['description'],
            'status' => 'Open',
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $this->storeTicketAttachment($ticket, $file);
            }
        }

        // Notify department managers (no interns — they only get pinged on assignment).
        $managers = $ticket->managersForNotification()->get();

        // Same-department tickets also notify the raiser's reporting (line)
        // manager. Notification-only — does NOT change ticket visibility or
        // the PIC pool. Cross-department tickets keep the standard dept-head
        // routing. De-duplicated against the dept-manager pool so a reporting
        // manager who is also a dept manager isn't pinged twice.
        $reportingManager = $ticket->reportingManagerForSameDeptNotification();
        if ($reportingManager && ! $managers->contains('id', $reportingManager->id)) {
            $managers->push($reportingManager);
        }

        foreach ($managers as $manager) {
            if ($manager->work_email) {
                Mail::to($manager->work_email)->queue(new TicketCreatedMail($ticket, $manager));
            }
        }
        if ($managers->isNotEmpty()) {
            Notification::send($managers, new TicketRaisedNotification($ticket->fresh(['creator'])));
        }

        // Same-dept reporting manager who has no (or an inactive) User account
        // — email-only fallback. The bell ping can't reach them (notifications
        // FK to users) until they register.
        $reportingManagerEmp = $ticket->reportingManagerEmployeeForEmailOnly();
        if ($reportingManagerEmp) {
            Mail::to($reportingManagerEmp->company_email)
                ->queue(new TicketCreatedMail($ticket, $reportingManagerEmp));
        }

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket created.');
    }

    // ── Ticket detail / chat view ─────────────────────────────────────────
    public function show(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
        $this->authorizeView($user, $ticket);

        $ticket->load(['creator.employee', 'assignee', 'messages.sender', 'attachments']);

        // Manage controls (Add/Remove PIC, Update Status) only when navigating
        // here from the Ticket Management page (?from=manage). Without this,
        // a manager viewing their own raised ticket from /tickets would see
        // manager controls — but they were acting as the raiser there.
        $hasManageRole = $user->canManageTicketsForDepartment($ticket->department)
                         || $user->isSuperadmin() || $user->isSystemAdmin();
        $cameFromManagePage = $request->query('from') === 'manage';
        $canManage = $hasManageRole && $cameFromManagePage;

        $assigneePool = collect();
        if ($canManage) {
            $assigneePool = $ticket->eligiblePicQuery()
                ->orderBy('name')
                ->get();
        }

        return view('tickets.show', [
            'ticket' => $ticket,
            'assigneePool' => $assigneePool,
            'canManage' => $canManage,
            'statuses' => Ticket::STATUSES,
        ]);
    }

    // ── Re-route a misfiled ticket (manager / superadmin only) ────────────
    public function editAdmin(Ticket $ticket)
    {
        $this->authorizeEdit($ticket);

        $ticket->load('attachments', 'company', 'creator');

        return view('tickets.edit-admin', [
            'ticket' => $ticket,
            'departments' => Ticket::DEPARTMENTS,
        ]);
    }

    public function updateAdmin(Request $request, Ticket $ticket)
    {
        $this->authorizeEdit($ticket);

        $data = $request->validate([
            'department' => 'required|in:'.implode(',', Ticket::DEPARTMENTS),
            'note' => 'nullable|string|max:1000',
        ]);

        if ($data['department'] === $ticket->department) {
            return redirect()->route('tickets.show', ['ticket' => $ticket, 'from' => 'manage'])
                ->with('info', 'No changes were saved — department is the same.');
        }

        $changes = [
            'department' => ['from' => $ticket->department, 'to' => $data['department']],
        ];

        DB::transaction(function () use ($ticket, $data, $changes) {
            // Old PIC is no longer in the new dept's eligible pool. Clear PIC
            // + assigned_at so the new dept managers start fresh. Status
            // returns to Open so it appears as new in their inbox.
            $ticket->update([
                'department' => $data['department'],
                'assigned_to' => null,
                'assigned_at' => null,
                'status' => 'Open',
            ]);

            TicketEditLog::create([
                'ticket_id' => $ticket->id,
                'edited_by_user_id' => Auth::id(),
                'changes' => $changes,
                'note' => $data['note'] ?? null,
            ]);
        });

        $ticket->refresh();

        // Notify the new department's managers as if the ticket had just
        // been raised in their queue.
        $managers = $ticket->managersForNotification()->get();

        // If the re-routed department now matches the raiser's own department,
        // their reporting manager is notified too (same rule as creation).
        $reportingManager = $ticket->reportingManagerForSameDeptNotification();
        if ($reportingManager && ! $managers->contains('id', $reportingManager->id)) {
            $managers->push($reportingManager);
        }

        foreach ($managers as $manager) {
            if ($manager->work_email) {
                Mail::to($manager->work_email)->queue(new TicketCreatedMail($ticket, $manager));
            }
        }
        if ($managers->isNotEmpty()) {
            Notification::send($managers, new TicketRaisedNotification($ticket->fresh(['creator'])));
        }

        // Email-only fallback for a same-dept reporting manager without an
        // active User account.
        $reportingManagerEmp = $ticket->reportingManagerEmployeeForEmailOnly();
        if ($reportingManagerEmp) {
            Mail::to($reportingManagerEmp->company_email)
                ->queue(new TicketCreatedMail($ticket, $reportingManagerEmp));
        }

        // After a dept change, the editor may no longer have manage rights on
        // the new department. Redirect to /tickets/manage instead of the show
        // page so authorizeView() doesn't 403 them.
        $user = Auth::user();
        $stillHasAccess = $user->isSuperadmin()
            || $user->isSystemAdmin()
            || $user->canManageTicketsForDepartment($ticket->department);

        if ($stillHasAccess) {
            return redirect()->route('tickets.show', ['ticket' => $ticket, 'from' => 'manage'])
                ->with('success', 'Department updated. The new department\'s managers have been notified.');
        }

        return redirect()->route('tickets.manage')
            ->with('success', 'Ticket moved to '.$ticket->department.'. Its new department\'s managers have been notified, and it is no longer in your inbox.');
    }

    /**
     * Permission gate for the Edit Department action. Superadmin/system_admin
     * always; otherwise must be a manager of the ticket's current department
     * (so a Tech manager can re-route their own misfiled Tech ticket, but
     * not grab one from another dept).
     */
    private function authorizeEdit(Ticket $ticket): void
    {
        $user = Auth::user();
        $allowed = $user
            && ($user->isSuperadmin()
                || $user->isSystemAdmin()
                || $user->canManageTicketsForDepartment($ticket->department));
        if (! $allowed) {
            abort(403);
        }
    }

    // ── Manager assigns PIC ───────────────────────────────────────────────
    public function assignPic(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
        if (! $user->canManageTicketsForDepartment($ticket->department)
            && ! $user->isSuperadmin() && ! $user->isSystemAdmin()) {
            abort(403);
        }

        $picUserId = $request->input('assigned_pic_user_id');

        // Remove PIC — clear assignment, return to Open. Also clear
        // assigned_at so the next PIC's clock starts fresh.
        if (! $picUserId) {
            $previousPic = $ticket->assigned_to ? User::find($ticket->assigned_to) : null;
            $ticket->update(['assigned_to' => null, 'assigned_at' => null, 'status' => 'Open']);
            if ($previousPic) {
                $previousPic->notify(new TicketUnassignedNotification($ticket->fresh(), $user));
            }

            return back()->with('success', 'PIC removed.');
        }

        // Direct PIC-to-PIC switching not allowed — manager must remove the
        // existing PIC first.
        if ($ticket->assigned_to && (int) $picUserId !== (int) $ticket->assigned_to) {
            return back()->with('error', 'Remove the current PIC before assigning a new one.');
        }

        $request->validate(['assigned_pic_user_id' => 'required|exists:users,id']);

        $ticket->load('creator.employee');
        $isEligible = $ticket->eligiblePicQuery()
            ->where('users.id', $picUserId)
            ->exists();
        if (! $isEligible) {
            return back()->withErrors(['assigned_pic_user_id' => 'Selected user is not eligible to be PIC for this ticket.']);
        }

        $candidate = User::findOrFail($picUserId);

        // Assigning a PIC moves the ticket to In Progress (unless terminal).
        $newStatus = in_array($ticket->status, Ticket::ARCHIVED_STATUSES, true)
            ? $ticket->status
            : 'In Progress';

        $ticket->update([
            'assigned_to' => $picUserId,
            'assigned_at' => now(),
            'status' => $newStatus,
        ]);

        Mail::to($candidate->work_email)->queue(new TicketAssignedMail($ticket->fresh(['creator', 'assignee']), $candidate));
        $candidate->notify(new TicketAssignedNotification($ticket->fresh(), $user));

        return back()->with('success', 'PIC assigned.');
    }

    // ── Update ticket status ──────────────────────────────────────────────
    public function updateStatus(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
        $isManager = $user->canManageTicketsForDepartment($ticket->department)
                     || $user->isSuperadmin() || $user->isSystemAdmin();
        $isAssignee = $ticket->assigned_to === $user->id;

        if (! $isManager && ! $isAssignee) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:'.implode(',', Ticket::STATUSES),
        ]);

        $previousStatus = $ticket->status;
        $newStatus = $request->status;

        // In Progress requires a PIC.
        if ($newStatus === 'In Progress' && empty($ticket->assigned_to)) {
            return back()->withErrors(['status' => 'In Progress requires an assigned PIC. Assign a PIC first.']);
        }

        $isResolutionEvent = $newStatus === 'Resolved'
            && ! in_array($previousStatus, Ticket::ARCHIVED_STATUSES, true);

        $update = ['status' => $newStatus];

        if ($newStatus === 'Resolved' && empty($ticket->resolved_at)) {
            $update['resolved_at'] = now();
        } elseif (in_array($previousStatus, Ticket::ARCHIVED_STATUSES, true)
                && ! in_array($newStatus, Ticket::ARCHIVED_STATUSES, true)) {
            $update['resolved_at'] = null;
        }

        $ticket->update($update);

        if ($isResolutionEvent && $ticket->creator) {
            Mail::to($ticket->creator->work_email)->queue(new TicketResolvedMail($ticket->fresh(['creator', 'assignee'])));
            $ticket->creator->notify(new TicketResolvedNotification($ticket->fresh(['creator', 'assignee'])));
        }

        return back()->with('success', $isResolutionEvent
            ? 'Ticket resolved.'
            : 'Ticket status updated.');
    }

    // ── Authorization helper ──────────────────────────────────────────────
    private function authorizeView(User $user, Ticket $ticket): void
    {
        if ($user->isSuperadmin() || $user->isSystemAdmin()) {
            return;
        }
        if ($ticket->user_id === $user->id || $ticket->assigned_to === $user->id) {
            return;
        }
        if ($user->canManageTicketsForDepartment($ticket->department)) {
            return;
        }
        abort(403);
    }

    /** Persist a single uploaded file as a TicketAttachment. */
    private function storeTicketAttachment(Ticket $ticket, $file): void
    {
        $meta = AttachmentProcessor::store(
            $file,
            'ticket_attachments',
            $ticket->id.'_'
        );
        TicketAttachment::create(array_merge(['ticket_id' => $ticket->id], $meta));
    }
}
