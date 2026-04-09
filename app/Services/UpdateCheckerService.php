<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UpdateCheckerService
{
    private const CACHE_KEY = 'system_update_check';
    private const CACHE_TTL = 86400; // 24 hours

    /**
     * Get cached update check results.
     */
    public function get(): array
    {
        return Cache::get(self::CACHE_KEY, fn () => $this->check());
    }

    /**
     * Force-refresh update check.
     */
    public function refresh(): array
    {
        $data = $this->check();
        Cache::put(self::CACHE_KEY, $data, self::CACHE_TTL);
        return $data;
    }

    /**
     * Check all packages for available updates.
     */
    public function check(): array
    {
        $composer = $this->checkComposerPackages();
        $npm = $this->checkNpmPackages();

        $allPackages = array_merge($composer, $npm);
        $outdated = array_filter($allPackages, fn ($p) => $p['update_available']);
        $critical = array_filter($outdated, fn ($p) => $p['severity'] === 'critical');
        $major = array_filter($outdated, fn ($p) => $p['severity'] === 'major');
        $minor = array_filter($outdated, fn ($p) => $p['severity'] === 'minor');
        $patch = array_filter($outdated, fn ($p) => $p['severity'] === 'patch');

        return [
            'checked_at'     => now()->toDateTimeString(),
            'total_packages' => count($allPackages),
            'up_to_date'     => count($allPackages) - count($outdated),
            'outdated'       => count($outdated),
            'critical_count' => count($critical),
            'major_count'    => count($major),
            'minor_count'    => count($minor),
            'patch_count'    => count($patch),
            'packages'       => $allPackages,
            'health'         => $this->calculateHealth($allPackages),
        ];
    }

    /**
     * Check Composer packages by parsing composer.lock against Packagist.
     */
    private function checkComposerPackages(): array
    {
        $lockFile = base_path('composer.lock');
        if (!File::exists($lockFile)) {
            return [];
        }

        try {
            $lock = json_decode(File::get($lockFile), true);
        } catch (\Throwable) {
            return [];
        }

        $packages = [];
        $allPackages = array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []);

        // Key packages to track (skip internal/laravel sub-packages to keep it focused)
        $tracked = [
            'laravel/framework', 'laravel/tinker', 'laravel/pint', 'laravel/sail', 'laravel/pail',
            'pragmarx/google2fa-laravel', 'bacon/bacon-qr-code',
            'phpunit/phpunit', 'fakerphp/faker', 'mockery/mockery',
            'nunomaduro/collision', 'phpoffice/phpword',
        ];

        foreach ($allPackages as $pkg) {
            $name = $pkg['name'] ?? '';
            if (!in_array($name, $tracked)) {
                continue;
            }

            $currentVersion = ltrim($pkg['version'] ?? '', 'v');
            $isDev = in_array($name, array_column($lock['packages-dev'] ?? [], 'name'));

            $latestVersion = $this->getPackagistLatest($name);

            $updateAvailable = $latestVersion && version_compare($currentVersion, $latestVersion, '<');
            $severity = $updateAvailable ? $this->compareSeverity($currentVersion, $latestVersion) : 'current';

            $packages[] = [
                'name'             => $name,
                'type'             => 'composer',
                'is_dev'           => $isDev,
                'current_version'  => $currentVersion,
                'latest_version'   => $latestVersion ?: $currentVersion,
                'update_available' => $updateAvailable,
                'severity'         => $severity,
                'icon'             => 'bi-filetype-php',
            ];
        }

        return $packages;
    }

    /**
     * Check NPM packages by parsing package.json vs npm registry.
     */
    private function checkNpmPackages(): array
    {
        $packageJsonFile = base_path('package.json');
        if (!File::exists($packageJsonFile)) {
            return [];
        }

        try {
            $packageJson = json_decode(File::get($packageJsonFile), true);
        } catch (\Throwable) {
            return [];
        }

        $packages = [];
        $allDeps = array_merge(
            $packageJson['dependencies'] ?? [],
            $packageJson['devDependencies'] ?? []
        );
        $devDeps = array_keys($packageJson['devDependencies'] ?? []);

        foreach ($allDeps as $name => $constraint) {
            // Strip constraint prefixes (^, ~, >=)
            $currentVersion = ltrim($constraint, '^~>=<! ');

            $latestVersion = $this->getNpmLatest($name);
            $updateAvailable = $latestVersion && version_compare($currentVersion, $latestVersion, '<');
            $severity = $updateAvailable ? $this->compareSeverity($currentVersion, $latestVersion) : 'current';

            $packages[] = [
                'name'             => $name,
                'type'             => 'npm',
                'is_dev'           => in_array($name, $devDeps),
                'current_version'  => $currentVersion,
                'latest_version'   => $latestVersion ?: $currentVersion,
                'update_available' => $updateAvailable,
                'severity'         => $severity,
                'icon'             => 'bi-filetype-js',
            ];
        }

        return $packages;
    }

    /**
     * Fetch the latest stable version from Packagist.
     */
    private function getPackagistLatest(string $package): ?string
    {
        try {
            $response = Http::timeout(5)
                ->get("https://repo.packagist.org/p2/{$package}.json");

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            $versions = $data['packages'][$package] ?? [];

            // Find latest stable (non-dev, non-alpha, non-beta, non-RC)
            foreach ($versions as $v) {
                $ver = $v['version'] ?? '';
                $normalized = $v['version_normalized'] ?? '';

                // Skip dev versions
                if (Str::contains($ver, ['dev', 'alpha', 'beta', 'RC', 'rc'], true)) {
                    continue;
                }

                return ltrim($ver, 'v');
            }

            return null;
        } catch (\Throwable $e) {
            Log::debug("UpdateChecker: Failed to fetch Packagist data for {$package}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch the latest stable version from npm registry.
     */
    private function getNpmLatest(string $package): ?string
    {
        try {
            $encoded = str_replace('/', '%2f', $package);
            $response = Http::timeout(5)
                ->get("https://registry.npmjs.org/{$encoded}/latest");

            if (!$response->successful()) {
                return null;
            }

            return $response->json('version');
        } catch (\Throwable $e) {
            Log::debug("UpdateChecker: Failed to fetch npm data for {$package}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Compare two semver versions and return severity.
     */
    private function compareSeverity(string $current, string $latest): string
    {
        $cur = explode('.', $current);
        $lat = explode('.', $latest);

        $curMajor = (int) ($cur[0] ?? 0);
        $latMajor = (int) ($lat[0] ?? 0);
        $curMinor = (int) ($cur[1] ?? 0);
        $latMinor = (int) ($lat[1] ?? 0);

        if ($latMajor > $curMajor) {
            // 2+ major versions behind = critical
            return ($latMajor - $curMajor) >= 2 ? 'critical' : 'major';
        }

        if ($latMinor > $curMinor) {
            return 'minor';
        }

        return 'patch';
    }

    /**
     * Calculate overall dependency health percentage.
     */
    private function calculateHealth(array $packages): int
    {
        if (empty($packages)) {
            return 100;
        }

        $total = count($packages);
        $penalties = 0;

        foreach ($packages as $pkg) {
            if (!$pkg['update_available']) {
                continue;
            }

            $penalties += match ($pkg['severity']) {
                'critical' => 4,
                'major'    => 2,
                'minor'    => 1,
                'patch'    => 0.5,
                default    => 0,
            };
        }

        $maxPenalty = $total * 4;
        $health = max(0, round(100 - ($penalties / $maxPenalty * 100)));

        return $health;
    }
}
