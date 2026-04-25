<?php

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class PlatformIntegrationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_platform_admin_gets_404(): void
    {
        config(['auth.providers.users.model' => User::class]);
        putenv('EIAAW_PLATFORM_ADMINS=allowed@eiaawsolutions.com');

        $user = User::factory()->create([
            'work_email' => 'hr@example.com',
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/superadmin/integrations')
            ->assertStatus(404);
    }

    public function test_platform_admin_can_view_page(): void
    {
        putenv('EIAAW_PLATFORM_ADMINS=admin@eiaawsolutions.com');

        $user = User::factory()->create([
            'work_email' => 'admin@eiaawsolutions.com',
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/superadmin/integrations')
            ->assertStatus(200)
            ->assertSee('Resend')
            ->assertSee('Stripe');
    }

    public function test_secrets_are_encrypted_at_rest(): void
    {
        PlatformSetting::put('resend_api_key', 're_test_supersecret_value', true, 1);

        $row = PlatformSetting::find('resend_api_key');
        $rawColumnValue = $row->getRawOriginal('value');

        // Stored value must NOT contain the plaintext key
        $this->assertStringNotContainsString('re_test_supersecret_value', $rawColumnValue);

        // Reading via the model accessor decrypts it
        $this->assertSame('re_test_supersecret_value', PlatformSetting::get('resend_api_key'));
    }

    public function test_non_secret_values_are_not_encrypted(): void
    {
        PlatformSetting::put('mail_from_address', 'hello@eiaawsolutions.com', false, 1);

        $row = PlatformSetting::find('mail_from_address');
        $rawColumnValue = $row->getRawOriginal('value');

        $this->assertSame('hello@eiaawsolutions.com', $rawColumnValue);
    }
}
