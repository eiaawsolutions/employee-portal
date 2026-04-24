<?php

namespace App\Console\Commands;

use App\Services\SystemMetadataService;
use Illuminate\Console\Command;

class RefreshSystemMetadata extends Command
{
    protected $signature = 'system:refresh-metadata';
    protected $description = 'Refresh cached system metadata (platform-wide, NOT per-tenant — counts routes/models/mail classes on disk).';

    /**
     * No TenantContext::forEach here: SystemMetadataService aggregates metadata
     * about the codebase itself (number of routes, models, mail classes,
     * controllers, views, scheduled jobs, git commit). These are platform-wide
     * statistics, identical for every tenant. Iterating per-tenant would do
     * the same work N times and confuse the cache. If we ever expose
     * tenant-specific stats (e.g. "this tenant has X employees"), they will
     * be a separate command, not this one.
     */
    public function handle(SystemMetadataService $service): int
    {
        $this->info('Refreshing system metadata…');

        $data = $service->refresh();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Database Tables', $data['tables']],
                ['Routes / Endpoints', $data['endpoints']],
                ['Mail Classes', $data['mail_classes']],
                ['Models', $data['models']],
                ['Migrations', $data['migrations']],
                ['Scheduled Jobs', $data['scheduled_jobs']],
                ['Controllers', $data['controllers']],
                ['Blade Views', $data['views']],
                ['Modules', $data['module_count']],
                ['Git Commit', $data['git']['hash'] . ' — ' . $data['git']['message']],
            ]
        );

        $this->info('✓ Metadata cached at ' . $data['collected_at']);

        return self::SUCCESS;
    }
}
