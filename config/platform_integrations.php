<?php

/*
 * Catalog of platform-level integrations editable by SuperAdmin via the
 * Integrations settings page. Each entry is rendered as a form field; the
 * value is encrypted at rest in `platform_settings.value` and the form
 * never echoes the raw value back to the page (mask only).
 *
 * Keep this list narrow — only EIAAW operational secrets, not tenant data.
 */

return [
    [
        'section' => 'Email — Resend',
        'description' => 'Transactional email (signup confirmations, password resets, invites). Get your key from https://resend.com/api-keys',
        'fields' => [
            [
                'key' => 'resend_api_key',
                'label' => 'Resend API key',
                'placeholder' => 're_…',
                'help' => 'Required for sending email. When set, MAIL_MAILER auto-switches to "resend".',
                'is_secret' => true,
            ],
            [
                'key' => 'mail_from_address',
                'label' => 'From address',
                'placeholder' => 'hello@eiaawsolutions.com',
                'help' => 'Must be a verified sender on your Resend domain.',
                'is_secret' => false,
            ],
            [
                'key' => 'mail_from_name',
                'label' => 'From name',
                'placeholder' => 'EIAAW Workforce',
                'is_secret' => false,
            ],
        ],
    ],
    [
        'section' => 'Payments — Stripe',
        'description' => 'Billing for tenants. Set both live and test keys; the active mode follows STRIPE_KEY in env.',
        'fields' => [
            ['key' => 'stripe_secret_key',     'label' => 'Stripe secret key',     'placeholder' => 'sk_live_… or sk_test_…', 'is_secret' => true],
            ['key' => 'stripe_publishable_key', 'label' => 'Stripe publishable key', 'placeholder' => 'pk_live_… or pk_test_…', 'is_secret' => true],
            ['key' => 'stripe_webhook_secret', 'label' => 'Stripe webhook secret', 'placeholder' => 'whsec_…', 'help' => 'From the webhook endpoint at https://ep.eiaawsolutions.com/stripe/webhook', 'is_secret' => true],
            ['key' => 'stripe_price_starter',  'label' => 'Starter price ID',  'placeholder' => 'price_…', 'is_secret' => false],
            ['key' => 'stripe_price_growth',   'label' => 'Growth price ID',   'placeholder' => 'price_…', 'is_secret' => false],
            ['key' => 'stripe_price_scale',    'label' => 'Scale price ID',    'placeholder' => 'price_…', 'is_secret' => false],
        ],
    ],
    [
        'section' => 'AI — Anthropic',
        'description' => 'Claude models for the AI assistant, AI invoice scan, content summarization.',
        'fields' => [
            ['key' => 'anthropic_api_key', 'label' => 'Anthropic API key', 'placeholder' => 'sk-ant-…', 'is_secret' => true],
        ],
    ],
    [
        'section' => 'AI — OpenAI',
        'description' => 'Fallback / cost-routing for routine prompts.',
        'fields' => [
            ['key' => 'openai_api_key', 'label' => 'OpenAI API key', 'placeholder' => 'sk-…', 'is_secret' => true],
        ],
    ],
    [
        'section' => 'Storage — Cloudflare R2',
        'description' => 'Optional. When set, file uploads route to R2 instead of local disk.',
        'fields' => [
            ['key' => 'r2_access_key_id',     'label' => 'R2 access key ID',     'is_secret' => true],
            ['key' => 'r2_secret_access_key', 'label' => 'R2 secret access key', 'is_secret' => true],
            ['key' => 'r2_bucket',            'label' => 'R2 bucket name',       'is_secret' => false],
            ['key' => 'r2_endpoint',          'label' => 'R2 S3 endpoint',       'placeholder' => 'https://<acct>.r2.cloudflarestorage.com', 'is_secret' => false],
        ],
    ],
    [
        'section' => 'Observability — Sentry',
        'fields' => [
            ['key' => 'sentry_dsn', 'label' => 'Sentry DSN', 'placeholder' => 'https://…@…ingest.sentry.io/…', 'is_secret' => true],
        ],
    ],
];
