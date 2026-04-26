<?php

namespace Tests\Feature;

use App\Models\SignupInvite;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regression: confirming a signup must redirect the user to their new
 * workspace login. The earlier bug self-collided on the invite's own
 * desired_slug row inside Tenant::isSlugAvailable() at provisioning time,
 * bouncing the user back to the signup form with "URL not available".
 */
class SignupConfirmFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_confirm_provisions_tenant_and_redirects_to_workspace(): void
    {
        $invite = SignupInvite::create([
            'work_email'         => 'amos@example.com',
            'full_name'          => 'Amos Wafula',
            'company_name'       => 'EIAAW Solutions',
            'desired_slug'       => 'uat',
            'plan'               => 'scale',
            'confirmation_token' => Str::random(48),
            'expires_at'         => now()->addDay(),
        ]);

        $response = $this->post(route('signup.confirm.submit', $invite->confirmation_token), [
            'password'              => 'a-strong-password-123',
            'password_confirmation' => 'a-strong-password-123',
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('uat.', $response->headers->get('Location'));
        $this->assertStringContainsString('/login', $response->headers->get('Location'));

        $this->assertDatabaseHas('tenants', ['slug' => 'uat']);
        $this->assertNotNull($invite->fresh()->confirmed_at);
    }

    public function test_isSlugAvailable_excludes_specified_invite(): void
    {
        $invite = SignupInvite::create([
            'work_email'         => 'b@example.com',
            'full_name'          => 'B',
            'company_name'       => 'B Co',
            'desired_slug'       => 'mineslug',
            'plan'               => 'starter',
            'confirmation_token' => Str::random(48),
            'expires_at'         => now()->addDay(),
        ]);

        $this->assertFalse(Tenant::isSlugAvailable('mineslug'));
        $this->assertTrue(Tenant::isSlugAvailable('mineslug', $invite->id));
    }
}
