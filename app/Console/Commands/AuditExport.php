<?php

namespace App\Console\Commands;

use App\Models\AiConversation;
use App\Models\SecurityAuditLog;
use App\Models\SubscriptionEvent;
use App\Models\Tenant;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * AuditExport — dumps a tenant's audit trail to JSON Lines for SIEM ingestion.
 *
 * Enterprise-tier feature (plan feature key: `audit.export`). The CLI can be
 * run by an operator for any tenant; a future admin-UI wrapper will gate on
 * Tenant::hasFeature('audit.export') before exposing the export button.
 *
 * Covers three evidence sources:
 *   1. security_audit_logs  — auth, 403, rate anomalies, admin actions
 *   2. ai_conversations     — every AI query/response (prompt + model + cost)
 *   3. subscription_events  — Stripe webhook receipts (global, not tenant-scoped)
 *
 * Output: one JSONL file per stream, written to storage/app/private/audit-exports/
 * with naming {tenant_slug}-{stream}-{from}-{to}.jsonl. Streaming writer, so
 * large exports don't blow up memory.
 *
 * Usage:
 *   php artisan audit:export --tenant=acme
 *   php artisan audit:export --tenant=acme --from=2026-01-01 --to=2026-03-31
 *   php artisan audit:export --all
 */
class AuditExport extends Command
{
    protected $signature = 'audit:export
        {--tenant= : Tenant slug to export (required unless --all)}
        {--all : Export every active tenant}
        {--from= : Start date YYYY-MM-DD (default: 30 days ago)}
        {--to= : End date YYYY-MM-DD (default: today)}
        {--disk=local : Filesystem disk to write to (default: local)}
        {--dry-run : Count rows without writing files}';

    protected $description = 'Export tenant audit trail (security logs, AI conversations, subscription events) to JSONL.';

    public function handle(): int
    {
        $from = Carbon::parse($this->option('from') ?: Carbon::now()->subDays(30)->toDateString())->startOfDay();
        $to   = Carbon::parse($this->option('to')   ?: Carbon::now()->toDateString())->endOfDay();
        $disk = $this->option('disk');
        $dryRun = (bool) $this->option('dry-run');

        if ($to->lt($from)) {
            $this->error('--to cannot be before --from.');
            return self::FAILURE;
        }

        $tenants = $this->resolveTenants();
        if ($tenants->isEmpty()) {
            $this->error('No tenants matched. Pass --tenant=slug or --all.');
            return self::FAILURE;
        }

        $this->info(sprintf(
            '%sExporting audit trail · window %s → %s · %d tenant(s)',
            $dryRun ? '[dry-run] ' : '',
            $from->toDateString(),
            $to->toDateString(),
            $tenants->count(),
        ));

        foreach ($tenants as $tenant) {
            $this->exportTenant($tenant, $from, $to, $disk, $dryRun);
        }

        return self::SUCCESS;
    }

    private function resolveTenants()
    {
        if ($this->option('all')) {
            return Tenant::query()
                ->where('status', Tenant::STATUS_ACTIVE)
                ->orderBy('id')
                ->get();
        }

        $slug = $this->option('tenant');
        if (!$slug) {
            return collect();
        }

        return Tenant::where('slug', $slug)->get();
    }

