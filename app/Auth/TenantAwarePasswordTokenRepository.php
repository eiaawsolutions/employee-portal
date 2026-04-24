<?php

namespace App\Auth;

use Illuminate\Auth\Passwords\DatabaseTokenRepository;

/**
 * Tenant-aware password token repository.
 *
 * Adds tenant_id to the INSERT payload. SELECT/DELETE queries are
 * automatically scoped because Postgres RLS on password_reset_tokens
 * filters rows by current_setting('app.tenant_id'). The ResolveTenant
 * middleware sets that variable per request.
 *
 * On a fresh deploy where SaaS Postgres is the only DB, the
 * `eiaaw_current_tenant_id()` helper returns 0 if no tenant is bound,
 * which the RLS policy treats as zero-match — so password resets ONLY
 * work on tenant subdomains, not the marketing apex. That's the
 * correct behavior: forgot-password forms live on tenant subdomains.
 */
class TenantAwarePasswordTokenRepository extends DatabaseTokenRepository
{
    protected function getPayload($email, #[\SensitiveParameter] $token)
    {
        $payload = parent::getPayload($email, $token);

        if (app()->bound('current_tenant')) {
            $payload['tenant_id'] = app('current_tenant')->id;
        }

        return $payload;
    }
}
