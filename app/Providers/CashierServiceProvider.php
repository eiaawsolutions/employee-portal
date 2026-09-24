<?php

namespace App\Providers;

use App\Models\Tenant;
use App\Services\Billing\CashierStripeGateway;
use App\Services\Billing\StripeGateway;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

/**
 * Configure Cashier 16 to bill the Tenant model (not User).
 *
 * - Customer model = Tenant (each tenant has its own Stripe customer)
 * - Owner key = tenant_id (subscriptions FK to tenants)
 * - Billing currency is MYR (config('cashier.currency') / Tenant::preferredCurrency())
 * - StripeGateway → Cashier's client, used by the pay-first signup checkout
 */
class CashierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StripeGateway::class, CashierStripeGateway::class);
    }

    public function boot(): void
    {
        Cashier::useCustomerModel(Tenant::class);
    }
}
