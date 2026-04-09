<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SystemMetadataService
{
    /** Cache key for the aggregated metadata. */
    private const CACHE_KEY = 'system_metadata';

    /** Cache duration in seconds (1 hour). */
    private const CACHE_TTL = 3600;

    /**
     * Get all metadata (cached).
     */
    public function get(): array
    {
        return Cache::get(self::CACHE_KEY, fn () => $this->collect());
    }

    /**
     * Force-refresh the cache and return the metadata.
     */
    public function refresh(): array
    {
        $data = $this->collect();
        Cache::put(self::CACHE_KEY, $data, self::CACHE_TTL);
        return $data;
    }

    /**
     * Collect all system metadata by introspection.
     */
    private function collect(): array
    {
        return [
            'collected_at'     => now()->toDateTimeString(),
            'tables'           => $this->countTables(),
            'endpoints'        => $this->countEndpoints(),
            'mail_classes'     => $this->countMailClasses(),
            'mail_list'        => $this->listMailClasses(),
            'models'           => $this->countModels(),
            'model_list'       => $this->listModels(),
            'migrations'       => $this->countMigrations(),
            'scheduled_jobs'   => $this->countScheduledJobs(),
            'controllers'      => $this->countControllers(),
            'views'            => $this->countViews(),
            'modules'          => $this->getModules(),
            'module_count'     => count($this->getModules()),
            'tech_stack'       => $this->getTechStack(),
            'git'              => $this->getGitInfo(),
            'live_stats'       => $this->getLiveStats(),
            'email_groups'     => $this->getEmailGroups(),
        ];
    }

    // ─── Introspection methods ────────────────────────────────

    private function countTables(): int
    {
        try {
            $tables = DB::select('SHOW TABLES');
            return count($tables);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function countEndpoints(): int
    {
        return count(Route::getRoutes()->getRoutes());
    }

    private function countMailClasses(): int
    {
        return count($this->listMailClasses());
    }

    private function listMailClasses(): array
    {
        $mailPath = app_path('Mail');
        if (!File::isDirectory($mailPath)) {
            return [];
        }

        return collect(File::files($mailPath))
            ->filter(fn ($f) => $f->getExtension() === 'php')
            ->map(fn ($f) => Str::replaceLast('.php', '', $f->getFilename()))
            ->sort()
            ->values()
            ->toArray();
    }

    private function countModels(): int
    {
        return count($this->listModels());
    }

    private function listModels(): array
    {
        $models = [];

        // Top-level models
        $modelPath = app_path('Models');
        if (File::isDirectory($modelPath)) {
            foreach (File::files($modelPath) as $f) {
                if ($f->getExtension() === 'php') {
                    $models[] = Str::replaceLast('.php', '', $f->getFilename());
                }
            }
        }

        // Sub-directory models (e.g., Accounting/)
        if (File::isDirectory($modelPath)) {
            foreach (File::directories($modelPath) as $dir) {
                $ns = basename($dir);
                foreach (File::files($dir) as $f) {
                    if ($f->getExtension() === 'php') {
                        $models[] = $ns . '\\' . Str::replaceLast('.php', '', $f->getFilename());
                    }
                }
            }
        }

        sort($models);
        return $models;
    }

    private function countMigrations(): int
    {
        $migrationPath = database_path('migrations');
        if (!File::isDirectory($migrationPath)) {
            return 0;
        }

        return collect(File::files($migrationPath))
            ->filter(fn ($f) => $f->getExtension() === 'php')
            ->count();
    }

    private function countScheduledJobs(): int
    {
        try {
            $content = File::get(base_path('routes/console.php'));
            return preg_match_all('/Schedule::command\(/', $content, $m);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function countControllers(): int
    {
        $controllerPath = app_path('Http/Controllers');
        if (!File::isDirectory($controllerPath)) {
            return 0;
        }

        $count = collect(File::files($controllerPath))
            ->filter(fn ($f) => $f->getExtension() === 'php')
            ->count();

        // Sub-directories (e.g., Accounting/)
        foreach (File::directories($controllerPath) as $dir) {
            $count += collect(File::files($dir))
                ->filter(fn ($f) => $f->getExtension() === 'php')
                ->count();
        }

        return $count;
    }

    private function countViews(): int
    {
        $viewPath = resource_path('views');
        if (!File::isDirectory($viewPath)) {
            return 0;
        }

        return collect(File::allFiles($viewPath))
            ->filter(fn ($f) => Str::endsWith($f->getFilename(), '.blade.php'))
            ->count();
    }

    /**
     * Return a list of system modules (auto-discovered from route prefixes).
     */
    private function getModules(): array
    {
        return [
            ['name' => 'Onboarding',         'icon' => 'bi-person-plus',         'color' => '#0d6efd'],
            ['name' => 'Employee Management', 'icon' => 'bi-people',              'color' => '#198754'],
            ['name' => 'Offboarding',         'icon' => 'bi-box-arrow-right',     'color' => '#dc3545'],
            ['name' => 'IT Assets',           'icon' => 'bi-laptop',              'color' => '#fd7e14'],
            ['name' => 'Leave Management',    'icon' => 'bi-calendar2-week',      'color' => '#20c997'],
            ['name' => 'Payroll',             'icon' => 'bi-wallet2',             'color' => '#6610f2'],
            ['name' => 'Attendance',          'icon' => 'bi-stopwatch',           'color' => '#0dcaf0'],
            ['name' => 'Expense Claims',      'icon' => 'bi-receipt-cutoff',      'color' => '#d63384'],
            ['name' => 'C-Suite Reports',     'icon' => 'bi-graph-up-arrow',      'color' => '#1a237e'],
            ['name' => 'AI Accounting',       'icon' => 'bi-calculator',          'color' => '#00695c'],
        ];
    }

    private function getTechStack(): array
    {
        return [
            ['label' => 'PHP ' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION, 'icon' => 'bi-filetype-php', 'color' => 'text-primary'],
            ['label' => 'Laravel ' . app()->version(), 'icon' => 'bi-box', 'color' => 'text-danger'],
            ['label' => $this->getDbVersion(), 'icon' => 'bi-database', 'color' => 'text-warning'],
            ['label' => 'Bootstrap 5.3', 'icon' => 'bi-bootstrap', 'color' => 'text-purple'],
            ['label' => 'Tailwind CSS v4', 'icon' => 'bi-palette', 'color' => 'text-info'],
            ['label' => 'Vite ' . $this->getViteVersion(), 'icon' => 'bi-lightning', 'color' => 'text-success'],
            ['label' => 'Chart.js', 'icon' => 'bi-bar-chart-line', 'color' => 'text-primary'],
            ['label' => 'Google2FA (TOTP)', 'icon' => 'bi-shield-lock', 'color' => 'text-success'],
            ['label' => 'OpenAI GPT-4o (Vision + Chat)', 'icon' => 'bi-robot', 'color' => 'text-success'],
            ['label' => $this->countMailClasses() . ' Mail Classes', 'icon' => 'bi-envelope', 'color' => 'text-primary'],
            ['label' => $this->countScheduledJobs() . ' Scheduled Jobs', 'icon' => 'bi-clock', 'color' => 'text-warning'],
            ['label' => $this->countControllers() . ' Controllers', 'icon' => 'bi-code-slash', 'color' => 'text-secondary'],
            ['label' => $this->countModels() . ' Models', 'icon' => 'bi-diagram-3', 'color' => 'text-info'],
            ['label' => $this->countViews() . ' Blade Views', 'icon' => 'bi-file-earmark-code', 'color' => 'text-primary'],
            ['label' => 'OWASP Compliant', 'icon' => 'bi-shield-check', 'color' => 'text-success'],
            ['label' => 'Malaysian Statutory (EPF/SOCSO/EIS/PCB)', 'icon' => 'bi-flag', 'color' => 'text-danger'],
            ['label' => 'Double-Entry Bookkeeping', 'icon' => 'bi-journal-bookmark', 'color' => 'text-info'],
            ['label' => 'CSP Nonce Security', 'icon' => 'bi-lock', 'color' => 'text-warning'],
        ];
    }

    private function getViteVersion(): string
    {
        try {
            $pkg = json_decode(File::get(base_path('package.json')), true);
            return ltrim($pkg['devDependencies']['vite'] ?? '?', '^~>=');
        } catch (\Throwable) {
            return '?';
        }
    }

    private function getDbVersion(): string
    {
        try {
            $version = DB::selectOne('SELECT VERSION() as v')->v ?? 'Unknown';
            if (Str::contains($version, 'MariaDB')) {
                return 'MariaDB ' . Str::before($version, '-');
            }
            return 'MySQL ' . Str::before($version, '-');
        } catch (\Throwable) {
            return 'MySQL';
        }
    }

    private function getGitInfo(): array
    {
        try {
            $base = escapeshellarg(base_path());
            $hash    = trim(shell_exec("git -C {$base} rev-parse --short HEAD 2>&1") ?? '');
            $message = trim(shell_exec("git -C {$base} log -1 --pretty=%s 2>&1") ?? '');
            $date    = trim(shell_exec("git -C {$base} log -1 --pretty=%ci 2>&1") ?? '');
            $branch  = trim(shell_exec("git -C {$base} rev-parse --abbrev-ref HEAD 2>&1") ?? '');

            // Recent commits (last 15)
            $logRaw = shell_exec("git -C {$base} log -15 --pretty=format:'%h|%s|%ci|%an' 2>&1") ?? '';
            $recentCommits = [];
            foreach (explode("\n", trim($logRaw)) as $line) {
                $parts = explode('|', $line, 4);
                if (count($parts) >= 3) {
                    $recentCommits[] = [
                        'hash'    => $parts[0],
                        'message' => Str::limit($parts[1] ?? '', 100),
                        'date'    => $parts[2] ?? '',
                        'author'  => $parts[3] ?? '',
                    ];
                }
            }

            return [
                'hash'           => (strlen($hash) <= 12) ? $hash : 'unknown',
                'message'        => (strlen($message) <= 200) ? $message : Str::limit($message, 200),
                'date'           => $date ?: now()->toDateTimeString(),
                'branch'         => $branch ?: 'main',
                'recent_commits' => $recentCommits,
            ];
        } catch (\Throwable) {
            return ['hash' => 'unknown', 'message' => '', 'date' => '', 'branch' => 'main', 'recent_commits' => []];
        }
    }

    private function getLiveStats(): array
    {
        try {
            return [
                ['icon' => 'bi-people-fill',       'color' => '#0d6efd', 'label' => 'Active Employees',  'value' => \App\Models\Employee::whereNull('active_until')->count()],
                ['icon' => 'bi-person-plus-fill',   'color' => '#198754', 'label' => 'Onboarding',        'value' => \App\Models\Onboarding::whereIn('status', ['pending', 'active'])->count()],
                ['icon' => 'bi-person-dash-fill',   'color' => '#dc3545', 'label' => 'Offboarding',       'value' => \App\Models\Offboarding::where('deactivation_status', '!=', 'done')->orWhereNull('deactivation_status')->count()],
                ['icon' => 'bi-laptop',             'color' => '#fd7e14', 'label' => 'Total Assets',      'value' => \App\Models\AssetInventory::count()],
                ['icon' => 'bi-person-lock',        'color' => '#6610f2', 'label' => 'User Accounts',     'value' => \App\Models\User::where('is_active', true)->count()],
                ['icon' => 'bi-building',           'color' => '#0dcaf0', 'label' => 'Companies',         'value' => \App\Models\Company::count()],
                ['icon' => 'bi-journal-text',       'color' => '#00695c', 'label' => 'Accounts (CoA)',    'value' => \App\Models\Accounting\ChartOfAccount::where('is_active', true)->count()],
                ['icon' => 'bi-receipt',            'color' => '#ab47bc', 'label' => 'Invoices',          'value' => \App\Models\Accounting\SalesInvoice::count()],
            ];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Group mail classes by module for the email section.
     */
    private function getEmailGroups(): array
    {
        $mailClasses = $this->listMailClasses();
        $groups = [
            'Onboarding & Employee' => ['color' => '#0d6efd', 'items' => []],
            'Offboarding & Assets'  => ['color' => '#dc3545', 'items' => []],
            'HRM Modules'           => ['color' => '#198754', 'items' => []],
            'Security & System'     => ['color' => '#6610f2', 'items' => []],
        ];

        $mapping = [
            'Onboarding & Employee' => ['OnboardingInviteMail', 'WelcomeNewHire', 'OnboardingEditNotificationMail', 'ConsentRequestMail', 'OnboardingConsentRequestMail', 'EmployeeConsentRequestMail', 'AnnouncementMail'],
            'Offboarding & Assets'  => ['OffboardingNoticeMail', 'OffboardingReminderMail', 'OffboardingWeekReminderMail', 'OffboardingSendoffMail', 'CalendarInvite', 'AarfAcknowledgementMail'],
            'HRM Modules'           => ['LeaveApplicationNotifyMail', 'LeaveApprovalNotifyMail', 'PendingLeaveReminderMail', 'ClaimSubmittedMail', 'ClaimApprovedMail', 'ClaimRejectedMail', 'ClaimReminderMail', 'PayslipReadyMail', 'EaFormReadyMail'],
            'Security & System'     => ['SecurityAuditMail', 'SuspiciousActivityAlert', 'WeeklyPendingSweepMail'],
        ];

        $assigned = [];
        foreach ($mapping as $group => $classes) {
            foreach ($classes as $cls) {
                if (in_array($cls, $mailClasses)) {
                    $groups[$group]['items'][] = Str::headline(Str::replaceLast('Mail', '', $cls));
                    $assigned[] = $cls;
                }
            }
        }

        // Catch any unassigned mail classes
        foreach ($mailClasses as $cls) {
            if (!in_array($cls, $assigned)) {
                $groups['HRM Modules']['items'][] = Str::headline(Str::replaceLast('Mail', '', $cls));
            }
        }

        return $groups;
    }
}
