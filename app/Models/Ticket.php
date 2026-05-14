<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Ticket — internal help-desk record. Per-tenant; within a tenant, owned
 * by one Company (sub-entity). PIC pool is scoped to users in the tenant
 * holding the right role / work_role.
 *
 * Adapted from Claritas (commits 308e0d2..ae438e7) with the multi-company
 * cluster routing dropped — see docs/PLAN-CLARITAS-REPLICATION.md.
 */
class Ticket extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'ticket_number', 'user_id', 'company_id',
        'assigned_to', 'assigned_at',
        'department', 'priority', 'status', 'subject', 'description',
        'resolved_at', 'last_reminder_sent_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'resolved_at' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
    ];

    public const DEPARTMENTS = [
        // Core (pinned at top of dropdowns)
        'HRA', 'Group IT', 'Finance', 'Admin',
        // Extended (alphabetical)
        'Community', 'Consulting', 'Content', 'Design', 'Digital', 'Ecommerce',
        'KOL', 'Management', 'Marketing', 'Media', 'Production', 'Projects', 'Sales', 'Tech',
    ];

    public const PRIORITIES = ['Low', 'Medium', 'High', 'Urgent'];

    /**
     * Status lifecycle:
     *   Open         → ticket raised, no PIC assigned
     *   In Progress  → PIC assigned (set automatically on assignment)
     *   Pending      → no PIC for 24h+ (auto-set by tickets:remind-stale)
     *   Resolved     → terminal: PIC marked it solved
     *   Closed       → terminal: manually closed without resolution
     */
    public const STATUSES = ['Open', 'In Progress', 'Pending', 'Resolved', 'Closed'];

    public const ARCHIVED_STATUSES = ['Resolved', 'Closed'];

    public const ACTIVE_STATUSES = ['Open', 'In Progress', 'Pending'];

    /**
     * Resolution-time thresholds (minutes) used to colour the Department
     * Health card. Avg ≤ GOOD = green, ≤ AMBER = amber, > AMBER = red.
     */
    public const HEALTH_GOOD_MAX_MINUTES = 1440;   // 24 hours

    public const HEALTH_AMBER_MAX_MINUTES = 4320;   // 72 hours

    /**
     * App-role gated departments: PIC eligibility = users.role ∈ list.
     * Used for HRA / IT / Finance / Admin where users carry app-level
     * permissions beyond just being a department manager.
     */
    public const DEPARTMENT_MANAGER_ROLES = [
        'HRA' => ['hr_manager', 'hr_executive', 'superadmin', 'system_admin'],
        'Group IT' => ['it_manager', 'it_executive', 'superadmin', 'system_admin'],
        'Finance' => ['finance_manager', 'finance_executive', 'superadmin', 'system_admin'],
        'Admin' => ['superadmin', 'system_admin'],
    ];

    /**
     * Departments whose PIC pool is determined by Employee.work_role = 'manager'
     * AND Employee.department = <this dept>, instead of by users.role.
     */
    public const WORK_ROLE_MANAGER_DEPARTMENTS = [
        'Community', 'Consulting', 'Content', 'Design', 'Digital', 'Ecommerce',
        'KOL', 'Management', 'Marketing', 'Media', 'Production', 'Projects', 'Sales', 'Tech',
    ];

    /**
     * Roles that are eligible to be assigned as PIC but are NOT considered
     * department managers (e.g. interns can be assigned but not raise
     * tickets on behalf of others or receive "new ticket raised" emails).
     */
    public const DEPARTMENT_PIC_EXTRA_ROLES = [
        'HRA' => ['hr_intern'],
        'Group IT' => ['it_intern'],
    ];

    /**
     * Allowed ticket subjects per department — drives the controlled-vocabulary
     * dropdown on the Raise New Ticket form. Standardised subjects keep
     * analytics aggregations clean.
     */
    public const DEPARTMENT_SUBJECTS = [
        'HRA' => [
            'Incorrect Personal Details',
            'Leave Request Issue',
            'Payroll / Salary Query',
            'Benefits Enquiry',
            'Onboarding Issue',
            'Offboarding / Resignation',
            'Employment Letter Request',
            'Other',
        ],
        'Group IT' => [
            'Email Problem',
            'Laptop / Hardware Issue',
            'Software Installation / Access',
            'Network / Internet Issue',
            'Account Lockout',
            'Password Reset',
            'Printer Issue',
            'Other',
        ],
        'Finance' => [
            'Expense Reimbursement',
            'Invoice Query',
            'Vendor Payment',
            'Tax / Compliance Query',
            'Budget Request',
            'Other',
        ],
        'Admin' => [
            'Office Supplies',
            'Facility / Maintenance',
            'Travel Booking',
            'Meeting Room Booking',
            'General Enquiry',
            'Other',
        ],
        'Community' => [
            'Member Enquiry', 'Event Coordination', 'Communications Request', 'Other',
        ],
        'Consulting' => [
            'Client Engagement', 'Resource Allocation', 'Project Scoping', 'Other',
        ],
        'Content' => [
            'Content Request', 'Editorial Review', 'Publishing Issue', 'Other',
        ],
        'Design' => [
            'Design Request', 'Brand Asset Request', 'Approval Required', 'Other',
        ],
        'Digital' => [
            'Website Issue', 'SEO / Analytics Query', 'Digital Tool Access', 'Other',
        ],
        'Ecommerce' => [
            'Order Issue', 'Payment Issue', 'Inventory Query', 'Customer Complaint', 'Other',
        ],
        'KOL' => [
            'Influencer Engagement', 'Content Collaboration', 'Campaign Brief',
            'Contract / Agreement', 'Payment / Compensation', 'Other',
        ],
        'Management' => [
            'Approval Request', 'Policy Query', 'Strategic Discussion', 'Other',
        ],
        'Marketing' => [
            'Campaign Request', 'Content Approval', 'Brand Asset Request',
            'Analytics Query', 'Other',
        ],
        'Media' => [
            'Media Request', 'Press Enquiry', 'Asset Distribution', 'Other',
        ],
        'Production' => [
            'Equipment Issue', 'Schedule Change', 'Quality Issue', 'Material Request', 'Other',
        ],
        'Projects' => [
            'Project Status Update', 'Resource Request', 'Timeline Change',
            'Risk / Issue Report', 'Other',
        ],
        'Sales' => [
            'Lead Query', 'Pricing Approval', 'Contract Review', 'Commission Issue', 'Other',
        ],
        'Tech' => [
            'Bug Report', 'Feature Request', 'Code Review Request',
            'Deployment Issue', 'Performance Issue', 'Other',
        ],
    ];

    /**
     * Keyword hints used to infer a department from a free-text "Other" subject.
     * First match wins (substring, case-insensitive). Digital sits first so
     * social-platform mentions ("Facebook password") win over IT's "password".
     */
    public const SUBJECT_KEYWORD_HINTS = [
        'Digital' => ['facebook', 'instagram', 'tiktok'],
        'Group IT' => ['laptop', 'computer', 'email', 'outlook', 'wifi', 'network',
            'password', 'login', 'access', 'software', 'install',
            'printer', 'vpn', 'monitor', 'mouse', 'keyboard'],
        'HRA' => ['salary', 'payroll', 'leave', 'onboard', 'offboard',
            'resignat', 'benefit', 'employ', 'contract',
            'office', 'supply', 'maintenance', 'facility',
            'travel', 'booking', 'meeting room', 'stationery'],
        'Finance' => ['invoice', 'payment', 'expense', 'reimburs', 'tax',
            'budget', 'vendor', 'claim', 'receipt'],
        'Marketing' => ['campaign', 'seo', 'analytics', 'marketing', 'ad ', 'ads '],
        'Design' => ['design', 'logo', 'mockup', 'figma', 'graphic'],
        'Tech' => ['bug', 'deploy', 'code review', 'api', 'performance issue',
            'server error'],
    ];

    /**
     * Reverse-lookup: subject → list of departments that accept it. Excludes
     * "Other" (handled separately). Most subjects belong to one department; a
     * few (e.g. "Brand Asset Request") legitimately belong to multiple, in
     * which case the optgroup the user picked from disambiguates client-side
     * and this map validates the (subject, department) pair server-side.
     */
    public static function subjectToDepartmentMap(): array
    {
        $map = [];
        foreach (self::DEPARTMENT_SUBJECTS as $dept => $subjects) {
            foreach ($subjects as $subject) {
                if ($subject === 'Other') {
                    continue;
                }
                $map[$subject][] = $dept;
            }
        }

        return $map;
    }

    /**
     * Server-side mirror of the client-side keyword inference used for "Other"
     * subjects. Returns the first matching department or null when nothing
     * matches.
     */
    public static function inferDepartmentFromText(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }
        $lower = mb_strtolower(trim($text));
        if ($lower === '') {
            return null;
        }
        foreach (self::SUBJECT_KEYWORD_HINTS as $dept => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($lower, $kw)) {
                    return $dept;
                }
            }
        }

        return null;
    }

    // ── Relationships ─────────────────────────────────────────────────────

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class)->orderBy('id');
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class)->orderBy('id');
    }

    public function editLogs()
    {
        return $this->hasMany(TicketEditLog::class)->latest();
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    /**
     * Restrict tickets to those visible on the Ticket Management page.
     * Tenant boundary is enforced by TenantScope + Postgres RLS; this scope
     * adds the within-tenant role check.
     *
     *  - Superadmin / system_admin → everything in the tenant.
     *  - Department managers → tickets in their managed department(s)
     *    plus any ticket assigned to them as PIC.
     *  - Non-managers → only tickets assigned to them.
     *
     * A user's own RAISED tickets are NOT included here — those belong on
     * the Self-Service page (/tickets) with its own filter.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperadmin() || $user->isSystemAdmin()) {
            return $query;
        }

        $managedDepartments = self::departmentsManagedBy($user);

        if (empty($managedDepartments)) {
            return $query->where('assigned_to', $user->id);
        }

        return $query->where(function ($q) use ($managedDepartments, $user) {
            $q->whereIn('department', $managedDepartments)
                ->orWhere('assigned_to', $user->id);
        });
    }

    public function scopeForDepartment(Builder $query, string $department): Builder
    {
        return $query->where('department', $department);
    }

    // ── Manager / PIC helpers ─────────────────────────────────────────────

    public static function departmentsManagedBy(User $user): array
    {
        $managed = [];
        foreach (self::DEPARTMENTS as $dept) {
            if (self::isManagerOf($user, $dept)) {
                $managed[] = $dept;
            }
        }

        return $managed;
    }

    /**
     * True if $user is a manager of $department. Role/work_role gated; no
     * cross-company filter (tickets are tenant-scoped, not company-scoped
     * for routing).
     */
    public static function isManagerOf(User $user, string $department): bool
    {
        if (in_array($department, self::WORK_ROLE_MANAGER_DEPARTMENTS, true)) {
            $emp = $user->employee;

            return $emp
                && $emp->work_role === 'manager'
                && $emp->department === $department;
        }
        $deptRoles = self::DEPARTMENT_MANAGER_ROLES[$department] ?? [];

        return in_array($user->role, $deptRoles, true);
    }

    /**
     * Eligible PIC pool for this ticket. Includes managers + extras
     * (e.g. interns) so the assignment dropdown can pick any team member.
     */
    public function eligiblePicQuery()
    {
        return self::picPoolForDepartment($this->department, includePicExtras: true);
    }

    /**
     * Department managers (no interns) for new-ticket emails and stale-ticket
     * reminders.
     */
    public function managersForNotification()
    {
        return self::picPoolForDepartment($this->department, includePicExtras: false);
    }

    /**
     * Returns a User query for everyone eligible for the given department in
     * the current tenant. TenantScope auto-scopes the User query.
     *
     * @param  bool  $includePicExtras  When true, also includes
     *                                  DEPARTMENT_PIC_EXTRA_ROLES (interns) —
     *                                  used for PIC dropdown. When false,
     *                                  managers only — used for notifications.
     */
    public static function picPoolForDepartment(string $department, bool $includePicExtras = false)
    {
        $query = User::where('is_active', true);

        if (in_array($department, self::WORK_ROLE_MANAGER_DEPARTMENTS, true)) {
            $query->where(function ($outer) use ($department) {
                $outer->whereHas('employee', function ($q) use ($department) {
                    $q->where('work_role', 'manager')->where('department', $department);
                })->orWhereIn('role', ['superadmin', 'system_admin']);
            });

            return $query;
        }

        $managerRoles = self::DEPARTMENT_MANAGER_ROLES[$department] ?? [];
        $extraRoles = $includePicExtras
            ? (self::DEPARTMENT_PIC_EXTRA_ROLES[$department] ?? [])
            : [];
        $deptRoles = array_values(array_unique(array_merge($managerRoles, $extraRoles)));

        return $query->whereIn('role', $deptRoles);
    }

    // ── Ticket numbering ──────────────────────────────────────────────────

    /**
     * Generate a unique ticket number in the format TIC-YYYY-0001 per tenant
     * per year. Uses a transaction with row-level lock to avoid race
     * conditions inside one tenant. Across tenants the (tenant_id, ticket_number)
     * composite unique handles isolation.
     */
    public static function generateTicketNumber(): string
    {
        $year = date('Y');
        $prefix = "TIC-{$year}-";

        return DB::transaction(function () use ($prefix) {
            // TenantScope is auto-applied — this only sees the current tenant's
            // tickets, which is exactly the sequence we want to increment.
            $latest = static::where('ticket_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $nextSeq = 1;
            if ($latest) {
                $lastSeq = (int) substr($latest->ticket_number, strlen($prefix));
                $nextSeq = $lastSeq + 1;
            }

            return $prefix.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
        });
    }

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = self::generateTicketNumber();
            }
        });
    }

    // ── Display helpers ───────────────────────────────────────────────────

    public function statusColor(): string
    {
        return match ($this->status) {
            'Open' => 'secondary',
            'In Progress' => 'warning',
            'Pending' => 'info',
            'Resolved' => 'success',
            'Closed' => 'dark',
            default => 'secondary',
        };
    }

    public function priorityColor(): string
    {
        return match ($this->priority) {
            'Low' => 'secondary',
            'Medium' => 'primary',
            'High' => 'warning',
            'Urgent' => 'danger',
            default => 'secondary',
        };
    }

    public function isArchivedStatus(): bool
    {
        return in_array($this->status, self::ARCHIVED_STATUSES, true);
    }

    /**
     * Time the PIC took to resolve — measured from assigned_at, not from
     * creation. A ticket that sat unassigned for days isn't the PIC's fault.
     * Falls back to created_at for legacy tickets without assigned_at.
     * Returns null until the ticket is terminal.
     */
    public function timeToResolve(): ?string
    {
        if (! $this->resolved_at) {
            return null;
        }
        $start = $this->assigned_at ?? $this->created_at;
        if (! $start) {
            return null;
        }
        $diff = $start->diff($this->resolved_at);

        $parts = [];
        if ($diff->y > 0) {
            $parts[] = $diff->y.'y';
        }
        if ($diff->m > 0) {
            $parts[] = $diff->m.'mo';
        }
        if ($diff->d > 0) {
            $parts[] = $diff->d.'d';
        }
        if ($diff->h > 0 && count($parts) < 2) {
            $parts[] = $diff->h.'h';
        }
        if ($diff->i > 0 && count($parts) < 2) {
            $parts[] = $diff->i.'m';
        }

        if (empty($parts)) {
            return '< 1m';
        }

        return implode(' ', array_slice($parts, 0, 2));
    }

    public static function formatMinutes(int $minutes): string
    {
        if ($minutes <= 0) {
            return '< 1m';
        }

        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $mins = $minutes % 60;

        $parts = [];
        if ($days > 0) {
            $parts[] = $days.'d';
        }
        if ($hours > 0) {
            $parts[] = $hours.'h';
        }
        if ($mins > 0 && count($parts) < 2) {
            $parts[] = $mins.'m';
        }

        if (empty($parts)) {
            return '< 1m';
        }

        return implode(' ', array_slice($parts, 0, 2));
    }

    /**
     * Maps an avg resolution time (minutes) to a health tier used to colour
     * the Department Health card.
     *
     * @return 'good' | 'amber' | 'poor' | 'nodata'
     */
    public static function healthTier(?int $minutes): string
    {
        if ($minutes === null || $minutes <= 0) {
            return 'nodata';
        }
        if ($minutes <= self::HEALTH_GOOD_MAX_MINUTES) {
            return 'good';
        }
        if ($minutes <= self::HEALTH_AMBER_MAX_MINUTES) {
            return 'amber';
        }

        return 'poor';
    }
}
