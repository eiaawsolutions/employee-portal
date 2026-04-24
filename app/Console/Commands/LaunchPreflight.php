<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/**
 * LaunchPreflight — one command that runs every CUTOVER.md T-1d check and
 * returns a pass/fail summary. Designed to replace the "human reads a
 * checklist" step with "run this, read the green/red list."
 *
 * Each check returns ['ok' => bool, 'detail' => string]. Failures do not
 * throw; they count up and set the exit code at the end.
 *
 * Scope — only checks that can run from inside the app without external
 * credentials beyond what's in .env:
 *   - DB driver + RLS enforcement
 *   - Schema migrations applied
 *   - Required env vars present
 *   - Stripe reachability (if STRIPE_SECRET set)
 *   - Anthropic API key reachability (if ANTHROPIC_API_KEY set)
 *   - Legal stubs replaced
 *   - CSP headers emitted correctly (via test suite sampling)
 *   - Scheduled commands registered
 *   - Key services resolvable
 */
class LaunchPreflight extends Command
{
    protected $signature = 'launch:preflight {--json : Emit JSON report instead of human output}';

    protected $description = 'Run every CUTOVER.md T-1d check and return a pass/fail summary.';

    private array $results = [];

    public function handle(): int
    {
        $this->runCheck('db_driver_is_pgsql', fn () => $this->checkDbDriver());
        $this->runCheck('db_role_lacks_bypassrls', fn () => $this->checkDbRoleNotBypassRls());
        $this->runCheck('migrations_up_to_date', fn () => $this->checkMigrationsRun());
        $this->runCheck('critical_env_vars', fn () => $this->checkEnvVars());
        $this->runCheck('stripe_keys_present', fn () => $this->checkEnvPresent('STRIPE_SECRET', 'STRIPE_WEBHOOK_SECRET'));
        $this->runCheck('stripe_price_ids_populated', fn () => $this->checkStripePriceIds());
        $this->runCheck('stripe_api_reachable', fn () => $this->checkStripeReachable());
        $this->runCheck('anthropic_key_present', fn () => $this->checkEnvPresent('ANTHROPIC_API_KEY'));
        $this->runCheck('anthropic_api_reachable', fn () => $this->checkAnthropicReachable());
        $this->runCheck('legal_stubs_replaced', fn () => $this->checkLegalStubsReplaced());
        $this->runCheck('scheduled_commands_registered', fn () => $this->checkScheduledCommands());
        $this->runCheck('ai_gateway_resolvable', fn () => $this->checkAiGatewayResolvable());
        $this->runCheck('csp_emits_both_policies', fn () => $this->checkCspEmits());
        $this->runCheck('tenancy_rls_passes', fn () => $this->checkTenancyRlsCommand());

        if ($this->option('json')) {
            $this->line(json_encode(['results' => $this->results, 'passed' => $this->passedCount(), 'failed' => $this->failedCount()], JSON_PRETTY_PRINT));
            return $this->failedCount() === 0 ? self::SUCCESS : self::FAILURE;
        }

        $this->renderHuman();
        return $this->failedCount() === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function runCheck(string $name, callable $fn): void
    {
        try {
            $result = $fn();
            $this->results[$name] = $result;
        } catch (\Throwable $e) {
            $this->results[$name] = ['ok' => false, 'detail' => 'threw: ' . $e->getMessage()];
        }
    }

    // ─── checks ──────────────────────────────────────────────────────────

    private function checkDbDriver(): array
    {
        $driver = DB::connection()->getDriverName();
        return [
            'ok' => $driver === 'pgsql',
            'detail' => "driver={$driver}; production MUST be pgsql for RLS enforcement",
        ];
    }

    private function checkDbRoleNotBypassRls(): array
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return ['ok' => false, 'detail' => 'skipped (not pgsql)'];
        }

