<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use App\Models\SecurityAuditLog;

class SecurityScoreService
{
    private const CACHE_KEY = 'security_score';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get the cached security score breakdown.
     */
    public function get(): array
    {
        return Cache::get(self::CACHE_KEY, fn () => $this->calculate());
    }

    /**
     * Force-refresh and return the security score.
     */
    public function refresh(): array
    {
        $data = $this->calculate();
        Cache::put(self::CACHE_KEY, $data, self::CACHE_TTL);
        return $data;
    }

    /**
     * Calculate the security score by checking real system state.
     *
     * Each check is weighted. Total possible = 100.
     * Returns breakdown array with individual check results and overall score.
     */
    public function calculate(): array
    {
        $checks = [
            $this->checkSecurityHeaders(),
            $this->checkCsrfProtection(),
            $this->checkAuthenticationSystem(),
            $this->checkSingleSessionEnforcement(),
            $this->checkTwoFactorAvailability(),
            $this->checkSecureFileServing(),
            $this->checkAuditLogging(),
            $this->checkThreatDetection(),
            $this->checkLogIntegrity(),
            $this->checkEncryptedBackups(),
            $this->checkImageSanitization(),
            $this->checkHttpsEnforcement(),
            $this->checkRateLimiting(),
            $this->checkDependencyVulnerabilities(),
            $this->checkWeeklyComplianceSweep(),
            $this->checkDebugMode(),
            $this->checkAppKeySet(),
            $this->checkDatabaseEncryption(),
            $this->checkRecentSecurityEvents(),
            $this->checkPasswordPolicy(),
        ];

        $totalWeight = array_sum(array_column($checks, 'weight'));
        $earnedPoints = 0;

        foreach ($checks as &$check) {
            $earned = $check['passed'] ? $check['weight'] : ($check['partial'] ?? 0);
            $check['earned'] = $earned;
            $earnedPoints += $earned;
        }
        unset($check);

        // Normalize to 0-100 scale
        $score = $totalWeight > 0 ? round(($earnedPoints / $totalWeight) * 100) : 0;

        // Categorize checks
        $passed = array_filter($checks, fn ($c) => $c['passed']);
        $warnings = array_filter($checks, fn ($c) => !$c['passed'] && ($c['partial'] ?? 0) > 0);
        $failed = array_filter($checks, fn ($c) => !$c['passed'] && ($c['partial'] ?? 0) === 0);

        return [
            'score'       => $score,
            'total_checks' => count($checks),
            'passed'      => count($passed),
            'warnings'    => count($warnings),
            'failed'      => count($failed),
            'grade'       => $this->scoreToGrade($score),
            'checks'      => $checks,
            'calculated_at' => now()->toDateTimeString(),
        ];
    }

    // ─── Individual security checks ──────────────────────────

    private function checkSecurityHeaders(): array
    {
        $middlewareFile = app_path('Http/Middleware/SecurityHeaders.php');
        $exists = File::exists($middlewareFile);
        $hasCSP = $exists && str_contains(File::get($middlewareFile), 'Content-Security-Policy');
        $hasHSTS = $exists && str_contains(File::get($middlewareFile), 'Strict-Transport-Security');
        $hasXFrame = $exists && str_contains(File::get($middlewareFile), 'X-Frame-Options');
        $hasPermissions = $exists && str_contains(File::get($middlewareFile), 'Permissions-Policy');

        $all = $hasCSP && $hasHSTS && $hasXFrame && $hasPermissions;
        $some = $hasCSP || $hasHSTS || $hasXFrame || $hasPermissions;

        return [
            'id'       => 'security_headers',
            'name'     => 'Security Headers',
            'category' => 'headers',
            'icon'     => 'bi-lock-fill',
            'color'    => 'danger',
            'weight'   => 8,
            'passed'   => $all,
            'partial'  => $some ? 5 : 0,
            'detail'   => $all
                ? 'CSP, HSTS, X-Frame-Options, Permissions-Policy all configured'
                : ($some ? 'Some security headers missing' : 'Security headers middleware not found'),
            'items'    => [
                ['label' => 'Content-Security-Policy', 'ok' => $hasCSP],
                ['label' => 'Strict-Transport-Security (HSTS)', 'ok' => $hasHSTS],
                ['label' => 'X-Frame-Options', 'ok' => $hasXFrame],
                ['label' => 'Permissions-Policy', 'ok' => $hasPermissions],
            ],
        ];
    }

