<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Cashier\Billable;

/**
 * Tenant — the customer organisation paying for an EIAAW Workforce subscription.
 *
 * Resolved per-request from the subdomain by the ResolveTenant middleware,
 * cached in app('current_tenant'). All tenant-scoped models use the
 * BelongsToTenant trait + TenantScope global scope.
 *
 * Implements Cashier's Billable interface so subscriptions/invoices/payment-method
 * are owned at the tenant level, not the user level. Cashier's owner-key column
 * is set to `tenant_id` via App\Providers\CashierServiceProvider.
 */
class Tenant extends Model
{
    use SoftDeletes, Billable;

    protected $fillable = [
        'slug', 'name', 'legal_name', 'country_code', 'billing_currency',
        'plan', 'plan_seats', 'trial_ends_at',
        'stripe_customer_id', 'stripe_subscription_id', 'subscription_status',
        'past_due_at',
        'stripe_id', 'pm_type', 'pm_last_four',
        'status', 'suspended_at', 'suspension_reason', 'canceled_at',
        'uses_dedicated_db', 'dedicated_db_dsn',
        'sso_enabled', 'sso_config',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'suspended_at' => 'datetime',
        'canceled_at' => 'datetime',
        'past_due_at' => 'datetime',
        'uses_dedicated_db' => 'boolean',
        'sso_enabled' => 'boolean',
        'sso_config' => 'array',
        'plan_seats' => 'integer',
    ];

    public const PLAN_STARTER = 'starter';
    public const PLAN_GROWTH  = 'growth';
    public const PLAN_SCALE   = 'scale';
    public const PLAN_ENTERPRISE = 'enterprise';

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_CANCELED  = 'canceled';

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_users')
            ->withPivot('tenant_role', 'joined_at')
            ->withTimestamps();
    }

    public function aiConversations(): HasMany
    {
        return $this->hasMany(AiConversation::class);
    }

    public function aiUsageDaily(): HasMany
    {
        return $this->hasMany(AiUsageDaily::class);
    }

    /**
     * Check feature access for this tenant's plan.
     * Reads from config/plans.php — see that file for the canonical feature map.
     */
    public function hasFeature(string $feature): bool
    {
        $features = config("plans.{$this->plan}.features", []);
        return in_array($feature, $features, true);
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function aiBudgetUsd(): float
    {
        return (float) config("plans.{$this->plan}.ai_budget_usd",
            (float) env('AI_BUDGET_STARTER_USD', 5));
    }

    /**
     * Per-seat monthly price in USD for this tenant's plan.
     * Returns null for Enterprise (custom-priced) — billing is handled out-of-band.
     */
    public function planPriceUsdMonthly(): ?float
    {
        $price = config("plans.{$this->plan}.price_usd_monthly");
        return $price === null ? null : (float) $price;
    }

    /**
     * Fully-qualified workspace URL for this tenant. Used by the post-signup
     * redirect, email links, and HQ tenant directory. Honours the configured
     * tenant_domain so dev / staging / production all produce the right URL.
     */
    public function workspaceUrl(string $path = '/'): string
    {
        $domain = config('eiaaw.tenant_domain');
        $path   = '/' . ltrim($path, '/');
        return "https://{$this->slug}.{$domain}{$path}";
    }

    /**
     * Reserved subdomain slugs — single source of truth in config/eiaaw.php.
     * Returns lowercase strings. Used by isSlugAvailable() and the signup
     * form validator.
     */
    public static function reservedSlugs(): array
    {
        return array_map('strtolower', (array) config('eiaaw.reserved_slugs', []));
    }

    /**
     * Whether `$slug` can be used as a new tenant subdomain. Checks:
     *   - Format (lowercase alphanumeric + hyphens, 3–60 chars, no leading/trailing hyphen)
     *   - Not in the reserved list (config/eiaaw.php → reserved_slugs)
     *   - No collision with an existing tenant (including soft-deleted)
     *   - No collision with a pending SignupInvite
     *
     * Database uniqueness is also enforced by UNIQUE constraints on
     * tenants.slug and signup_invites.desired_slug, so concurrent signups
     * for the same slug fail loudly at INSERT time. This method exists
     * for friendly form-validation errors before that point.
     */
    public static function isSlugAvailable(string $slug, ?int $ignoreInviteId = null): bool
    {
        $slug = strtolower($slug);

        if (!preg_match('/^[a-z0-9](?:[a-z0-9-]{1,58}[a-z0-9])?$/', $slug)) {
            return false;
        }

        if (in_array($slug, self::reservedSlugs(), true)) {
            return false;
        }

        if (self::withTrashed()->where('slug', $slug)->exists()) {
            return false;
        }

        $inviteQuery = \App\Models\SignupInvite::where('desired_slug', $slug);
        if ($ignoreInviteId !== null) {
            $inviteQuery->where('id', '!=', $ignoreInviteId);
        }
        if ($inviteQuery->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Current count of users in this tenant's tenant_users pivot. Cheap query
     * (count on a small pivot table); not cached — callers may cache if needed.
     */
    public function seatsUsed(): int
    {
        return $this->users()->count();
    }

    /**
     * Whether one more user can be added without exceeding plan_seats.
     * Enterprise tenants with plan_seats >= 9999 are treated as effectively
     * unlimited (still bounded — never trust "infinity").
     */
    public function canAddUser(int $additional = 1): bool
    {
        $limit = (int) ($this->plan_seats ?? 0);
        if ($limit <= 0) return false;
        return ($this->seatsUsed() + $additional) <= $limit;
    }


    /**
     * Cashier billing identity. Used when creating the Stripe customer.
     * Falls back to the first owner user's work_email if no billing_email
     * is set on the tenant directly.
     */
    public function stripeName(): ?string
    {
        return $this->legal_name ?: $this->name;
    }

    public function stripeEmail(): ?string
    {
        // Prefer an explicit billing_email column if/when added; for now use
        // the first owner user's work_email.
        $owner = $this->users()
            ->wherePivot('tenant_role', 'owner')
            ->first();
        return $owner?->work_email;
    }

    /**
     * Cashier uses this for checkout currency selection. Session 11 dropped
     * MYR — every workspace bills in USD. The `billing_currency` column is
     * retained but defaulted to USD; future multi-currency support can
     * resurface it.
     */
    public function preferredCurrency(): string
    {
        return strtolower($this->billing_currency ?: 'usd');
    }
}
