<?php

namespace App\Http\Controllers;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Support\PlatformAdminVisibility;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * HQ Tickets — fleet-wide ticket health for EIAAW staff. Aggregates only;
 * never reads ticket subjects, descriptions, chat messages, attachments,
 * or creator/PIC names. See app/Support/PlatformAdminVisibility.php for
 * the privacy contract.
 *
 * Gated by EnsurePlatformAdmin middleware in routes/web.php.
 *
 * Cross-tenant access pattern: uses Ticket::withoutGlobalScope(TenantScope::class)
 * because the request runs at the platform-admin surface with no tenant bound.
 * Postgres RLS will fail-closed if app.tenant_id is unset — this controller
 * must be reached only via the EnsurePlatformAdmin route group (which is
 * platform-admin gated and intentionally outside tenant scope).
 *
 * Note: TenantContext::asNone() is NOT used here because we want each
 * Ticket aggregate keyed by tenant_id — we read across all tenants in
 * one query rather than iterating per-tenant.
 */
class HqTicketsController extends Controller
{
    public function index()
    {
        // ── Fleet-wide ticket counts (aggregates only) ────────────────────
        $byStatus = Ticket::query()
            ->withoutGlobalScope(TenantScope::class)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $totals = [
            'all_time' => (int) array_sum($byStatus),
            'open' => (int) ($byStatus['Open'] ?? 0),
            'in_progress' => (int) ($byStatus['In Progress'] ?? 0),
            'pending' => (int) ($byStatus['Pending'] ?? 0),
            'resolved' => (int) ($byStatus['Resolved'] ?? 0),
            'closed' => (int) ($byStatus['Closed'] ?? 0),
            'active' => (int) (($byStatus['Open'] ?? 0)
                                  + ($byStatus['In Progress'] ?? 0)
                                  + ($byStatus['Pending'] ?? 0)),
            'created_30d' => (int) Ticket::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
            'resolved_30d' => (int) Ticket::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('status', 'Resolved')
                ->whereNotNull('resolved_at')
                ->where('resolved_at', '>=', now()->subDays(30))
                ->count(),
            'idle_7d_plus' => (int) Ticket::query()
                ->withoutGlobalScope(TenantScope::class)
                ->whereIn('status', Ticket::ACTIVE_STATUSES)
                ->where('updated_at', '<', now()->subDays(7))
                ->count(),
        ];

        // ── Fleet-wide by priority (active tickets only) ──────────────────
        $byPriority = Ticket::query()
            ->withoutGlobalScope(TenantScope::class)
            ->whereIn('status', Ticket::ACTIVE_STATUSES)
            ->select('priority', DB::raw('COUNT(*) as total'))
            ->groupBy('priority')
            ->pluck('total', 'priority')
            ->toArray();

        $byPriorityOrdered = [];
        foreach (Ticket::PRIORITIES as $p) {
            $byPriorityOrdered[$p] = (int) ($byPriority[$p] ?? 0);
        }

        // ── Fleet-wide by department (active tickets only) ────────────────
        $byDepartment = Ticket::query()
            ->withoutGlobalScope(TenantScope::class)
            ->whereIn('status', Ticket::ACTIVE_STATUSES)
            ->select('department', DB::raw('COUNT(*) as total'))
            ->groupBy('department')
            ->orderByDesc('total')
            ->pluck('total', 'department')
            ->toArray();

        // ── Per-tenant ticket health table ────────────────────────────────
        // Pulls tenant_id-keyed aggregates in one shot, then joins to the
        // PlatformAdminVisibility-restricted tenant identity columns.
        $perTenantRaw = Ticket::query()
            ->withoutGlobalScope(TenantScope::class)
            ->select(
                'tenant_id',
                DB::raw('COUNT(*) as total_all_time'),
                DB::raw("COUNT(*) FILTER (WHERE status IN ('Open', 'In Progress', 'Pending')) as total_active"),
                DB::raw("COUNT(*) FILTER (WHERE status = 'Pending') as total_pending"),
                DB::raw("COUNT(*) FILTER (WHERE status IN ('Open', 'In Progress', 'Pending') AND updated_at < NOW() - INTERVAL '7 days') as total_idle_7d"),
                DB::raw("COUNT(*) FILTER (WHERE created_at >= NOW() - INTERVAL '30 days') as created_30d"),
                DB::raw("COUNT(*) FILTER (WHERE status = 'Resolved' AND resolved_at >= NOW() - INTERVAL '30 days') as resolved_30d"),
                DB::raw("AVG(EXTRACT(EPOCH FROM (resolved_at - COALESCE(assigned_at, created_at))) / 60) FILTER (WHERE status = 'Resolved' AND resolved_at >= NOW() - INTERVAL '30 days') as avg_resolution_minutes_30d"),
            )
            ->groupBy('tenant_id')
            ->get()
            ->keyBy('tenant_id');

        // Join to tenant identity (allowlisted fields only). Show every
        // active/suspended tenant even if zero tickets, so HQ can spot
        // workspaces that haven't adopted ticketing.
        $tenants = Tenant::query()
            ->select(PlatformAdminVisibility::TENANT_FIELDS)
            ->whereIn('status', [Tenant::STATUS_ACTIVE, Tenant::STATUS_SUSPENDED])
            ->orderBy('name')
            ->get();

        $perTenant = $tenants->map(function (Tenant $tenant) use ($perTenantRaw) {
            $row = $perTenantRaw->get($tenant->id);
            $avgMinutes = $row && $row->avg_resolution_minutes_30d !== null
                ? (int) round((float) $row->avg_resolution_minutes_30d)
                : null;

            return [
                'tenant_id' => $tenant->id,
                'tenant_slug' => $tenant->slug,
                'tenant_name' => $tenant->name,
                'tenant_plan' => $tenant->plan,
                'tenant_status' => $tenant->status,
                'total_all_time' => (int) ($row->total_all_time ?? 0),
                'total_active' => (int) ($row->total_active ?? 0),
                'total_pending' => (int) ($row->total_pending ?? 0),
                'total_idle_7d' => (int) ($row->total_idle_7d ?? 0),
                'created_30d' => (int) ($row->created_30d ?? 0),
                'resolved_30d' => (int) ($row->resolved_30d ?? 0),
                'avg_resolution_30d' => $avgMinutes,
                'avg_resolution_formatted' => $avgMinutes !== null
                    ? Ticket::formatMinutes($avgMinutes)
                    : null,
                'avg_resolution_tier' => Ticket::healthTier($avgMinutes),
            ];
        });

        return view('superadmin.hq.tickets', [
            'totals' => $totals,
            'byPriorityOrdered' => $byPriorityOrdered,
            'byDepartment' => $byDepartment,
            'perTenant' => $perTenant,
        ]);
    }
}
