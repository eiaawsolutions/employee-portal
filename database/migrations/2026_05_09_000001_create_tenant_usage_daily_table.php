<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tenant_usage_daily — daily snapshot of cheap, allowlisted aggregates per
 * tenant. Populated by `meter:tenant-usage` (runs daily ~03:45 after the
 * billing rollups). Used by the HQ Overview dashboard for cost allocation
 * and capacity planning.
 *
 * Privacy: every column here is an aggregate. No row-level tenant data is
 * stored. See app/Support/PlatformAdminVisibility.php for the full contract.
 *
 * Why a separate daily table (vs derive-on-read):
 *   - HQ aggregates across N tenants would require N expensive scans per
 *     page-load. Daily snapshot keeps the dashboard cheap.
 *   - Storage size + email volume need history (trend charts). Live counts
 *     give a point-in-time number with no time series.
 *   - Decouples display from the live AI/files/email subsystems so a slow
 *     bucket query never bricks the HQ dashboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_usage_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('usage_date');

            // Capacity / counts
            $table->unsignedInteger('users_count')->default(0);
            $table->unsignedInteger('employees_count')->default(0);
            $table->unsignedBigInteger('db_row_count_total')->default(0);

            // Storage (private + public buckets, in megabytes)
            $table->unsignedBigInteger('storage_mb')->default(0);

            // Email volume (sent in trailing 30 days from this snapshot)
            $table->unsignedInteger('emails_sent_30d')->default(0);

            // AI cost mirror (sum of ai_usage_daily for trailing 30 days from
            // this snapshot — denormalised so the HQ overview can read this
            // table alone).
            $table->unsignedInteger('ai_requests_30d')->default(0);
            $table->unsignedBigInteger('ai_tokens_30d')->default(0);
            $table->decimal('ai_cost_usd_30d', 12, 6)->default(0);

            // Last activity heuristic — max(users.last_login_at,
            // ai_conversations.created_at) — used for churn signals.
            $table->timestamp('last_active_at')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'usage_date']);
            $table->index('usage_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_usage_daily');
    }
};
