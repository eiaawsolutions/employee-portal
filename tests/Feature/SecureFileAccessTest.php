<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecureFileAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_unauthenticated_user_cannot_access_files(): void
    {
        $response = $this->get('/secure-file/nric_documents/test.pdf');
        $response->assertRedirect(route('login'));
    }

    public function test_hr_manager_can_access_nric_documents(): void
    {
        $user = User::factory()->hrManager()->withTwoFactor()->create();
        Storage::disk('local')->put('nric_documents/test.pdf', 'fake-pdf-content');

        $response = $this->actingAs($user)->get('/secure-file/nric_documents/test.pdf');
        $response->assertStatus(200);
    }

    public function test_employee_cannot_access_other_nric_documents(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        Employee::factory()->withUser($user)->create();
        Storage::disk('local')->put('nric_documents/other-person.pdf', 'fake-pdf-content');

        $response = $this->actingAs($user)->get('/secure-file/nric_documents/other-person.pdf');
        $response->assertStatus(403);
    }

    public function test_employee_can_access_own_nric_file(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        Employee::factory()->withUser($user)->create([
            'nric_file_paths' => ['nric_documents/my-nric.pdf'],
        ]);
        Storage::disk('local')->put('nric_documents/my-nric.pdf', 'fake-pdf-content');

        $response = $this->actingAs($user)->get('/secure-file/nric_documents/my-nric.pdf');
        $response->assertStatus(200);
    }

    public function test_it_manager_can_access_aarf_files(): void
    {
        $user = User::factory()->itManager()->withTwoFactor()->create();
        Storage::disk('local')->put('aarfs/form.pdf', 'fake-pdf-content');

        $response = $this->actingAs($user)->get('/secure-file/aarfs/form.pdf');
        $response->assertStatus(200);
    }

    public function test_it_intern_cannot_access_contracts(): void
    {
        $user = User::factory()->itIntern()->create();
        Storage::disk('local')->put('employee_contracts/contract.pdf', 'fake-content');

        $response = $this->actingAs($user)->get('/secure-file/employee_contracts/contract.pdf');
        $response->assertStatus(403);
    }

    public function test_path_traversal_is_blocked(): void
    {
        $user = User::factory()->hrManager()->withTwoFactor()->create();
        Storage::disk('local')->put('nric_documents/test.pdf', 'fake-pdf-content');

        $response = $this->actingAs($user)->get('/secure-file/nric_documents/../.env');
        // Path traversal chars are stripped, resulting path doesn't match a valid directory
        $response->assertStatus(404);
    }

    public function test_unknown_directory_is_denied(): void
    {
        $user = User::factory()->hrManager()->withTwoFactor()->create();
        Storage::disk('local')->put('secret_stuff/data.txt', 'content');

        $response = $this->actingAs($user)->get('/secure-file/secret_stuff/data.txt');
        $response->assertStatus(403);
    }

    public function test_hr_executive_can_access_education_certificates(): void
    {
        $user = User::factory()->hrExecutive()->withTwoFactor()->create();
        Storage::disk('local')->put('education_certificates/cert.pdf', 'fake-content');

        $response = $this->actingAs($user)->get('/secure-file/education_certificates/cert.pdf');
        $response->assertStatus(200);
    }

    public function test_hr_manager_can_access_claim_receipts(): void
    {
        $user = User::factory()->hrManager()->withTwoFactor()->create();
        Storage::disk('local')->put('claim_receipts/receipt.pdf', 'fake-content');

        $response = $this->actingAs($user)->get('/secure-file/claim_receipts/receipt.pdf');
        $response->assertStatus(200);
    }
}
