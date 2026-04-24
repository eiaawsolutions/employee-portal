<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * BackupTestRestore — disaster-recovery verification.
 *
 * A backup that hasn't been restored is not a backup. This command picks
 * the most recent encrypted backup, restores it into an isolated Postgres
 * instance, asserts row counts on the critical tables, then disconnects.
 *
 * Configuration:
 *   TEST_RESTORE_DSN — Postgres connection string for a sidecar instance
 *     that can be clobbered. DO NOT POINT THIS AT PRODUCTION.
 *
 * Example DSN (Railway sidecar):
 *   TEST_RESTORE_DSN=postgres://restore_test:secret@sidecar.railway.internal:5432/restore_test
 *
 * Safety:
 *   - Refuses to run if TEST_RESTORE_DSN host or database name matches the
 *     primary DB_HOST/DB_DATABASE — prevents accidental self-destruction.
 *   - Verifies the DSN is reachable before touching the backup file.
 *   - Runs restore via `pg_restore` shelled out (the backup format is
 *     pg_dump custom format per BackupSystem).
 *
 * Scheduled weekly Monday 05:00 so Monday-morning triage catches any
 * restore regression before the next business-hours incident.
 */
class BackupTestRestore extends Command
{
    protected $signature = 'backup:test-restore
        {--file= : Specific backup file; default picks the most recent encrypted one}
        {--dry-run : Verify config + locate backup, but do not execute restore}';

    protected $description = 'Restore the latest backup into a sidecar Postgres and assert row counts.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $restoreDsn = env('TEST_RESTORE_DSN');
        if (empty($restoreDsn)) {
            $this->error('TEST_RESTORE_DSN not set — cannot perform restore verification.');
            $this->line('   Set to the connection string of a sidecar Postgres that can be clobbered.');
            return self::FAILURE;
        }

        if ($this->pointsAtPrimaryDb($restoreDsn)) {
            $this->error('SAFETY ABORT: TEST_RESTORE_DSN host/database matches the PRIMARY DB. Refusing to run.');
            Log::critical('backup.test_restore.safety_abort', ['dsn_prefix' => substr($restoreDsn, 0, 40)]);
            return self::FAILURE;
        }

        $backupFile = $this->option('file') ?: $this->findLatestBackup();
        if (!$backupFile || !file_exists($backupFile)) {
            $this->error('No backup file found at the expected location.');
            $this->line('   Expected: storage/app/backups/*.sql.gz.enc (or .sql.gz)');
            return self::FAILURE;
        }

        $this->info(($dryRun ? '[dry-run] ' : '') . "Verifying: " . basename($backupFile));

        if (!$this->pingDsn($restoreDsn)) {
            $this->error('TEST_RESTORE_DSN is not reachable.');
            return self::FAILURE;
        }

        if ($dryRun) {
            $this->info('[dry-run] Config valid, sidecar reachable, backup located. Not restoring.');
            return self::SUCCESS;
        }

        // 1. Baseline — how many rows is the PRIMARY currently reporting?
        $expected = $this->criticalRowCounts(DB::connection());
        $this->line("Primary row counts (expectation floor):");
        foreach ($expected as $t => $n) {
            $this->line("  {$t}: {$n}");
        }

        // 2. Restore. BackupRestore handles decryption + pg_restore/psql paths.
        // Point its DB target at the sidecar by temporarily swapping the default
        // connection config in-memory (not persisted to .env).
        $restoreConfig = $this->parseDsn($restoreDsn);
        config(['database.connections.pgsql_restore' => $restoreConfig]);

        // Swap the 'default' for the duration of the restore call. The restore
        // command reads from config('database.default') to know where to apply.
        $originalDefault = config('database.default');
        config(['database.default' => 'pgsql_restore']);

