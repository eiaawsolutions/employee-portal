<?php

namespace App\Http\Controllers;

use App\Mail\DedicatedDatabaseRequestMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

/**
 * DedicatedDatabaseController — Enterprise workspace-admin request flow for
 * moving off the shared Postgres pool onto a dedicated database.
 *
 * The actual provisioning is a manual ops task (spin up a new Postgres
 * instance, run migrations, copy tenant data, flip the DSN). This
 * controller only RECORDS INTENT: it sets `tenants.uses_dedicated_db`
 * to true and emails ops with the request context. Runtime DSN switching
 * (reading `dedicated_db_dsn` and reconnecting per-tenant) is deferred.
 *
 * Plan-gated to `infra.dedicated_db` (Enterprise only) via route middleware.
 * Authorisation: only workspace owners can submit the request.
 */
class DedicatedDatabaseController extends Controller
{
    public function show()
    {
        $tenant = $this->currentTenant();

        return view('superadmin.dedicated-database', [
            'tenant' => $tenant,
            'alreadyRequested' => (bool) $tenant->uses_dedicated_db,
        ]);
    }

    public function request(Request $request)
    {
        $tenant = $this->currentTenant();
        $user = $request->user();

        // Authorisation — only workspace owners can request infra changes.
        if (!$this->isOwner($user, $tenant)) {
            abort(403, 'Only workspace owners can request a dedicated database.');
        }

        $data = Validator::make($request->all(), [
            'region_preference' => ['required', 'in:ap-southeast-1,ap-southeast-3,eu-west-1,us-east-1'],
            'compliance_note'   => ['nullable', 'string', 'max:2000'],
            'target_go_live'    => ['nullable', 'date', 'after:today'],
            'acknowledged'      => ['accepted'],
        ])->validate();

        if ($tenant->uses_dedicated_db) {
            return back()->with('info', 'A dedicated database request is already on file for this workspace.');
        }

        // Record intent. The actual DSN stays null until ops provisions and
        // writes the value via `php artisan tenant:set-dedicated-dsn`.
        $tenant->update([
            'uses_dedicated_db' => true,
        ]);

        Log::channel('single')->info('dedicated_database.request_submitted', [
            'tenant_id' => $tenant->id,
            'slug' => $tenant->slug,
            'requested_by' => $user->id,
            'region' => $data['region_preference'],
            'target_go_live' => $data['target_go_live'] ?? null,
        ]);

        // Notify ops so the manual provisioning work can start. Mailable is
        // best-effort — do not fail the request if mail is misconfigured.
        try {
            Mail::to(config('eiaaw.sales_email'))->send(
                new DedicatedDatabaseRequestMail($tenant, $user, $data)
            );
        } catch (\Throwable $e) {
            Log::warning('DedicatedDatabaseRequestMail failed: ' . $e->getMessage());
        }

        return back()->with(
            'success',
            "Your dedicated database request has been submitted. Our team will reach out within 2 business days to scope provisioning."
        );
    }

    private function currentTenant()
    {
        if (!app()->bound('current_tenant')) {
            abort(403, 'This page requires an active tenant context.');
        }
        return app('current_tenant');
    }

    private function isOwner($user, $tenant): bool
    {
        if (!$user) return false;
        // Owners are either (a) users with owner role at tenant level or
        // (b) superadmin in the workspace.
        if (in_array($user->role, ['superadmin', 'system_admin'], true)) {
            return true;
        }
        return $tenant->users()
            ->where('users.id', $user->id)
            ->wherePivot('tenant_role', 'owner')
            ->exists();
    }
}
