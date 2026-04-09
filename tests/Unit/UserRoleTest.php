<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    // ── HR role checks ────────────────────────────────────────────────────

    public function test_hr_manager_is_hr(): void
    {
        $user = User::factory()->hrManager()->create();
        $this->assertTrue($user->isHr());
        $this->assertTrue($user->isHrManager());
        $this->assertFalse($user->isIt());
    }

    public function test_hr_executive_is_hr(): void
    {
        $user = User::factory()->hrExecutive()->create();
        $this->assertTrue($user->isHr());
        $this->assertTrue($user->isHrExecutive());
    }

    public function test_hr_intern_is_hr(): void
    {
        $user = User::factory()->hrIntern()->create();
        $this->assertTrue($user->isHr());
        $this->assertTrue($user->isHrIntern());
    }

    // ── IT role checks ────────────────────────────────────────────────────

    public function test_it_manager_is_it(): void
    {
        $user = User::factory()->itManager()->create();
        $this->assertTrue($user->isIt());
        $this->assertTrue($user->isItManager());
        $this->assertFalse($user->isHr());
    }

    public function test_it_executive_is_it(): void
    {
        $user = User::factory()->itExecutive()->create();
        $this->assertTrue($user->isIt());
    }

    // ── Admin role checks ─────────────────────────────────────────────────

    public function test_superadmin_role(): void
    {
        $user = User::factory()->superadmin()->create();
        $this->assertTrue($user->isSuperadmin());
        $this->assertFalse($user->isHr());
        $this->assertFalse($user->isIt());
    }

    public function test_system_admin_role(): void
    {
        $user = User::factory()->systemAdmin()->create();
        $this->assertTrue($user->isSystemAdmin());
    }

    // ── Onboarding permissions ────────────────────────────────────────────

    public function test_hr_manager_can_view_and_edit_onboarding(): void
    {
        $user = User::factory()->hrManager()->create();
        $this->assertTrue($user->canViewOnboarding());
        $this->assertTrue($user->canEditOnboarding());
        $this->assertTrue($user->canAddOnboarding());
    }

    public function test_hr_executive_can_view_but_not_edit_onboarding(): void
    {
        $user = User::factory()->hrExecutive()->create();
        $this->assertTrue($user->canViewOnboarding());
        $this->assertFalse($user->canEditOnboarding());
        $this->assertFalse($user->canAddOnboarding());
    }

    public function test_hr_intern_can_view_but_not_edit_onboarding(): void
    {
        $user = User::factory()->hrIntern()->create();
        $this->assertTrue($user->canViewOnboarding());
        $this->assertFalse($user->canEditOnboarding());
    }

    public function test_it_user_cannot_view_onboarding(): void
    {
        $user = User::factory()->itManager()->create();
        $this->assertFalse($user->canViewOnboarding());
    }

    public function test_employee_cannot_view_onboarding(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $this->assertFalse($user->canViewOnboarding());
    }

    // ── Asset permissions ─────────────────────────────────────────────────

    public function test_it_manager_can_view_and_edit_assets(): void
    {
        $user = User::factory()->itManager()->create();
        $this->assertTrue($user->canViewAssets());
        $this->assertTrue($user->canEditAsset());
        $this->assertTrue($user->canAddAsset());
    }

    public function test_hr_manager_can_view_and_edit_assets(): void
    {
        $user = User::factory()->hrManager()->create();
        $this->assertTrue($user->canViewAssets());
        $this->assertTrue($user->canEditAsset());
    }

    public function test_employee_cannot_view_assets(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $this->assertFalse($user->canViewAssets());
    }

    // ── Leave permissions ─────────────────────────────────────────────────

    public function test_hr_manager_can_manage_leave(): void
    {
        $user = User::factory()->hrManager()->create();
        $this->assertTrue($user->canViewLeaveAdmin());
        $this->assertTrue($user->canManageLeave());
    }

    public function test_hr_executive_can_view_but_not_manage_leave(): void
    {
        $user = User::factory()->hrExecutive()->create();
        $this->assertTrue($user->canViewLeaveAdmin());
        $this->assertFalse($user->canManageLeave());
    }

    // ── Payroll permissions ───────────────────────────────────────────────

    public function test_hr_manager_can_manage_payroll(): void
    {
        $user = User::factory()->hrManager()->create();
        $this->assertTrue($user->canViewPayroll());
        $this->assertTrue($user->canManagePayroll());
        $this->assertTrue($user->canApprovePayRun());
    }

    public function test_superadmin_can_approve_payrun(): void
    {
        $user = User::factory()->superadmin()->create();
        $this->assertTrue($user->canApprovePayRun());
    }

    public function test_hr_executive_cannot_approve_payrun(): void
    {
        $user = User::factory()->hrExecutive()->create();
        $this->assertFalse($user->canApprovePayRun());
    }

    // ── Claims permissions ────────────────────────────────────────────────

    public function test_hr_manager_can_manage_claims(): void
    {
        $user = User::factory()->hrManager()->create();
        $this->assertTrue($user->canViewAllClaims());
        $this->assertTrue($user->canManageClaims());
    }

    // ── Accounting permissions ────────────────────────────────────────────

    public function test_finance_manager_can_manage_accounting(): void
    {
        $user = User::factory()->financeManager()->create();
        $this->assertTrue($user->canViewAccounting());
        $this->assertTrue($user->canManageAccounting());
        $this->assertTrue($user->canApproveTransactions());
        $this->assertTrue($user->canUseAiChat());
    }

    public function test_employee_cannot_access_accounting(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $this->assertFalse($user->canViewAccounting());
        $this->assertFalse($user->canManageAccounting());
    }

    // ── isHrOrIt compound check ───────────────────────────────────────────

    public function test_is_hr_or_it_covers_all_admin_roles(): void
    {
        $this->assertTrue(User::factory()->hrManager()->create()->isHrOrIt());
        $this->assertTrue(User::factory()->itManager()->create()->isHrOrIt());
        $this->assertTrue(User::factory()->superadmin()->create()->isHrOrIt());
        $this->assertTrue(User::factory()->systemAdmin()->create()->isHrOrIt());
        $this->assertFalse(User::factory()->create(['role' => 'employee'])->isHrOrIt());
    }
}