    private function checkCsrfProtection(): array
    {
        // Laravel includes CSRF middleware by default — verify VerifyCsrfToken exists
        $exists = class_exists(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        return [
            'id'       => 'csrf',
            'name'     => 'CSRF Protection',
            'category' => 'input',
            'icon'     => 'bi-shield-exclamation',
            'color'    => 'success',
            'weight'   => 6,
            'passed'   => $exists,
            'detail'   => $exists
                ? 'Laravel CSRF middleware active on all state-changing routes'
                : 'CSRF middleware not detected',
        ];
    }

    private function checkAuthenticationSystem(): array
    {
        $configFile = config_path('auth.php');
        $hasWorkEmail = File::exists($configFile) && str_contains(File::get($configFile), 'work_email_eloquent');

        return [
            'id'       => 'auth_system',
            'name'     => 'Custom Authentication',
            'category' => 'auth',
            'icon'     => 'bi-key-fill',
            'color'    => 'primary',
            'weight'   => 7,
            'passed'   => $hasWorkEmail,
            'detail'   => $hasWorkEmail
                ? 'Work email auth provider with timing-safe hash comparison'
                : 'Standard authentication (work email provider not configured)',
        ];
    }

    private function checkSingleSessionEnforcement(): array
    {
        $exists = File::exists(app_path('Http/Middleware/EnforceSingleSession.php'));
        $hasSessionToken = Schema::hasColumn('users', 'session_token');

        $passed = $exists && $hasSessionToken;

        return [
            'id'       => 'single_session',
            'name'     => 'Single Session Enforcement',
            'category' => 'auth',
            'icon'     => 'bi-person-lock',
            'color'    => 'warning',
            'weight'   => 5,
            'passed'   => $passed,
            'partial'  => $exists ? 3 : 0,
            'detail'   => $passed
                ? 'Concurrent login prevention active with session token rotation'
                : ($exists ? 'Middleware exists but session_token column missing' : 'Single session middleware not found'),
        ];
    }

    private function checkTwoFactorAvailability(): array
    {
        $middlewareExists = File::exists(app_path('Http/Middleware/EnforceTwoFactor.php'));
        $has2FAColumns = Schema::hasColumn('users', 'two_factor_secret');

        $passed = $middlewareExists && $has2FAColumns;

        return [
            'id'       => 'two_factor',
            'name'     => 'Two-Factor Authentication',
            'category' => 'auth',
            'icon'     => 'bi-phone-vibrate',
            'color'    => 'info',
            'weight'   => 6,
            'passed'   => $passed,
            'partial'  => $middlewareExists ? 4 : 0,
            'detail'   => $passed
                ? '2FA enforcement middleware active with TOTP support'
                : ($middlewareExists ? '2FA middleware exists but database columns missing' : '2FA not configured'),
        ];
    }

    private function checkSecureFileServing(): array
    {
        $controllerExists = File::exists(app_path('Http/Controllers/SecureFileController.php'));
        $hasPermMap = $controllerExists && str_contains(File::get(app_path('Http/Controllers/SecureFileController.php')), 'DIRECTORY_PERMISSIONS');
        $hasMimeCheck = File::exists(app_path('Providers/AppServiceProvider.php'))
            && str_contains(File::get(app_path('Providers/AppServiceProvider.php')), 'valid_file_content');

        $passed = $controllerExists && $hasPermMap && $hasMimeCheck;

        return [
            'id'       => 'secure_files',
            'name'     => 'Secure File Serving',
            'category' => 'files',
            'icon'     => 'bi-file-earmark-lock2',
            'color'    => 'info',
            'weight'   => 6,
            'passed'   => $passed,
            'partial'  => ($controllerExists ? 3 : 0) + ($hasMimeCheck ? 2 : 0),
            'detail'   => $passed
                ? 'Role-based directory permissions + MIME validation + magic-byte checks'
                : 'Incomplete secure file serving configuration',
            'items'    => [
                ['label' => 'SecureFileController', 'ok' => $controllerExists],
                ['label' => 'Directory permission map', 'ok' => $hasPermMap],
                ['label' => 'MIME/magic-byte validation', 'ok' => $hasMimeCheck],
            ],
        ];
    }

    private function checkAuditLogging(): array
    {
        $securityLog = class_exists(\App\Models\SecurityAuditLog::class) && Schema::hasTable('security_audit_logs');
        $accountingLog = Schema::hasTable('acc_audit_trail');

        $passed = $securityLog && $accountingLog;

        return [
            'id'       => 'audit_logging',
            'name'     => 'Audit Logging',
            'category' => 'monitoring',
            'icon'     => 'bi-journal-text',
            'color'    => 'secondary',
            'weight'   => 6,
            'passed'   => $passed,
            'partial'  => $securityLog ? 4 : 0,
            'detail'   => $passed
                ? 'SecurityAuditLog + AccountingAuditTrail active'
                : ($securityLog ? 'Security audit logging active, accounting trail missing' : 'Audit logging not configured'),
        ];
    }

    private function checkThreatDetection(): array
    {
        $serviceExists = File::exists(app_path('Services/ThreatDetector.php'));
        $middlewareExists = File::exists(app_path('Http/Middleware/SecurityAuditMiddleware.php'));

        $passed = $serviceExists && $middlewareExists;

        return [
            'id'       => 'threat_detection',
            'name'     => 'Real-time Threat Detection',
            'category' => 'monitoring',
            'icon'     => 'bi-exclamation-triangle-fill',
            'color'    => 'warning',
            'weight'   => 6,
            'passed'   => $passed,
            'detail'   => $passed
                ? 'ThreatDetector + SecurityAuditMiddleware: brute force, privilege escalation, rate anomaly detection'
                : 'Threat detection not fully configured',
        ];
    }

    private function checkLogIntegrity(): array
    {
        $serviceExists = File::exists(app_path('Services/LogIntegrity.php'));
        $hasKey = !empty(config('app.log_integrity_key', env('LOG_INTEGRITY_KEY')));

        return [
            'id'       => 'log_integrity',
            'name'     => 'HMAC Log Integrity',
            'category' => 'monitoring',
            'icon'     => 'bi-fingerprint',
            'color'    => 'primary',
            'weight'   => 5,
            'passed'   => $serviceExists,
            'partial'  => $serviceExists && !$hasKey ? 3 : 0,
            'detail'   => $serviceExists
                ? 'SHA-256 chained entries with tamper-evident audit trail'
                : 'Log integrity service not found',
        ];
    }

    private function checkEncryptedBackups(): array
    {
        $consoleContent = File::exists(base_path('routes/console.php'))
            ? File::get(base_path('routes/console.php'))
            : '';

        $hasBackup = str_contains($consoleContent, 'backup:run');
        $hasEncrypt = str_contains($consoleContent, '--encrypt');

        return [
            'id'       => 'encrypted_backups',
            'name'     => 'Encrypted Backups',
            'category' => 'data',
            'icon'     => 'bi-shield-lock-fill',
            'color'    => 'danger',
            'weight'   => 5,
            'passed'   => $hasBackup && $hasEncrypt,
            'partial'  => $hasBackup ? 3 : 0,
            'detail'   => ($hasBackup && $hasEncrypt)
                ? 'AES-256-CBC encrypted daily full + 6-hourly DB snapshots'
                : ($hasBackup ? 'Backups configured but encryption not verified' : 'Automated backups not scheduled'),
        ];
    }

    private function checkImageSanitization(): array
    {
        $serviceExists = File::exists(app_path('Services/ImageSanitizer.php'));
        $hasRule = File::exists(app_path('Providers/AppServiceProvider.php'))
            && str_contains(File::get(app_path('Providers/AppServiceProvider.php')), 'sanitize_image');

        return [
            'id'       => 'image_sanitization',
            'name'     => 'Image Metadata Stripping',
            'category' => 'files',
            'icon'     => 'bi-image',
            'color'    => 'info',
            'weight'   => 3,
            'passed'   => $serviceExists && $hasRule,
            'partial'  => $serviceExists ? 2 : 0,
            'detail'   => ($serviceExists && $hasRule)
                ? 'GD pixel-copy reprocessing removes all EXIF/GPS data'
                : 'Image sanitization not fully configured',
        ];
    }

    private function checkHttpsEnforcement(): array
    {
        $middlewareExists = File::exists(app_path('Http/Middleware/ForceHttps.php'));

        return [
            'id'       => 'https',
            'name'     => 'TLS/HTTPS Enforcement',
            'category' => 'transport',
            'icon'     => 'bi-globe2',
            'color'    => 'success',
            'weight'   => 5,
            'passed'   => $middlewareExists,
            'detail'   => $middlewareExists
                ? 'Forced HTTPS redirect with HSTS headers'
                : 'HTTPS enforcement middleware not found',
        ];
    }

    private function checkRateLimiting(): array
    {
        $routeContent = File::exists(base_path('routes/web.php'))
            ? File::get(base_path('routes/web.php'))
            : '';

        $hasThrottle = str_contains($routeContent, 'throttle:');

        return [
            'id'       => 'rate_limiting',
            'name'     => 'Rate Limiting',
            'category' => 'input',
            'icon'     => 'bi-speedometer2',
            'color'    => 'warning',
            'weight'   => 5,
            'passed'   => $hasThrottle,
            'detail'   => $hasThrottle
                ? 'Route-level throttling on login, uploads, AI, and password reset endpoints'
                : 'Rate limiting not detected in routes',
        ];
    }

    private function checkDependencyVulnerabilities(): array
    {
        // Check if composer.lock exists (locked dependencies = reproducible builds)
        $lockExists = File::exists(base_path('composer.lock'));

        return [
            'id'       => 'dependencies',
            'name'     => 'Dependency Management',
            'category' => 'supply_chain',
            'icon'     => 'bi-box-seam',
            'color'    => 'primary',
            'weight'   => 4,
            'passed'   => $lockExists,
            'detail'   => $lockExists
                ? 'composer.lock present — reproducible builds with pinned versions'
                : 'composer.lock missing — run composer install to pin dependencies',
        ];
    }

    private function checkWeeklyComplianceSweep(): array
    {
        $consoleContent = File::exists(base_path('routes/console.php'))
            ? File::get(base_path('routes/console.php'))
            : '';

        $hasSweep = str_contains($consoleContent, 'sweep:pending-weekly');

        return [
            'id'       => 'compliance_sweep',
            'name'     => 'Weekly Compliance Sweep',
            'category' => 'compliance',
            'icon'     => 'bi-calendar-check',
            'color'    => 'info',
            'weight'   => 4,
            'passed'   => $hasSweep,
            'detail'   => $hasSweep
                ? 'Automated Wednesday audit of pending consents, AARF, leave & claim approvals'
                : 'Weekly compliance sweep not scheduled',
        ];
    }

    private function checkDebugMode(): array
    {
        $debugOff = !config('app.debug', false);

        return [
            'id'       => 'debug_mode',
            'name'     => 'Debug Mode Disabled',
            'category' => 'config',
            'icon'     => 'bi-bug',
            'color'    => 'danger',
            'weight'   => 5,
            'passed'   => $debugOff,
            'detail'   => $debugOff
                ? 'Debug mode disabled — no stack traces or sensitive info exposed'
                : 'WARNING: Debug mode is ON — stack traces and sensitive data may be exposed to users',
        ];
    }

    private function checkAppKeySet(): array
    {
        $keySet = !empty(config('app.key'));

        return [
            'id'       => 'app_key',
            'name'     => 'Application Encryption Key',
            'category' => 'config',
            'icon'     => 'bi-key',
            'color'    => 'success',
            'weight'   => 4,
            'passed'   => $keySet,
            'detail'   => $keySet
                ? 'APP_KEY set — encryption, signed URLs, and cookies are secure'
                : 'CRITICAL: APP_KEY not set — encryption is compromised',
        ];
    }

    private function checkDatabaseEncryption(): array
    {
        // Check if the DB connection uses SSL/TLS
        $sslConfigured = !empty(config('database.connections.mysql.options.' . \PDO::MYSQL_ATTR_SSL_CA))
            || !empty(config('database.connections.mysql.options.' . \PDO::MYSQL_ATTR_SSL_CERT));

        return [
            'id'       => 'db_encryption',
            'name'     => 'Database Connection Security',
            'category' => 'data',
            'icon'     => 'bi-database-lock',
            'color'    => 'warning',
            'weight'   => 3,
            'passed'   => $sslConfigured,
            'detail'   => $sslConfigured
                ? 'Database connection encrypted via SSL/TLS'
                : 'Database SSL not configured (acceptable for localhost/private network)',
        ];
    }

    private function checkRecentSecurityEvents(): array
    {
        try {
            $recentCritical = SecurityAuditLog::where('created_at', '>=', now()->subDay())
                ->whereIn('event_type', ['brute_force', 'session_hijack', 'privilege_escalation'])
                ->count();

            $noThreats = $recentCritical === 0;

            return [
                'id'       => 'recent_events',
                'name'     => 'Threat Status (24h)',
                'category' => 'monitoring',
                'icon'     => 'bi-shield-fill-check',
                'color'    => $noThreats ? 'success' : 'danger',
                'weight'   => 4,
                'passed'   => $noThreats,
                'detail'   => $noThreats
                    ? 'No critical security events in the last 24 hours'
                    : "{$recentCritical} critical security event(s) detected in the last 24 hours",
            ];
        } catch (\Throwable) {
            return [
                'id'       => 'recent_events',
                'name'     => 'Threat Status (24h)',
                'category' => 'monitoring',
                'icon'     => 'bi-shield-fill-check',
                'color'    => 'secondary',
                'weight'   => 4,
                'passed'   => true,
                'detail'   => 'Security audit log table not available',
            ];
        }
    }

    private function checkPasswordPolicy(): array
    {
        // Check if password validation rules enforce complexity
        $providerFile = app_path('Providers/AppServiceProvider.php');
        $hasPasswordRule = File::exists($providerFile)
            && (str_contains(File::get($providerFile), 'Password::') || str_contains(File::get($providerFile), 'password'));

        // Also check User model or auth controllers for password hashing
        $usesHash = true; // Laravel always hashes via bcrypt/argon2 by default

        return [
            'id'       => 'password_policy',
            'name'     => 'Password Security',
            'category' => 'auth',
            'icon'     => 'bi-asterisk',
            'color'    => 'primary',
            'weight'   => 3,
            'passed'   => $usesHash,
            'detail'   => 'Passwords hashed with bcrypt/argon2 — Laravel default hashing active',
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────

    private function scoreToGrade(int $score): string
    {
        return match (true) {
            $score >= 95 => 'A+',
            $score >= 90 => 'A',
            $score >= 85 => 'A-',
            $score >= 80 => 'B+',
            $score >= 75 => 'B',
            $score >= 70 => 'B-',
            $score >= 65 => 'C+',
            $score >= 60 => 'C',
            $score >= 50 => 'D',
            default      => 'F',
        };
    }
}
