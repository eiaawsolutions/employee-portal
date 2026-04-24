<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * SeedLoadTest — seeds tenants + users for k6 smoke runs.
 *
 *  php artisan tenant:seed-load-test --tenants=100 --users-per-tenant=10
 *
 * Creates tenants with slug "loadtest-NNN" (idempotent — reuses existing slugs),
 * each with --users-per-tenant users at role=employee. Writes
 * docs/load-test/tenants.json with the credentials k6 reads.
 *
 * Password is a fixed value so the k6 script can just read accounts and
 * stream them in. NEVER RUN THIS AGAINST PRODUCTION — guard via APP_ENV
 * check; we refuse unless APP_ENV is 'staging' or 'local'.
 */
class SeedLoadTest extends Command
{
    protected $signature = 'tenant:seed-load-test
        {--tenants=100 : Number of tenants to create}
        {--users-per-tenant=10 : Users per tenant}
        {--password=LoadTest#2026 : Shared password for all seeded users}
        {--output=docs/load-test/tenants.json : Where to write the credentials JSON}
        {--force : Override the staging/local env guard}';

    protected $description = 'Seed synthetic tenants + users for k6 load testing. Refuses to run in production.';

    public function handle(): int
    {
        $env = app()->environment();
        if (!in_array($env, ['local', 'staging', 'testing'], true) && !$this->option('force')) {
            $this->error("Refusing to seed in env={$env}. Pass --force to override.");
            return self::FAILURE;
        }

        $numTenants = (int) $this->option('tenants');
        $usersPerTenant = (int) $this->option('users-per-tenant');
        $password = (string) $this->option('password');
        $hashedPassword = Hash::make($password);

        $credentials = [];
        $this->info("Seeding {$numTenants} tenants × {$usersPerTenant} users in env={$env}");

        $bar = $this->output->createProgressBar($numTenants);
        $bar->start();

        for ($t = 1; $t <= $numTenants; $t++) {
            $slug = sprintf('loadtest-%03d', $t);

            $tenant = Tenant::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => "Load Test {$t}",
                    'legal_name' => "Load Test Corp {$t} Sdn. Bhd.",
                    'country_code' => 'MY',
                    'billing_currency' => 'USD',
                    'plan' => Tenant::PLAN_GROWTH,
                    'plan_seats' => $usersPerTenant,
                    'trial_ends_at' => now()->addDays(14),
                    'status' => Tenant::STATUS_ACTIVE,
                ]
            );

            DB::transaction(function () use ($tenant, $usersPerTenant, $hashedPassword, $password, $slug, &$credentials) {
                for ($u = 1; $u <= $usersPerTenant; $u++) {
                    $email = sprintf('u%02d@%s.test', $u, $slug);
                    $user = User::firstOrCreate(
                        ['work_email' => $email, 'tenant_id' => $tenant->id],
                        [
                            'name' => "Load User {$u} / {$slug}",
                            'password' => $hashedPassword,
                            'role' => 'employee',
                            'is_active' => true,
                        ]
                    );

                    // Attach to tenant pivot if not already
                    if (!$tenant->users()->where('users.id', $user->id)->exists()) {
                        $tenant->users()->attach($user->id, [
                            'tenant_role' => $u === 1 ? 'owner' : 'member',
                            'joined_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    $credentials[] = [
                        'tenant_slug' => $slug,
                        'work_email' => $email,
                        'password' => $password,
                    ];
                }
            });

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Write credentials JSON
        $outputPath = base_path($this->option('output'));
        @mkdir(dirname($outputPath), 0755, true);
        file_put_contents($outputPath, json_encode($credentials, JSON_PRETTY_PRINT));

        $this->info(sprintf(
            'Done. %d tenants × %d users = %d accounts written to %s',
            $numTenants, $usersPerTenant, count($credentials), $outputPath
        ));

        $this->line('Run the smoke load test:');
        $this->line("  k6 run -e BASE_HOST={$this->guessHost()} -e VUS=1000 docs/load-test/workforce-smoke.js");

        return self::SUCCESS;
    }

    private function guessHost(): string
    {
        return config('eiaaw.tenant_domain', 'ep.eiaawsolutions.com');
    }
}