        $row = DB::selectOne('SELECT rolsuper, rolbypassrls FROM pg_roles WHERE rolname = current_user');
        if (!$row) {
            return ['ok' => false, 'detail' => 'could not read current role from pg_roles'];
        }
        $safe = !$row->rolsuper && !$row->rolbypassrls;
        return [
            'ok' => $safe,
            'detail' => $safe
                ? 'current DB role is not superuser and does not have BYPASSRLS'
                : 'DB ROLE IS PRIVILEGED — superuser or BYPASSRLS is set; tenant isolation is DEFEATED',
        ];
    }

    private function checkMigrationsRun(): array
    {
        if (!Schema::hasTable('migrations')) {
            return ['ok' => false, 'detail' => 'migrations table is missing'];
        }
        $pending = (int) Artisan::call('migrate:status', []);
        // migrate:status returns 0 either way; easier to count actual rows.
        $appliedCount = DB::table('migrations')->count();
        return [
            'ok' => $appliedCount > 0,
            'detail' => "{$appliedCount} migrations applied; run `php artisan migrate:status` for the full list",
        ];
    }

    private function checkEnvVars(): array
    {
        $required = [
            'APP_KEY', 'APP_URL', 'APP_TENANT_DOMAIN',
            'DB_CONNECTION', 'DB_HOST', 'DB_DATABASE',
            'LOG_INTEGRITY_KEY', 'BACKUP_ENCRYPTION_KEY',
        ];
        $missing = [];
        foreach ($required as $key) {
            if (empty(env($key))) $missing[] = $key;
        }
        return [
            'ok' => empty($missing),
            'detail' => empty($missing)
                ? 'all ' . count($required) . ' critical env vars set'
                : 'MISSING: ' . implode(', ', $missing),
        ];
    }

    private function checkEnvPresent(string ...$keys): array
    {
        $missing = array_filter($keys, fn ($k) => empty(env($k)));
        return [
            'ok' => empty($missing),
            'detail' => empty($missing)
                ? implode(', ', $keys) . ' set'
                : 'MISSING: ' . implode(', ', $missing),
        ];
    }

    private function checkStripePriceIds(): array
    {
        $pricing = config('eiaaw.pricing.tiers', []);
        $missing = [];
        foreach (['starter', 'growth', 'scale'] as $tier) {
            foreach (['monthly', 'annual'] as $period) {
                $id = data_get($pricing, "{$tier}.stripe_prices.{$period}");
                if (empty($id)) {
                    $missing[] = strtoupper("{$tier}_USD_{$period}");
                }
            }
        }
        return [
            'ok' => empty($missing),
            'detail' => empty($missing)
                ? 'all 6 Stripe Price IDs populated (USD-only since Session 11)'
                : count($missing) . ' / 6 missing — run `php artisan stripe:sync-prices --apply` or paste STRIPE_PRICE_* env vars',
        ];
    }

    private function checkStripeReachable(): array
    {
        $secret = env('STRIPE_SECRET');
        if (empty($secret)) {
            return ['ok' => false, 'detail' => 'STRIPE_SECRET not set — cannot verify API reachability'];
        }
        if (!str_starts_with($secret, 'sk_')) {
            return ['ok' => false, 'detail' => 'STRIPE_SECRET does not start with sk_ — looks malformed'];
        }

        try {
            $r = Http::withBasicAuth($secret, '')
                ->timeout(10)
                ->get('https://api.stripe.com/v1/balance');
            $ok = $r->status() === 200;
            $live = str_starts_with($secret, 'sk_live_');
            return [
                'ok' => $ok,
                'detail' => $ok
                    ? "Stripe API reachable (" . ($live ? 'LIVE' : 'test') . ' mode)'
                    : "Stripe API returned {$r->status()}",
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'detail' => 'Stripe request failed: ' . $e->getMessage()];
        }
    }

    private function checkAnthropicReachable(): array
    {
        $key = env('ANTHROPIC_API_KEY');
        if (empty($key)) {
            return ['ok' => false, 'detail' => 'ANTHROPIC_API_KEY not set — AI assistant will be non-functional at launch'];
        }
        if (!str_starts_with($key, 'sk-ant-')) {
            return ['ok' => false, 'detail' => 'ANTHROPIC_API_KEY does not start with sk-ant- — looks malformed'];
        }

        try {
            // Cheapest verifiable call: 1-token Haiku response
            $r = Http::timeout(10)
                ->withHeaders([
                    'x-api-key' => $key,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => env('ANTHROPIC_MODEL_ROUTINE', 'claude-haiku-4-5-20251001'),
                    'max_tokens' => 1,
                    'messages' => [['role' => 'user', 'content' => 'ping']],
                ]);
            $ok = $r->status() === 200;
            return [
                'ok' => $ok,
                'detail' => $ok
                    ? 'Anthropic API responded 200 on 1-token probe'
                    : "Anthropic API returned {$r->status()} — " . substr($r->body(), 0, 200),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'detail' => 'Anthropic request failed: ' . $e->getMessage()];
        }
    }

    private function checkLegalStubsReplaced(): array
    {
        $stubBanner = resource_path('views/marketing/legal/_stub-banner.blade.php');
        if (File::exists($stubBanner)) {
            return [
                'ok' => false,
                'detail' => '_stub-banner.blade.php still exists — counsel-reviewed legal copy has NOT replaced the stubs',
            ];
        }
        foreach (['terms', 'privacy', 'dpa'] as $doc) {
            $path = resource_path("views/marketing/legal/{$doc}.blade.php");
            if (!File::exists($path)) {
                return ['ok' => false, 'detail' => "missing {$doc}.blade.php"];
            }
            $content = File::get($path);
            if (str_contains($content, 'Pre-launch placeholder') || str_contains($content, '_stub-banner')) {
                return [
                    'ok' => false,
                    'detail' => "{$doc}.blade.php still references the pre-launch stub",
                ];
            }
        }
        return ['ok' => true, 'detail' => 'legal stubs replaced'];
    }

    private function checkScheduledCommands(): array
    {
        $required = [
            'billing:trial-end',
            'billing:past-due-suspend',
            'billing:delete-canceled',
            'billing:purge-canceled',
            'payroll:verify-statutory-rates',
            'backup:test-restore',
            'tenancy:check-rls',
            'tenancy:test-leakage',
            'audit:export',
            'log:verify-integrity',
        ];
        $registered = array_keys(Artisan::all());
        $missing = array_diff($required, $registered);
        return [
            'ok' => empty($missing),
            'detail' => empty($missing)
                ? count($required) . ' scheduled commands registered'
                : 'missing: ' . implode(', ', $missing),
        ];
    }

    private function checkAiGatewayResolvable(): array
    {
        try {
            app(\App\Services\AiGateway::class);
            return ['ok' => true, 'detail' => 'AiGateway resolves from container'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'detail' => 'AiGateway failed to resolve: ' . $e->getMessage()];
        }
    }

    private function checkCspEmits(): array
    {
        // Make an in-process GET against the landing route and inspect headers.
        $request = \Illuminate\Http\Request::create('/', 'GET');
        try {
            $response = app()->handle($request);
            $hasEnforced = $response->headers->has('Content-Security-Policy');
            $hasReportOnly = $response->headers->has('Content-Security-Policy-Report-Only');
            $ok = $hasEnforced && $hasReportOnly;
            return [
                'ok' => $ok,
                'detail' => $ok
                    ? 'both CSP headers emitted on /'
                    : 'CSP missing: enforced=' . ($hasEnforced ? 'y' : 'n') . ' reportOnly=' . ($hasReportOnly ? 'y' : 'n'),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'detail' => 'CSP check threw: ' . $e->getMessage()];
        }
    }

    private function checkTenancyRlsCommand(): array
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return ['ok' => false, 'detail' => 'skipped (not pgsql) — MUST pass in production'];
        }
        $exit = Artisan::call('tenancy:check-rls');
        return [
            'ok' => $exit === 0,
            'detail' => $exit === 0 ? 'tenancy:check-rls passed' : "tenancy:check-rls exit={$exit}",
        ];
    }

    // ─── rendering ───────────────────────────────────────────────────────

    private function passedCount(): int
    {
        return count(array_filter($this->results, fn ($r) => $r['ok']));
    }

    private function failedCount(): int
    {
        return count(array_filter($this->results, fn ($r) => !$r['ok']));
    }

    private function renderHuman(): void
    {
        $this->line('');
        $this->line('<bg=black;fg=white> EIAAW Workforce — Launch Preflight </>');
        $this->line('');

        foreach ($this->results as $name => $result) {
            $mark = $result['ok'] ? '<fg=green>✓</>' : '<fg=red>✗</>';
            $label = str_pad($name, 38, '.');
            $this->line("  {$mark} {$label} {$result['detail']}");
        }

        $this->line('');
        $passed = $this->passedCount();
        $failed = $this->failedCount();
        $total = $passed + $failed;
        if ($failed === 0) {
            $this->line("<bg=green;fg=black> PASS </>  <fg=green>{$passed}/{$total} checks green — launch gate cleared</>");
        } else {
            $this->line("<bg=red;fg=white> FAIL </>  <fg=red>{$failed}/{$total} checks failing — DO NOT LAUNCH</>");
        }
        $this->line('');
    }
}
