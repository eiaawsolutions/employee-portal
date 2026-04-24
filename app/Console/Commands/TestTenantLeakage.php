<?php

namespace App\Console\Commands;

use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\Customer;
use App\Models\Accounting\SalesInvoice;
use App\Models\AssetInventory;
use App\Models\Employee;
use App\Models\EmployeeEmergencyContact;
use App\Models\EmployeeSpouseDetail;
use App\Models\ExpenseClaim;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Payslip;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * tenancy:test-leakage — proves the multi-tenant isolation works end-to-end.
 *
 * Creates two tenants (acme + bigcorp), populates each with a user + employee +
 * spouse detail + emergency contact, then exercises every query path that
 * could leak across tenants:
 *
 *   1. App-layer scope: Employee::all() inside tenant context returns only
 *      that tenant's rows.
 *   2. Postgres RLS: raw DB::table('employees')->get() inside tenant context
 *      returns only that tenant's rows (i.e. RLS works even if the global
 *      scope is bypassed).
 *   3. Auth: a user with email collision between two tenants resolves to the
 *      correct one via the tenant-scoped lookup.
 *   4. Cross-tenant write attempt: trying to update tenant B's employee while
 *      acting as tenant A is rejected by RLS.
 *
 * Exits 0 if every assertion passes, 1 if any fail. Output is structured so
 * CI can parse it.
 *
 * Postgres-only — RLS is the thing being tested. Skips with a notice on MySQL.
 */
class TestTenantLeakage extends Command
{
    protected $signature   = 'tenancy:test-leakage {--cleanup : Drop the test tenants after running}';
    protected $description = 'End-to-end test: prove cross-tenant data is isolated by app scope + Postgres RLS';

    private array $passes = [];
    private array $fails = [];

