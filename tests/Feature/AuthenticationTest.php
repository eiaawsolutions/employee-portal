<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_loads(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('Employee Portal');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['work_email' => 'test@claritas.asia']);

        $response = $this->post(route('login'), [
            'work_email' => 'test@claritas.asia',
            'password'   => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        User::factory()->create(['work_email' => 'test@claritas.asia']);

        $response = $this->post(route('login'), [
            'work_email' => 'test@claritas.asia',
            'password'   => 'wrongpassword',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('work_email');
    }

    public function test_user_cannot_login_with_nonexistent_email(): void
    {
        $response = $this->post(route('login'), [
            'work_email' => 'nonexistent@claritas.asia',
            'password'   => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('work_email');
    }

    public function test_deactivated_user_cannot_login(): void
    {
        User::factory()->inactive()->create(['work_email' => 'inactive@claritas.asia']);

        $response = $this->post(route('login'), [
            'work_email' => 'inactive@claritas.asia',
            'password'   => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('work_email');
    }

    public function test_account_locks_after_five_failed_attempts(): void
    {
        $user = User::factory()->create(['work_email' => 'lockme@claritas.asia']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'work_email' => 'lockme@claritas.asia',
                'password'   => 'wrongpassword',
            ]);
        }

        $user->refresh();
        $this->assertFalse($user->is_active);
        $this->assertEquals('login_lockout', $user->deactivation_reason);
    }

    public function test_successful_login_resets_failed_attempts(): void
    {
        $user = User::factory()->create([
            'work_email'     => 'test@claritas.asia',
            'login_attempts' => 3,
        ]);

        $this->post(route('login'), [
            'work_email' => 'test@claritas.asia',
            'password'   => 'password123',
        ]);

        $user->refresh();
        $this->assertEquals(0, $user->login_attempts);
    }

    public function test_user_with_past_exit_date_is_deactivated_on_login(): void
    {
        $user = User::factory()->create(['work_email' => 'exited@claritas.asia']);
        Employee::factory()->withUser($user)->create([
            'company_email' => 'exited@claritas.asia',
            'exit_date'     => now()->subDay(),
        ]);

        $this->post(route('login'), [
            'work_email' => 'exited@claritas.asia',
            'password'   => 'password123',
        ]);

        $user->refresh();
        $this->assertFalse($user->is_active);
        $this->assertEquals('exit_date', $user->deactivation_reason);
    }

    public function test_single_session_token_is_set_on_login(): void
    {
        $user = User::factory()->create(['work_email' => 'test@claritas.asia']);

        $this->post(route('login'), [
            'work_email' => 'test@claritas.asia',
            'password'   => 'password123',
        ]);

        $user->refresh();
        $this->assertNotNull($user->session_token);
        $this->assertEquals(60, strlen($user->session_token));
    }

    public function test_logout_clears_session_token(): void
    {
        $user = User::factory()->create(['session_token' => 'test-token']);
        $this->actingAs($user);

        $this->post(route('logout'));

        $user->refresh();
        $this->assertNull($user->session_token);
        $this->assertGuest();
    }

    public function test_hr_user_redirected_to_hr_dashboard(): void
    {
        $user = User::factory()->hrManager()->create();

        $response = $this->post(route('login'), [
            'work_email' => $user->work_email,
            'password'   => 'password123',
        ]);

        $response->assertRedirect(route('hr.dashboard'));
    }

    public function test_it_user_redirected_to_it_dashboard(): void
    {
        $user = User::factory()->itManager()->create();

        $response = $this->post(route('login'), [
            'work_email' => $user->work_email,
            'password'   => 'password123',
        ]);

        $response->assertRedirect(route('it.dashboard'));
    }

    public function test_employee_redirected_to_user_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->post(route('login'), [
            'work_email' => $user->work_email,
            'password'   => 'password123',
        ]);

        $response->assertRedirect(route('user.dashboard'));
    }

    public function test_generic_error_message_prevents_user_enumeration(): void
    {
        $genericError = 'The provided credentials do not match our records.';

        // Non-existent user
        $response1 = $this->post(route('login'), [
            'work_email' => 'nobody@claritas.asia',
            'password'   => 'wrongpassword',
        ]);

        // Existing user, wrong password
        User::factory()->create(['work_email' => 'real@claritas.asia']);
        $response2 = $this->post(route('login'), [
            'work_email' => 'real@claritas.asia',
            'password'   => 'wrongpassword',
        ]);

        $this->assertEquals(
            $response1->getSession()->get('errors')->get('work_email'),
            $response2->getSession()->get('errors')->get('work_email')
        );
    }

    public function test_forgot_password_returns_generic_message(): void
    {
        $response = $this->post(route('password.email'), [
            'email' => 'nonexistent@claritas.asia',
        ]);

        $response->assertSessionHas('status');
        $this->assertStringContainsString('If an account exists', session('status'));
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/profile');
        $response->assertRedirect(route('login'));
    }
}
