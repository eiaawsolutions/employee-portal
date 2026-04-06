<?php

namespace App\Console\Commands;

use App\Services\SystemMetadataService;
use Illuminate\Console\Command;

class RefreshSystemMetadata extends Command
{
    protected $signature = 'system:refresh-metadata';
    protected $description = 'Refresh cached system metadata for System Overview and Knowledge Base pages';

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
