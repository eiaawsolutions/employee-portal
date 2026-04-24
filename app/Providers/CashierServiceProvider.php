<?php

namespace App\Providers;

use App\Models\Tenant;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

/**
 * Configure Cashier 16 to bill the Tenant model (not User).
 *
 * - Customer model = Tenant (each tenant has its own Stripe customer)
 * - Owner key = tenant_id (subscriptions FK to tenants)
 * - Default currency comes from config('cashier.currency'), set in .env
 *   to MYR primary; per-tenant override via Tenant::preferredCurrency()
 */
class CashierServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Cashier::useCustomerModel(Tenant::class);
    }
}
