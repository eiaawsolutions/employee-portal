<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pay-first signup: the invite now carries the Stripe Checkout it was sent
 * through and when it was paid. A workspace is only provisioned from a paid
 * invite (SignupController::confirm).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signup_invites', function (Blueprint $t) {
            $t->unsignedInteger('seats')->nullable();
            $t->string('billing_period', 10)->nullable();
            $t->string('stripe_checkout_session_id')->nullable()->index();
            $t->string('stripe_customer_id')->nullable();
            $t->string('stripe_subscription_id')->nullable();
            $t->timestamp('paid_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('signup_invites', function (Blueprint $t) {
            $t->dropIndex(['stripe_checkout_session_id']);
            $t->dropColumn([
                'seats', 'billing_period', 'stripe_checkout_session_id',
                'stripe_customer_id', 'stripe_subscription_id', 'paid_at',
            ]);
        });
    }
};
