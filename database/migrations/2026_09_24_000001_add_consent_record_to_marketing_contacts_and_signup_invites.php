<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consent record for the public forms (PDPA + APAC baseline): when the
 * visitor ticked the box, and which version of the privacy notice / terms
 * they agreed to (config('eiaaw.privacy_version')).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['marketing_contacts', 'signup_invites'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->timestamp('consent_at')->nullable();
                $t->string('consent_version', 20)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['marketing_contacts', 'signup_invites'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['consent_at', 'consent_version']);
            });
        }
    }
};
