<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Infisical backend (resolved by App\Providers\SecretsServiceProvider)
    |--------------------------------------------------------------------------
    | The ONLY raw secrets that live in env are the Infisical bootstrap
    | credentials. Everything else is a `secret://project/env/name` handle,
    | resolved at boot before other providers run.
    |
    | Production project: eiaaw-workforce-prod
    | Machine identity: eiaaw-workforce-app
    */
    'infisical' => [
        'enabled' => env('INFISICAL_RESOLVER_ENABLED', false),
        'site_url' => env('INFISICAL_SITE_URL', 'https://app.infisical.com'),
        'client_id' => env('INFISICAL_APP_CLIENT_ID'),
        'client_secret' => env('INFISICAL_APP_CLIENT_SECRET'),
        'project_id' => env('INFISICAL_PROJECT_ID'),
        'environment' => env('INFISICAL_ENVIRONMENT', 'prod'),
        'cache_ttl' => (int) env('INFISICAL_CACHE_TTL', 300),
        'request_timeout' => (int) env('INFISICAL_REQUEST_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Config paths to resolve
    |--------------------------------------------------------------------------
    | Explicit allow-list of dot-delimited config keys that may hold
    | `secret://` handles. The provider only touches these; unlisted keys
    | are left alone (opt-in safety rail).
    |
    | EIAAW Workforce surface area:
    |   - Stripe (Cashier — billing)
    |   - Anthropic + OpenAI (AiGateway — AI assistant)
    |   - Mail (Mailgun transactional)
    |   - DB + Redis passwords (bootstrap-level; usually Railway-provided)
    |   - Cloudflare R2 (private file storage)
    |   - LOG_INTEGRITY_KEY + BACKUP_ENCRYPTION_KEY (SaaS ops)
    |   - SSO per-tenant config is stored ENCRYPTED in DB, not here
    */
    'resolve' => [
        // Stripe billing
        'cashier.secret',
        'cashier.webhook.secret',
        'services.stripe.secret',
        'services.stripe.webhook_secret',

        // AI providers
        'services.anthropic.api_key',
        'services.openai.api_key',

        // Mail
        'mail.mailers.smtp.password',
        'services.mailgun.secret',

        // Database + cache (Railway usually injects these directly, but
        // resolving allows a single Infisical-of-truth pattern for audits)
        'database.connections.pgsql.password',
        'database.redis.default.password',
        'database.redis.cache.password',

        // Object storage
        'filesystems.disks.r2.key',
        'filesystems.disks.r2.secret',
        'filesystems.disks.s3.key',
        'filesystems.disks.s3.secret',

        // SaaS-ops keys
        'app.log_integrity_key',
        'app.backup_encryption_key',

        // Sentry (Session 12 observability)
        'sentry.dsn',

        // Stripe Price IDs (populated via stripe:sync-prices, stored in Infisical
        // so all EIAAW environments resolve from the same source of truth)
        'eiaaw.pricing.tiers.starter.stripe_prices.monthly',
        'eiaaw.pricing.tiers.starter.stripe_prices.annual',
        'eiaaw.pricing.tiers.growth.stripe_prices.monthly',
        'eiaaw.pricing.tiers.growth.stripe_prices.annual',
        'eiaaw.pricing.tiers.scale.stripe_prices.monthly',
        'eiaaw.pricing.tiers.scale.stripe_prices.annual',
    ],
];