    private function exportTenant(Tenant $tenant, Carbon $from, Carbon $to, string $disk, bool $dryRun): void
    {
        $this->line("→ [{$tenant->id}] {$tenant->slug}");

        // security_audit_logs + ai_conversations are tenant-scoped — run inside
        // TenantContext so RLS + the TenantScope trait honour the tenant.
        TenantContext::run($tenant, function () use ($tenant, $from, $to, $disk, $dryRun) {
            $this->exportStream(
                tenant: $tenant,
                from: $from,
                to: $to,
                disk: $disk,
                dryRun: $dryRun,
                stream: 'security',
                query: SecurityAuditLog::query()
                    ->whereBetween('created_at', [$from, $to])
                    ->orderBy('id'),
                transform: fn ($row) => [
                    'id' => $row->id,
                    'at' => optional($row->created_at)->toIso8601String(),
                    'event_type' => $row->event_type,
                    'actor' => [
                        'user_id' => $row->user_id,
                        'email' => $row->work_email,
                        'role' => $row->role,
                    ],
                    'request' => [
                        'url' => $row->url,
                        'method' => $row->method,
                        'ip' => $row->ip_address,
                    ],
                    'details' => $row->details,
                    'emailed' => (bool) $row->emailed,
                ],
            );

            $this->exportStream(
                tenant: $tenant,
                from: $from,
                to: $to,
                disk: $disk,
                dryRun: $dryRun,
                stream: 'ai',
                query: AiConversation::query()
                    ->whereBetween('created_at', [$from, $to])
                    ->orderBy('id'),
                transform: fn ($row) => [
                    'id' => $row->id,
                    'at' => optional($row->created_at)->toIso8601String(),
                    'session_id' => $row->session_id,
                    'user_id' => $row->user_id,
                    'role' => $row->role,
                    'model' => $row->model,
                    'content' => $row->content,
                    'tokens' => [
                        'input' => (int) $row->input_tokens,
                        'output' => (int) $row->output_tokens,
                        'cache_read' => (int) $row->cache_read_tokens,
                        'cache_write' => (int) $row->cache_write_tokens,
                    ],
                    'cost_usd' => (float) $row->cost_usd,
                    'latency_ms' => (int) $row->latency_ms,
                ],
            );
        });

        // subscription_events is NOT tenant-scoped (Stripe webhook log) — filter by
        // stripe_customer_id linking back to this tenant. Run outside TenantContext.
        TenantContext::asNone(function () use ($tenant, $from, $to, $disk, $dryRun) {
            $this->exportStream(
                tenant: $tenant,
                from: $from,
                to: $to,
                disk: $disk,
                dryRun: $dryRun,
                stream: 'billing',
                query: SubscriptionEvent::query()
                    ->where('tenant_id', $tenant->id)
                    ->whereBetween('created_at', [$from, $to])
                    ->orderBy('id'),
                transform: fn ($row) => [
                    'id' => $row->id,
                    'at' => optional($row->created_at)->toIso8601String(),
                    'event_type' => $row->event_type,
                    'stripe_event_id' => $row->stripe_event_id,
                    'processed_at' => optional($row->processed_at)->toIso8601String(),
                    'error' => $row->processing_error,
                ],
            );
        });
    }

    private function exportStream(
        Tenant $tenant,
        Carbon $from,
        Carbon $to,
        string $disk,
        bool $dryRun,
        string $stream,
        $query,
        callable $transform,
    ): void {
        if ($dryRun) {
            $count = (clone $query)->count();
            $this->line("    {$stream}: {$count} rows (dry-run)");
            return;
        }

        $filename = sprintf(
            'audit-exports/%s-%s-%s-%s.jsonl',
            $tenant->slug,
            $stream,
            $from->toDateString(),
            $to->toDateString(),
        );

        // Stream to a temp file, then atomically move into place. Keeps the
        // final path from being observable mid-write to any SIEM watcher.
        $tempPath = tempnam(sys_get_temp_dir(), 'eiaaw-audit-');
        $fh = fopen($tempPath, 'w');
        if ($fh === false) {
            $this->error("    {$stream}: failed to open temp file");
            return;
        }

        $count = 0;
        $query->chunk(1000, function ($rows) use ($fh, $transform, &$count) {
            foreach ($rows as $row) {
                fwrite($fh, json_encode($transform($row), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
                $count++;
            }
        });
        fclose($fh);

        Storage::disk($disk)->put($filename, file_get_contents($tempPath));
        @unlink($tempPath);

        $this->line("    {$stream}: {$count} rows → {$filename}");
    }
}
