<?php

namespace App\Console\Commands;

use App\Services\SecurityScoreService;
use App\Services\UpdateCheckerService;
use Illuminate\Console\Command;

class CheckSystemUpdates extends Command
{
    protected $signature = 'system:check-updates';
    protected $description = 'Check for available package updates and refresh the security score';

    public function handle(UpdateCheckerService $updates, SecurityScoreService $security): int
    {
        $this->info('Checking for package updates...');

        $data = $updates->refresh();

        $this->table(
            ['Package', 'Type', 'Current', 'Latest', 'Status'],
            collect($data['packages'])->map(fn ($p) => [
                $p['name'],
                strtoupper($p['type']),
                $p['current_version'],
                $p['latest_version'],
                $p['update_available']
                    ? '<fg=yellow>' . strtoupper($p['severity']) . '</>'
                    : '<fg=green>OK</>',
            ])->toArray()
        );

        $this->newLine();
        $this->info("Total: {$data['total_packages']} packages | Up to date: {$data['up_to_date']} | Outdated: {$data['outdated']}");

        if ($data['critical_count'] > 0) {
            $this->error("{$data['critical_count']} package(s) are critically outdated!");
        }

        // Also refresh security score
        $this->newLine();
        $this->info('Refreshing security score...');
        $score = $security->refresh();
        $this->info("Security Score: {$score['score']}/100 (Grade: {$score['grade']}) | Passed: {$score['passed']}/{$score['total_checks']}");

        return self::SUCCESS;
    }
}
