<?php

namespace App\Services;

use App\Models\SignupInvite;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * TenantProvisioner — turns a confirmed SignupInvite into a live tenant.
 *
 * Single transaction across two surfaces (the "global" surface that creates
 * the Tenant, and the "tenant-scoped" surface that creates the first owner
 * User). After commit:
 *   - Tenant exists with a 14-day trial set
 *   - First owner User exists in the tenant_users pivot with role=owner
 *   - Welcome state is set so the dashboard onboarding wizard fires
 *
 * Stripe customer is NOT created here — Cashier creates it lazily on first
 * payment-method attach or subscription create. Webhook handles seat
 * reconciliation in Wk3.
 */
class TenantProvisioner
{
    /**
     * Create a Tenant + first owner User from a confirmed signup invite.
     * Returns the new Tenant.
     */
    public function provisionFromInvite(SignupInvite $invite, string $password): Tenant
    {
        return DB::transaction(function () use ($invite, $password) {
            // 1. Create the Tenant (no current_tenant context — this is platform-level).
            $tenant = TenantContext::asNone(function () use ($invite) {
                return Tenant::create([
                    'slug'               => $invite->desired_slug,
                    'name'               => $invite->company_name,
                    'plan'               => $invite->plan,
                    'plan_seats'         => 5,
                    'trial_ends_at'      => now()->addDays((int) env('STRIPE_TRIAL_DAYS', 14)),
                    'status'             => Tenant::STATUS_ACTIVE,
                    'country_code'       => 'MY',
                    'billing_currency'   => 'USD',
                ]);
            });

            // 2. Create the owner user inside the new tenant context so the
            //    BelongsToTenant trait auto-fills user.tenant_id.
            TenantContext::run($tenant, function () use ($tenant, $invite, $password) {
                $user = User::create([
                    'name'           => $invite->full_name,
                    'work_email'     => $invite->work_email,
                    'password'       => Hash::make($password),
                    'role'           => 'superadmin', // tenant owner = superadmin within their workspace
                    'is_active'      => true,
                    'login_attempts' => 0,
                ]);

                // 3. Pivot row marking ownership.
                $tenant->users()->attach($user->id, [
                    'tenant_role' => 'owner',
                    'joined_at'   => now(),
                ]);
            });

            // 4. Mark invite consumed.
            $invite->update(['confirmed_at' => now()]);

            return $tenant->refresh();
        });
    }
}
