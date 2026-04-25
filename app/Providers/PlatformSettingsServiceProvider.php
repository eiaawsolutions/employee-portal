<?php

namespace App\Providers;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

/**
 * PlatformSettingsServiceProvider
 *
 * On boot, reads values from the platform_settings table and overrides the
 * relevant config keys so downstream services (Mail, Cashier, AiGateway,
 * Sentry, R2 disk) consume operator-managed values instead of env-only.
 *
 * Fail-open: if the table doesn't exist yet (fresh install pre-migrate),
 * we silently skip — no boot-time crash.
 */
class PlatformSettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        try {
            if (! Schema::hasTable('platform_settings')) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        try {
            $this->applyMail();
            $this->applyStripe();
            $this->applyAi();
            $this->applyR2();
            $this->applySentry();
        } catch (\Throwable $e) {
            Log::warning('PlatformSettingsServiceProvider: apply failed', ['error' => $e->getMessage()]);
        }
    }

    private function applyMail(): void
    {
        $resendKey = PlatformSetting::get('resend_api_key');
        if ($resendKey) {
            config([
                'services.resend.key' => $resendKey,
                'mail.default' => 'resend',
                'mail.mailers.resend' => [
                    'transport' => 'resend',
                ],
            ]);
        }

        $from = PlatformSetting::get('mail_from_address');
        $name = PlatformSetting::get('mail_from_name');
        if ($from) {
            config(['mail.from.address' => $from]);
        }
        if ($name) {
            config(['mail.from.name' => $name]);
        }
    }

    private function applyStripe(): void
    {
        if ($k = PlatformSetting::get('stripe_secret_key')) {
            config(['cashier.secret' => $k, 'services.stripe.secret' => $k]);
        }
        if ($k = PlatformSetting::get('stripe_publishable_key')) {
            config(['cashier.key' => $k, 'services.stripe.key' => $k]);
        }
        if ($k = PlatformSetting::get('stripe_webhook_secret')) {
            config(['cashier.webhook.secret' => $k, 'services.stripe.webhook.secret' => $k]);
        }
        foreach (['starter', 'growth', 'scale'] as $tier) {
            if ($price = PlatformSetting::get("stripe_price_{$tier}")) {
                config(["eiaaw.plans.{$tier}.price_id" => $price]);
            }
        }
    }

    private function applyAi(): void
    {
        if ($k = PlatformSetting::get('anthropic_api_key')) {
            config(['services.anthropic.key' => $k]);
        }
        if ($k = PlatformSetting::get('openai_api_key')) {
            config(['services.openai.key' => $k]);
        }
    }

    private function applyR2(): void
    {
        $bucket = PlatformSetting::get('r2_bucket');
        $key = PlatformSetting::get('r2_access_key_id');
        $secret = PlatformSetting::get('r2_secret_access_key');
        $endpoint = PlatformSetting::get('r2_endpoint');
        if ($bucket && $key && $secret && $endpoint) {
            config([
                'filesystems.disks.r2' => [
                    'driver' => 's3',
                    'key' => $key,
                    'secret' => $secret,
                    'region' => 'auto',
                    'bucket' => $bucket,
                    'endpoint' => $endpoint,
                    'use_path_style_endpoint' => true,
                    'throw' => false,
                ],
            ]);
        }
    }

    private function applySentry(): void
    {
        if ($dsn = PlatformSetting::get('sentry_dsn')) {
            config(['sentry.dsn' => $dsn]);
        }
    }
}