    public function handle(): int
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->warn('SKIPPED — RLS isolation test requires Postgres (current driver: ' . DB::connection()->getDriverName() . ').');
            return 0;
        }

        $this->info('Setting up two test tenants…');

        $tenantA = $this->makeOrFindTenant('test-tenant-a', 'Acme Sdn Bhd');
        $tenantB = $this->makeOrFindTenant('test-tenant-b', 'BigCorp Sdn Bhd');
        $this->line("  Tenant A: id={$tenantA->id} slug={$tenantA->slug}");
        $this->line("  Tenant B: id={$tenantB->id} slug={$tenantB->slug}");

        // ── Seed identical-shape data in each tenant, including a deliberate ──
        //    email collision (same work_email in both tenants) to prove the
        //    composite unique constraint allows it AND auth resolves correctly.
        $userA  = $this->seedUserAndEmployee($tenantA, 'Alice Acme',  'admin@example.com');
        $userB  = $this->seedUserAndEmployee($tenantB, 'Bob BigCorp', 'admin@example.com');
        $this->line("  Seeded user/employee in each tenant (with intentional email collision).");

        // ── Run each test in turn ────────────────────────────────────────────
        $this->testGlobalScopeIsolation($tenantA, $tenantB);
        $this->testPostgresRlsIsolation($tenantA, $tenantB);
        $this->testEmailCollisionResolvesCorrectly($tenantA, $tenantB);
        $this->testCrossTenantWriteIsRejected($tenantA, $tenantB);
        $this->testEmptyTenantSeesNothing();
        $this->testChildTableScoping($tenantA, $tenantB);
        $this->testLeaveModuleScoping($tenantA, $tenantB);
        $this->testAssetsModuleScoping($tenantA, $tenantB);
        $this->testAccountingModuleScoping($tenantA, $tenantB);
        $this->testRawSqlAcrossModules($tenantA, $tenantB);

        $this->newLine();
        $this->info('── Results ─────────────────────────────────');
        foreach ($this->passes as $p) {
            $this->line("  <fg=green>✓</> {$p}");
        }
        foreach ($this->fails as $f) {
            $this->line("  <fg=red>✗</> {$f}");
        }
        $this->newLine();
        $this->info(count($this->passes) . ' passed, ' . count($this->fails) . ' failed.');

        if ($this->option('cleanup')) {
            $this->info('Cleaning up test tenants…');
            $this->cleanup($tenantA, $tenantB);
        } else {
            $this->comment("Test tenants left in place (id={$tenantA->id},{$tenantB->id}). Pass --cleanup to remove.");
        }

        return count($this->fails) === 0 ? 0 : 1;
    }

    private function makeOrFindTenant(string $slug, string $name): Tenant
    {
        // No tenant context — this is platform-level data setup.
        return TenantContext::asNone(function () use ($slug, $name) {
            return Tenant::withoutGlobalScope(TenantScope::class)
                ->firstOrCreate(['slug' => $slug], [
                    'name' => $name,
                    'plan' => 'growth',
                    'plan_seats' => 5,
                    'status' => Tenant::STATUS_ACTIVE,
                    'country_code' => 'MY',
                    'billing_currency' => 'MYR',
                ]);
        });
    }

    private function seedUserAndEmployee(Tenant $tenant, string $name, string $email): User
    {
        return TenantContext::run($tenant, function () use ($name, $email) {
            $user = User::create([
                // tenant_id auto-filled by trait
                'name'        => $name,
                'work_email'  => $email,
                'password'    => Hash::make('test-password'),
                'role'        => 'hr_manager',
                'is_active'   => true,
                'login_attempts' => 0,
            ]);

            $employee = Employee::create([
                'user_id'     => $user->id,
                'full_name'   => $name,
                'active_from' => now()->toDateString(),
            ]);

            EmployeeSpouseDetail::create([
                'employee_id' => $employee->id,
                'name'        => "Spouse of {$name}",
            ]);

            EmployeeEmergencyContact::create([
                'employee_id'   => $employee->id,
                'contact_order' => 1,
                'name'          => "Contact of {$name}",
                'tel_no'        => '+60123456789',
                'relationship'  => 'parent',
            ]);

            // ── Leave row (tests Leave module retrofit) ─────────────────────
            // NOTE: leave_types.code is globally unique in the legacy Claritas schema.
            // Real SaaS deploys need (tenant_id, code) composite — flagged in Session 5
            // schema-uniqueness-audit backlog. For now we use a per-tenant unique code.
            $leaveType = LeaveType::create([
                'name' => "Annual Leave ({$name})",
                'code' => 'AL-' . substr(md5($name), 0, 4),
                'is_paid' => true,
                'is_active' => true,
                'sort_order' => 1,
            ]);
            LeaveApplication::create([
                'employee_id'   => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date'    => now()->addDays(7)->toDateString(),
                'end_date'      => now()->addDays(8)->toDateString(),
                'total_days'    => 2,
                'status'        => 'pending',
                'reason'        => "Leave for {$name}",
            ]);

            // ── Asset inventory row (tests Assets module retrofit) ─────────
            AssetInventory::create([
                'asset_tag'   => "TAG-{$name}",
                'asset_type'  => 'laptop',
                'brand'       => 'TestBrand',
                'model'       => 'TestModel',
                'serial_no'   => "SN-{$name}",
                'status'      => 'available',
            ]);

            // ── Accounting Customer + Sales Invoice (tests Accounting retrofit) ─
            $coa = ChartOfAccount::create([
                'company'        => $name,
                'account_code'   => '1000-' . substr(md5($name), 0, 4),
                'name'           => "Accounts Receivable - {$name}",
                'type'           => 'asset',
                'normal_balance' => 'debit',
                'currency'       => 'MYR',
                'is_active'      => true,
            ]);
            $customer = Customer::create([
                'company'    => $name,
                'customer_code' => 'CUST-' . substr(md5($name), 0, 4),
                'name'       => "Customer of {$name}",
                'email'      => "customer-{$name}@example.com",
                'currency'   => 'MYR',
                'is_active'  => true,
            ]);
            SalesInvoice::create([
                'company'        => $name,
                'invoice_number' => 'INV-' . substr(md5($name), 0, 6),
                'customer_id'    => $customer->id,
                'date'           => now()->toDateString(),
                'due_date'       => now()->addDays(30)->toDateString(),
                'subtotal'       => 1000,
                'tax_total'      => 60,
                'total'          => 1060,
                'balance_due'    => 1060,
                'status'         => 'sent',
                'currency'       => 'MYR',
                'created_by'     => $user->id,
            ]);

            return $user;
        });
    }

    private function testGlobalScopeIsolation(Tenant $a, Tenant $b): void
    {
        TenantContext::run($a, function () {
            $emails = Employee::pluck('full_name')->all();
            if (count($emails) >= 1 && !in_array('Bob BigCorp', $emails, true) && in_array('Alice Acme', $emails, true)) {
                $this->passes[] = 'Eloquent global scope: Employee::all() returns only tenant A rows';
            } else {
                $this->fails[] = 'Eloquent global scope LEAKED: Employee::all() returned ' . json_encode($emails);
            }
        });

        TenantContext::run($b, function () {
            $emails = Employee::pluck('full_name')->all();
            if (count($emails) >= 1 && !in_array('Alice Acme', $emails, true) && in_array('Bob BigCorp', $emails, true)) {
                $this->passes[] = 'Eloquent global scope: Employee::all() returns only tenant B rows';
            } else {
                $this->fails[] = 'Eloquent global scope LEAKED: Employee::all() returned ' . json_encode($emails);
            }
        });
    }

    private function testPostgresRlsIsolation(Tenant $a, Tenant $b): void
    {
        // Use raw query builder — bypasses Eloquent global scope. RLS is the
        // ONLY layer that should still constrain the result here.
        TenantContext::run($a, function () {
            $names = DB::table('employees')->pluck('full_name')->all();
            if (count($names) >= 1 && !in_array('Bob BigCorp', $names, true) && in_array('Alice Acme', $names, true)) {
                $this->passes[] = 'Postgres RLS: DB::table("employees") (no scope) returns only tenant A rows';
            } else {
                $this->fails[] = 'Postgres RLS LEAKED: DB::table("employees") returned ' . json_encode($names);
            }
        });

        TenantContext::run($b, function () {
            $names = DB::table('employees')->pluck('full_name')->all();
            if (count($names) >= 1 && !in_array('Alice Acme', $names, true) && in_array('Bob BigCorp', $names, true)) {
                $this->passes[] = 'Postgres RLS: DB::table("employees") (no scope) returns only tenant B rows';
            } else {
                $this->fails[] = 'Postgres RLS LEAKED: DB::table("employees") returned ' . json_encode($names);
            }
        });
    }

    private function testEmailCollisionResolvesCorrectly(Tenant $a, Tenant $b): void
    {
        $aliceFromA = TenantContext::run($a, fn () => User::where('work_email', 'admin@example.com')->first());
        $bobFromB   = TenantContext::run($b, fn () => User::where('work_email', 'admin@example.com')->first());

        if ($aliceFromA && $aliceFromA->name === 'Alice Acme') {
            $this->passes[] = 'Email collision: tenant A resolves admin@example.com → Alice Acme';
        } else {
            $this->fails[] = 'Email collision FAILED for tenant A: got ' . ($aliceFromA?->name ?? 'null');
        }

        if ($bobFromB && $bobFromB->name === 'Bob BigCorp') {
            $this->passes[] = 'Email collision: tenant B resolves admin@example.com → Bob BigCorp';
        } else {
            $this->fails[] = 'Email collision FAILED for tenant B: got ' . ($bobFromB?->name ?? 'null');
        }
    }

    private function testCrossTenantWriteIsRejected(Tenant $a, Tenant $b): void
    {
        // Get the id of tenant B's employee while in tenant B context
        $bobEmployeeId = TenantContext::run($b, fn () => Employee::where('full_name', 'Bob BigCorp')->value('id'));

        // Now switch to tenant A and try to update tenant B's row by raw id.
        // RLS should reject the UPDATE (zero rows affected, no exception in
        // current Postgres unless we set FORCE — but FORCE is set so the row
        // simply isn't visible to UPDATE).
        TenantContext::run($a, function () use ($bobEmployeeId) {
            $rowsAffected = DB::table('employees')
                ->where('id', $bobEmployeeId)
                ->update(['full_name' => 'Hacked by tenant A']);

            if ($rowsAffected === 0) {
                $this->passes[] = 'Cross-tenant write blocked: UPDATE on tenant B row from tenant A context affected 0 rows';
            } else {
                $this->fails[] = "Cross-tenant write LEAKED: UPDATE affected {$rowsAffected} rows";
            }
        });

        // Verify tenant B's name is unchanged
        $bobNameAfter = TenantContext::run($b, fn () => Employee::where('id', $bobEmployeeId)->value('full_name'));
        if ($bobNameAfter === 'Bob BigCorp') {
            $this->passes[] = 'Cross-tenant write blocked: tenant B row unchanged after attack attempt';
        } else {
            $this->fails[] = "Cross-tenant write LEAKED: tenant B row now reads '{$bobNameAfter}'";
        }
    }

    private function testEmptyTenantSeesNothing(): void
    {
        $empty = $this->makeOrFindTenant('test-tenant-empty', 'Empty Test');

        TenantContext::run($empty, function () {
            $count = Employee::count();
            $rawCount = DB::table('employees')->count();
            if ($count === 0 && $rawCount === 0) {
                $this->passes[] = 'Empty tenant sees zero rows from both Eloquent and raw queries';
            } else {
                $this->fails[] = "Empty tenant LEAKED: Eloquent count={$count}, raw count={$rawCount}";
            }
        });

        // Cleanup the empty test tenant immediately
        TenantContext::asNone(function () use ($empty) {
            Tenant::withoutGlobalScope(TenantScope::class)->where('id', $empty->id)->forceDelete();
        });
    }

    private function testChildTableScoping(Tenant $a, Tenant $b): void
    {
        TenantContext::run($a, function () {
            $spouseNames = EmployeeSpouseDetail::pluck('name')->all();
            if (count($spouseNames) === 1 && $spouseNames[0] === 'Spouse of Alice Acme') {
                $this->passes[] = 'Child table (employee_spouse_details): tenant A sees only its own rows';
            } else {
                $this->fails[] = 'Child table LEAKED: tenant A spouse rows = ' . json_encode($spouseNames);
            }

            $emergencyNames = EmployeeEmergencyContact::pluck('name')->all();
            if (count($emergencyNames) === 1 && $emergencyNames[0] === 'Contact of Alice Acme') {
                $this->passes[] = 'Child table (employee_emergency_contacts): tenant A sees only its own rows';
            } else {
                $this->fails[] = 'Child table LEAKED: tenant A emergency rows = ' . json_encode($emergencyNames);
            }
        });
    }

    private function testLeaveModuleScoping(Tenant $a, Tenant $b): void
    {
        TenantContext::run($a, function () {
            $reasons = LeaveApplication::pluck('reason')->all();
            $hasOnlyAlice = count($reasons) === 1 && str_contains($reasons[0], 'Alice');
            if ($hasOnlyAlice) {
                $this->passes[] = 'Leave module: tenant A sees only its own LeaveApplication rows';
            } else {
                $this->fails[] = 'Leave module LEAKED: tenant A reasons = ' . json_encode($reasons);
            }
        });
        TenantContext::run($b, function () {
            $reasons = LeaveApplication::pluck('reason')->all();
            $hasOnlyBob = count($reasons) === 1 && str_contains($reasons[0], 'Bob');
            if ($hasOnlyBob) {
                $this->passes[] = 'Leave module: tenant B sees only its own LeaveApplication rows';
            } else {
                $this->fails[] = 'Leave module LEAKED: tenant B reasons = ' . json_encode($reasons);
            }
        });
    }

    private function testAssetsModuleScoping(Tenant $a, Tenant $b): void
    {
        TenantContext::run($a, function () {
            $tags = AssetInventory::pluck('asset_tag')->all();
            if (count($tags) === 1 && $tags[0] === 'TAG-Alice Acme') {
                $this->passes[] = 'Assets module: tenant A sees only its own AssetInventory rows';
            } else {
                $this->fails[] = 'Assets module LEAKED: tenant A tags = ' . json_encode($tags);
            }
        });
        TenantContext::run($b, function () {
            $tags = AssetInventory::pluck('asset_tag')->all();
            if (count($tags) === 1 && $tags[0] === 'TAG-Bob BigCorp') {
                $this->passes[] = 'Assets module: tenant B sees only its own AssetInventory rows';
            } else {
                $this->fails[] = 'Assets module LEAKED: tenant B tags = ' . json_encode($tags);
            }
        });
    }

    private function testAccountingModuleScoping(Tenant $a, Tenant $b): void
    {
        TenantContext::run($a, function () {
            $customers = Customer::pluck('name')->all();
            $invoices  = SalesInvoice::pluck('invoice_number')->all();
            if (count($customers) === 1 && str_contains($customers[0], 'Alice') && count($invoices) === 1) {
                $this->passes[] = 'Accounting module: tenant A sees only its own Customer + SalesInvoice rows';
            } else {
                $this->fails[] = 'Accounting module LEAKED: tenant A customers=' . json_encode($customers) . ' invoices=' . json_encode($invoices);
            }
        });
        TenantContext::run($b, function () {
            $customers = Customer::pluck('name')->all();
            if (count($customers) === 1 && str_contains($customers[0], 'Bob')) {
                $this->passes[] = 'Accounting module: tenant B sees only its own Customer rows';
            } else {
                $this->fails[] = 'Accounting module LEAKED: tenant B customers = ' . json_encode($customers);
            }
        });
    }

    /**
     * Spot-check 5 retrofitted tables via raw SQL (bypasses Eloquent global scope).
     * If RLS is enforced, each query returns only the current tenant's row.
     */
    private function testRawSqlAcrossModules(Tenant $a, Tenant $b): void
    {
        $tablesToProbe = [
            'leave_applications',
            'asset_inventories',
            'acc_customers',
            'acc_sales_invoices',
            'employee_emergency_contacts',
        ];

        foreach ($tablesToProbe as $table) {
            $countA = TenantContext::run($a, fn () => DB::table($table)->count());
            $countB = TenantContext::run($b, fn () => DB::table($table)->count());

            if ($countA === 1 && $countB === 1) {
                $this->passes[] = "Raw SQL RLS isolation: {$table} returns 1 row per tenant context";
            } else {
                $this->fails[] = "Raw SQL RLS LEAKED on {$table}: tenant A count={$countA}, tenant B count={$countB} (expected 1 each)";
            }
        }
    }

    private function cleanup(Tenant $a, Tenant $b): void
    {
        TenantContext::asNone(function () use ($a, $b) {
            // Cascade: deleting the tenant cascades through tenant_id FK on every table.
            Tenant::withoutGlobalScope(TenantScope::class)
                ->whereIn('id', [$a->id, $b->id])
                ->forceDelete();
        });
    }
}
