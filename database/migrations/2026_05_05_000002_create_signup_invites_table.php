<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * signup_invites — pending tenant signups (post-form, pre-confirmation).
 *
 * The Wk2 trial signup flow:
 *   1. Visitor fills signup form on ep.eiaawsolutions.com/signup
 *      (work_email, full_name, company_name, desired_slug)
 *   2. Row inserted here; confirmation email with token sent to work_email
 *   3. Visitor clicks confirmation link → POST creates the Tenant + first
 *      owner User (with chosen password), starts trial, redirects to
 *      slug.ep.eiaawsolutions.com/dashboard, deletes this row
 *
 * Tokens expire in 24 hours (longer than password reset because new users
 * may not check email immediately). Cleanup is via the existing schedule.
 *
 * This table is GLOBAL (no tenant_id) — it lives at the marketing apex
 * before any tenant exists. RLS is intentionally not applied.
 *
 * Postgres-only (the legacy MySQL Claritas portal is invite-only HR-driven).
 */
return new class extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::create('signup_invites', function (Blueprint $table) {
            $table->id();
            $table->string('work_email')->unique();
            $table->string('full_name');
            $table->string('company_name');
            $table->string('desired_slug', 60)->unique();
            $table->string('confirmation_token', 64)->unique();
            $table->string('plan', 20)->default('growth');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('expires_at');
            $table->ipAddress('signup_ip')->nullable();
            $table->string('signup_user_agent', 500)->nullable();
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::dropIfExists('signup_invites');
    }
};
