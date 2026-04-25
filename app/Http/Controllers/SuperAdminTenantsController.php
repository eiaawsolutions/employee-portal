<?php

namespace App\Http\Controllers;

use App\Models\SecurityAuditLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Platform-level Tenants management. EIAAW staff only — gated by
 * EnsurePlatformAdmin middleware. Operates outside RLS (queries all
 * tenants directly via the Tenant model with explicit no-scope).
 */
class SuperAdminTenantsController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->input('q');
        $status = $request->input('status');
        $plan = $request->input('plan');

        $tenants = Tenant::query()
            ->withTrashed()
            ->withCount('users')
            ->when($q, function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('name', 'ilike', "%{$q}%")
                      ->orWhere('slug', 'ilike', "%{$q}%")
                      ->orWhere('legal_name', 'ilike', "%{$q}%");
                });
            })
            ->when($status, fn ($b) => $b->where('status', $status))
            ->when($plan, fn ($b) => $b->where('plan', $plan))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $stats = [
            'total'      => Tenant::withTrashed()->count(),
            'active'     => Tenant::where('status', Tenant::STATUS_ACTIVE)->count(),
            'suspended'  => Tenant::where('status', Tenant::STATUS_SUSPENDED)->count(),
            'canceled'   => Tenant::where('status', Tenant::STATUS_CANCELED)->count(),
            'in_trial'   => Tenant::whereNotNull('trial_ends_at')->where('trial_ends_at', '>', now())->count(),
            'past_due'   => Tenant::whereNotNull('past_due_at')->count(),
        ];

        return view('superadmin.tenants.index', compact('tenants', 'stats', 'q', 'status', 'plan'));
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['users']);

        // AI usage — last 30 days. Compute totals server-side so the view
        // can't miss-name a column (the previous view summed tokens_total /
        // cost_usd_total which don't exist; result was always 0).
        $usage = DB::table('ai_usage_daily')
            ->where('tenant_id', $tenant->id)
            ->where('usage_date', '>=', now()->subDays(30)->toDateString())
            ->orderBy('usage_date')
            ->get();

        $aiTotals = [
            'tokens'   => (int) $usage->sum(fn ($r) => (int) $r->input_tokens
                                                   + (int) $r->output_tokens
                                                   + (int) $r->cache_read_tokens
                                                   + (int) $r->cache_write_tokens),
            'cost_usd' => (float) $usage->sum('cost_usd'),
            'requests' => (int) $usage->sum('request_count'),
            'days'     => $usage->count(),
        ];

        // Latest meter snapshot for the cost-stack panel. Null on tenants
        // the meter has never touched (fresh signup, or meter not yet run).
        $snapshot = \App\Models\TenantUsageDaily::query()
            ->where('tenant_id', $tenant->id)
            ->latest('usage_date')
            ->first();

        // Estimated MRR for this tenant. Enterprise returns null (custom).
        $mrr = $tenant->planPriceUsdMonthly() !== null
            ? $tenant->planPriceUsdMonthly() * (int) $tenant->plan_seats
            : null;

        return view('superadmin.tenants.show', compact('tenant', 'usage', 'aiTotals', 'snapshot', 'mrr'));
    }

    public function suspend(Request $request, Tenant $tenant)
    {
        $reason = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ])['reason'];

        $tenant->status = Tenant::STATUS_SUSPENDED;
        $tenant->suspended_at = now();
        $tenant->suspension_reason = $reason;
        $tenant->save();

        $this->audit($request, 'tenant_suspended', [
            'tenant_id' => $tenant->id,
            'tenant_slug' => $tenant->slug,
            'reason' => $reason,
        ]);

        return back()->with('status', "Suspended {$tenant->name}.");
    }

    public function reactivate(Request $request, Tenant $tenant)
    {
        $tenant->status = Tenant::STATUS_ACTIVE;
        $tenant->suspended_at = null;
        $tenant->suspension_reason = null;
        $tenant->save();

        $this->audit($request, 'tenant_reactivated', [
            'tenant_id' => $tenant->id,
            'tenant_slug' => $tenant->slug,
        ]);

        return back()->with('status', "Reactivated {$tenant->name}.");
    }

    private function audit(Request $request, string $event, array $details): void
    {
        $user = $request->user();
        SecurityAuditLog::record($event, [
            'user_id' => $user->id,
            'work_email' => $user->work_email ?? null,
            'role' => $user->role ?? null,
            'url' => $request->path(),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
            'details' => json_encode($details),
        ]);
    }
}
