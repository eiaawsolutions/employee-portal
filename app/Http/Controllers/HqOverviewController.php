<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantUsageDaily;
use App\Support\PlatformAdminVisibility;
use Illuminate\Support\Facades\DB;

/**
 * HQ Overview — fleet-wide dashboard for EIAAW staff. Aggregates only;
 * never reads tenant business data. See
 * app/Support/PlatformAdminVisibility.php for the privacy contract.
 *
 * Gated by EnsurePlatformAdmin middleware in routes/web.php.
 */
class HqOverviewController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        // ── Fleet counts (cheap aggregates on the tenants table) ──────────
        $byStatus = Tenant::query()
            ->withTrashed()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $byPlan = Tenant::query()
            ->where('status', '!=', Tenant::STATUS_CANCELED)
            ->select('plan', DB::raw('COUNT(*) as total'))
            ->groupBy('plan')
            ->pluck('total', 'plan')
            ->toArray();

        $totals = [
            'all_time'       => Tenant::withTrashed()->count(),
            'active'         => $byStatus[Tenant::STATUS_ACTIVE] ?? 0,
            'suspended'      => $byStatus[Tenant::STATUS_SUSPENDED] ?? 0,
            'canceled'       => $byStatus[Tenant::STATUS_CANCELED] ?? 0,
            'in_trial'       => Tenant::whereNotNull('trial_ends_at')
                                    ->where('trial_ends_at', '>', now())
                                    ->count(),
            'past_due'       => Tenant::whereNotNull('past_due_at')->count(),
            'trial_ends_7d'  => Tenant::whereNotNull('trial_ends_at')
                                    ->whereBetween('trial_ends_at', [now(), now()->addDays(7)])
                                    ->count(),
            'signups_30d'    => Tenant::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        // ── MRR estimate (best-effort: plan price × paying tenants) ───────
        // Excludes trialing, suspended, canceled. Enterprise (custom price)
        // returns null; surfaced as a separate "X enterprise tenants on
        // custom pricing" line so HQ knows the MRR floor + the unknown.
        $payingTenants = Tenant::query()
            ->where('status', Tenant::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('trial_ends_at')->orWhere('trial_ends_at', '<', now());
            })
            ->get(['id', 'plan', 'plan_seats']);

        $mrr = ['total_known' => 0.0, 'enterprise_unknown' => 0];
        foreach ($payingTenants as $t) {
            $price = $t->planPriceUsdMonthly();
            if ($price === null) {
                $mrr['enterprise_unknown']++;
                continue;
            }
            $mrr['total_known'] += $price * (int) $t->plan_seats;
        }

        // ── Latest snapshot per tenant from the daily meter ──────────────
        $latestSnapshots = TenantUsageDaily::query()
            ->whereIn('id', function ($q) {
                $q->select(DB::raw('MAX(id)'))
                    ->from('tenant_usage_daily')
                    ->groupBy('tenant_id');
            })
            ->with(['tenant' => function ($q) {
                $q->select(PlatformAdminVisibility::TENANT_FIELDS);
            }])
            ->get();

        // ── Top 10 by AI spend (last snapshot) ───────────────────────────
        $topByAiSpend = $latestSnapshots
            ->sortByDesc('ai_cost_usd_30d')
            ->take(10)
            ->values();

        // ── Top 10 by seat utilisation (closest to limit) ────────────────
        $topByUtilisation = $latestSnapshots
            ->filter(fn ($s) => $s->tenant && (int) $s->tenant->plan_seats > 0)
            ->map(function ($s) {
                $s->utilisation = $s->users_count / max(1, (int) $s->tenant->plan_seats);
                return $s;
            })
            ->sortByDesc('utilisation')
            ->take(10)
            ->values();

        // ── Fleet 30-day AI spend (sum of latest snapshots) ──────────────
        $fleetAiCost30d = (float) $latestSnapshots->sum('ai_cost_usd_30d');
        $fleetAiTokens30d = (int) $latestSnapshots->sum('ai_tokens_30d');
        $fleetStorageMb = (int) $latestSnapshots->sum('storage_mb');

        // ── Tenants the meter has never touched (fresh signups since last
        //    meter run, or meter failures) ─────────────────────────────────
        $unmeteredTenants = Tenant::query()
            ->whereDoesntHave('users') // skip totally empty
            ->orWhereNotIn('id', $latestSnapshots->pluck('tenant_id'))
            ->where('status', '!=', Tenant::STATUS_CANCELED)
            ->count();

        return view('superadmin.hq.index', compact(
            'totals', 'byPlan', 'mrr',
            'topByAiSpend', 'topByUtilisation',
            'fleetAiCost30d', 'fleetAiTokens30d', 'fleetStorageMb',
            'unmeteredTenants', 'today',
        ));
    }
}
