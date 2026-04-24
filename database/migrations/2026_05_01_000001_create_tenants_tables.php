<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * EIAAW Workforce — multi-tenant SaaS foundation.
 *
 * Adds:
 *  - tenants               : the customer organisation paying the subscription
 *  - tenant_users          : pivot table linking auth users to tenants with a role
 *  - ai_conversations      : per-tenant AI chat transcript + token cost log
 *  - ai_usage_daily        : per-tenant per-day token + USD spend summary (drives circuit breaker)
 *  - subscription_events   : Stripe webhook log (idempotent, signature-verified)
 *
 * Does NOT yet add tenant_id to existing tables — that retrofit happens in a
 * dedicated follow-up migration once the Postgres port lands. See DELIVERY-REPORT.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        // EIAAW Workforce SaaS tables are Postgres-only. The Claritas portal
        // (single-tenant, MySQL) doesn't need them and shouldn't have them
        // creating drift between deployments.
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('country_code', 2)->default('MY');
            $table->string('billing_currency', 3)->default('MYR');

            // Plan
            $table->string('plan', 20)->default('starter');
            $table->unsignedInteger('plan_seats')->default(5);
            $table->timestamp('trial_ends_at')->nullable();

            // Stripe
            $table->string('stripe_customer_id')->nullable()->unique();
            $table->string('stripe_subscription_id')->nullable()->unique();
            $table->string('subscription_status', 30)->nullable();

            // Lifecycle
            $table->string('status', 20)->default('active');
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();

            // Enterprise extras
            $table->boolean('uses_dedicated_db')->default(false);
            $table->string('dedicated_db_dsn')->nullable();
            $table->boolean('sso_enabled')->default(false);
            $table->json('sso_config')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('plan');
            $table->index('status');
            $table->index('subscription_status');
        });

        Schema::create('tenant_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('tenant_role', 30)->default('member'); // owner, admin, member
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']);
            $table->index('user_id');

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('role', 16); // user | assistant | system | tool
            $table->string('model', 60)->nullable();
            $table->text('content');
            $table->json('tool_calls')->nullable();
            $table->json('tool_results')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('cache_read_tokens')->default(0);
            $table->unsignedInteger('cache_write_tokens')->default(0);
            $table->decimal('cost_usd', 10, 6)->default(0);
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('session_id', 60)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index('session_id');

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('ai_usage_daily', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->date('usage_date');
            $table->unsignedBigInteger('input_tokens')->default(0);
            $table->unsignedBigInteger('output_tokens')->default(0);
            $table->unsignedBigInteger('cache_read_tokens')->default(0);
            $table->unsignedBigInteger('cache_write_tokens')->default(0);
            $table->decimal('cost_usd', 12, 6)->default(0);
            $table->unsignedInteger('request_count')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'usage_date']);

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        Schema::create('subscription_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('stripe_event_id')->unique();
            $table->string('event_type', 60);
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->text('processing_error')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'event_type']);
            $table->index('event_type');

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('set null');
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::dropIfExists('subscription_events');
        Schema::dropIfExists('ai_usage_daily');
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('tenant_users');
        Schema::dropIfExists('tenants');
    }
};