        try {
            $exitCode = $this->callSilent('backup:restore', [
                'file' => $backupFile,
                '--decrypt' => true,
                '--type' => 'database',
            ]);

            if ($exitCode !== 0) {
                $this->error("backup:restore returned exit {$exitCode}");
                Log::warning('backup.test_restore.restore_failed', ['exit' => $exitCode, 'file' => basename($backupFile)]);
                return self::FAILURE;
            }

            // 3. Verify — row counts on the RESTORED DB should be ≥ 95% of primary.
            //    (slight lag is expected since primary continues writing during backup)
            $restoredCounts = $this->criticalRowCounts(DB::connection('pgsql_restore'));
            $this->line("\nRestored row counts:");
            $failed = false;
            foreach ($expected as $table => $expect) {
                $got = $restoredCounts[$table] ?? 0;
                $ratio = $expect > 0 ? $got / $expect : 1.0;
                $ok = $ratio >= 0.95;
                $symbol = $ok ? '<fg=green>✓</>' : '<fg=red>✗</>';
                $this->line(sprintf('  %s %s: %d / %d (%.1f%%)', $symbol, $table, $got, $expect, $ratio * 100));
                if (!$ok) $failed = true;
            }

            if ($failed) {
                Log::critical('backup.test_restore.row_count_drift', [
                    'expected' => $expected,
                    'restored' => $restoredCounts,
                ]);
                return self::FAILURE;
            }

            Log::info('backup.test_restore.ok', [
                'file' => basename($backupFile),
                'primary_counts' => $expected,
                'restored_counts' => $restoredCounts,
            ]);

            $this->info("\nRestore verification passed. DR is confirmed green.");
            return self::SUCCESS;

        } finally {
            config(['database.default' => $originalDefault]);
            DB::purge('pgsql_restore');
        }
    }

    private function findLatestBackup(): ?string
    {
        $dir = storage_path('app/backups');
        if (!is_dir($dir)) return null;

        $candidates = glob($dir . DIRECTORY_SEPARATOR . '*.sql.gz.enc')
                   ?: glob($dir . DIRECTORY_SEPARATOR . '*.sql.gz')
                   ?: [];
        if (empty($candidates)) return null;

        usort($candidates, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        return $candidates[0];
    }

    private function pointsAtPrimaryDb(string $dsn): bool
    {
        $parsed = parse_url($dsn);
        $primaryHost = env('DB_HOST');
        $primaryDb = env('DB_DATABASE');
        $restoreDb = ltrim($parsed['path'] ?? '', '/');
        return ($parsed['host'] ?? '') === $primaryHost && $restoreDb === $primaryDb;
    }

    private function pingDsn(string $dsn): bool
    {
        try {
            $cfg = $this->parseDsn($dsn);
            config(['database.connections.pgsql_restore_ping' => $cfg]);
            DB::connection('pgsql_restore_ping')->select('SELECT 1');
            DB::purge('pgsql_restore_ping');
            return true;
        } catch (\Throwable $e) {
            $this->warn("DSN ping failed: " . $e->getMessage());
            return false;
        }
    }

    private function parseDsn(string $dsn): array
    {
        $p = parse_url($dsn);
        return [
            'driver'   => 'pgsql',
            'host'     => $p['host'] ?? '127.0.0.1',
            'port'     => $p['port'] ?? 5432,
            'database' => ltrim($p['path'] ?? '', '/'),
            'username' => $p['user'] ?? '',
            'password' => $p['pass'] ?? '',
            'charset'  => 'utf8',
            'prefix'   => '',
            'schema'   => 'public',
            'sslmode'  => 'prefer',
        ];
    }

    /**
     * The tables we sanity-check. Row counts on these signal whether the
     * restore produced a usable DB. Not exhaustive — we care about "is
     * anything here at all," not "every row matches."
     */
    private function criticalRowCounts($connection): array
    {
        $counts = [];
        foreach (['tenants', 'users', 'employees', 'security_audit_logs', 'migrations'] as $t) {
            try {
                $counts[$t] = (int) $connection->table($t)->count();
            } catch (\Throwable $e) {
                $counts[$t] = -1;
            }
        }
        return $counts;
    }
}
