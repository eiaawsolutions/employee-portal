<?php

namespace App\Http\Controllers;

use App\Mail\FindWorkspaceMail;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

/**
 * FindWorkspaceController — "Can't remember your workspace URL?" form.
 *
 * Neutral-response pattern (prevents account enumeration): the lookup
 * always renders the same "If we found a workspace for that email,
 * we've emailed you the list" confirmation, regardless of whether
 * matching tenants exist. When there is a match, the list goes only to
 * the address that asked (FindWorkspaceMail), sent synchronously so it
 * doesn't depend on a queue worker.
 *
 * The DB query crosses tenants by design, so TenantScope is bypassed.
 * Rate-limited 5/min via route definition.
 */
class FindWorkspaceController extends Controller
{
    public function show()
    {
        return view('marketing.find-workspace');
    }

    public function lookup(Request $request)
    {
        $data = Validator::make($request->all(), [
            'work_email' => ['required', 'email:rfc', 'max:255'],
        ])->validate();

        $email = strtolower(trim($data['work_email']));

        // Cross-tenant lookup. Global TenantScope would filter to the
        // (non-existent) current tenant since we're on the marketing apex.
        $tenantIds = User::withoutGlobalScope(TenantScope::class)
            ->where('work_email', $email)
            ->where('is_active', true)
            ->pluck('tenant_id')
            ->unique()
            ->values();

        $tenants = Tenant::whereIn('id', $tenantIds)
            ->where(function ($q) {
                $q->whereNull('suspended_at')->orWhere('suspended_at', '>', now());
            })
            ->get(['id', 'slug', 'name']);

        if ($tenants->isNotEmpty()) {
            try {
                Mail::to($email)->send(new FindWorkspaceMail($tenants));
            } catch (\Throwable $e) {
                Log::error('find-workspace.mail_failed', ['error' => $e->getMessage()]);
                report($e);
            }
        }

        Log::info('find-workspace lookup', [
            'tenant_count' => $tenants->count(),
            'ip' => $request->ip(),
        ]);

        // Neutral confirmation — do NOT leak whether the email matched.
        return view('marketing.find-workspace', [
            'submitted' => true,
            'submittedEmail' => $email,
        ]);
    }
}
