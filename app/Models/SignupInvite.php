<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * SignupInvite — pending tenant signup.
 *
 * Lives at the marketing apex; intentionally NOT tenant-scoped (no
 * BelongsToTenant trait). Created by SignupController@start and sent to
 * Stripe Checkout; marked paid by SignupCheckout::recordPayment; consumed by
 * SignupController@confirm which provisions the Tenant + owner User.
 */
class SignupInvite extends Model
{
    protected $fillable = [
        'work_email', 'full_name', 'company_name', 'desired_slug',
        'confirmation_token', 'plan', 'confirmed_at', 'expires_at',
        'signup_ip', 'signup_user_agent', 'consent_at', 'consent_version',
        'seats', 'billing_period', 'stripe_checkout_session_id',
        'stripe_customer_id', 'stripe_subscription_id', 'paid_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'expires_at'   => 'datetime',
        'consent_at'   => 'datetime',
        'paid_at'      => 'datetime',
        'seats'        => 'integer',
    ];

    public function isPaid(): bool
    {
        return !is_null($this->paid_at);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConfirmed(): bool
    {
        return !is_null($this->confirmed_at);
    }
}
