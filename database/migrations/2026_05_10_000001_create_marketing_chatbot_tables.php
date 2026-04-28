<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Marketing chatbot + contact-form tables.
 *
 *  - marketing_contacts:  Talk-to-us form submissions from apex pages.
 *  - marketing_chat_log:  one row per chatbot turn (no message bodies; char
 *                         counts + tokens + cost only; privacy-by-default).
 *
 * Both tables are GLOBAL (no tenant_id) — they live at the apex marketing
 * surface before any tenant exists. RLS is intentionally not applied. They
 * follow the same shape as signup_invites.
 *
 * Postgres-only (matches the rest of the EP schema).
 */
return new class extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::create('marketing_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 191);
            $table->string('phone', 40)->nullable();
            $table->string('company', 191)->nullable();
            $table->text('message');
            $table->string('source', 40)->default('chatbot'); // chatbot | landing-form | faq-form
            $table->ipAddress('ip')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('emailed_at')->nullable(); // set when sales notification dispatched
            $table->timestamps();

            $table->index('email');
            $table->index('created_at');
            $table->index('source');
        });

        Schema::create('marketing_chat_log', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 64)->index(); // client-set or generated; groups multi-turn
            $table->unsignedInteger('user_message_chars');
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->decimal('cost_usd', 10, 6)->default(0);
            $table->string('model', 64);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->boolean('was_refused')->default(false);
            $table->string('refusal_reason', 80)->nullable();
            $table->ipAddress('ip')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_chat_log');
        Schema::dropIfExists('marketing_contacts');
    }
};
