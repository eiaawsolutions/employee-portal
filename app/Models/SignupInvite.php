<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * SignupInvite — pending tenant signup.
 *
 * Lives at the marketing apex; intentionally NOT tenant-scoped (no
 * BelongsToTenant trait). Created by SignupController@start, consumed by
 * SignupController@confirm which provisions the Tenant + owner User.
 */
class SignupInvite extends Model
{
    protected $fillable = [
        'work_email', 'full_name', 'company_name', 'desired_slug',
        'confirmation_token', 'plan', 'confirmed_at', 'expires_at',
        'signup_ip', 'signup_user_agent',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConfirmed(): bool
    {
        return !is_null($this->confirmed_at);
    }
}
