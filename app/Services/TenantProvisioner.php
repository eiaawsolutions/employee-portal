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
 *   - Tenant exists on an active, already-paid MYR subscription (no trial) —
 *     the Stripe customer + subscription come from the paid Checkout Session
 *     recorded on the invite (SignupCheckout::recordPayment)
 *   - First owner User exists in the tenant_users pivot with role=owner
 *   - Welcome state is set so the dashboard onboarding wizard fires
 */
class TenantProvisioner
{
    /**
     * Create a Tenant + first owner User from a confirmed signup invite.
     * Returns the new Tenant.
     */
    public function provisionFromInvite(SignupInvite $invite, string $password): Tenant
    {
        // Race-safe re-check: catches the narrow window between the
        // signup form's availability check and this commit. The DB has
        // a UNIQUE constraint as the ultimate guard, but raising a
        // friendly exception here gives the controller a clean error
        // path instead of a 500 from a constraint-violation.
        if (!Tenant::isSlugAvailable($invite->desired_slug, $invite->id)) {
            throw new \App\Exceptions\SlugUnavailableException($invite->desired_slug);
        }

        return DB::transaction(function () use ($invite, $password) {
            // 1. Create the Tenant (no current_tenant context — this is platform-level).
            $tenant = TenantContext::asNone(function () use ($invite) {
                return Tenant::create([
                    'slug'               => $invite->desired_slug,
                    'name'               => $invite->company_name,
                    'plan'               => $invite->plan,
                    'plan_seats'         => max(5, (int) $invite->seats),
                    'trial_ends_at'      => null,
                    'status'             => Tenant::STATUS_ACTIVE,
                    'country_code'       => 'MY',
                    'billing_currency'   => 'MYR',
                    'stripe_id'              => $invite->stripe_customer_id,
                    'stripe_customer_id'     => $invite->stripe_customer_id,
                    'stripe_subscription_id' => $invite->stripe_subscription_id,
                    'subscription_status'    => $invite->isPaid() ? 'active' : null,
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
