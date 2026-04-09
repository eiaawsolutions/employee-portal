<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeListingTest extends TestCase
{
    use RefreshDatabase;

    private function createHrUserWithEmployee(): User
    {
        $user = User::factory()->hrManager()->create();
        Employee::factory()->withUser($user)->create();
        return $user;
    }

    public function test_hr_user_can_view_employee_listing(): void
    {
        $user = $this->createHrUserWithEmployee();
        Employee::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('employees.index'));
        $response->assertStatus(200);
    }

    public function test_employee_cannot_view_employee_listing(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)->get(route('employees.index'));
        $response->assertStatus(403);
    }

    public function test_employee_listing_search_filters(): void
    {
        $user = $this->createHrUserWithEmployee();
        Employee::factory()->create(['full_name' => 'John Doe', 'department' => 'Technology']);
        Employee::factory()->create(['full_name' => 'Jane Smith', 'department' => 'Marketing']);

        $response = $this->actingAs($user)->get(route('employees.index', ['search' => 'John']));
        $response->assertStatus(200);
        $response->assertSee('John Doe');
    }

    public function test_employee_listing_department_filter(): void
    {
        $user = $this->createHrUserWithEmployee();
        Employee::factory()->create(['full_name' => 'Tech Worker', 'department' => 'Technology']);
        Employee::factory()->create(['full_name' => 'Marketing Worker', 'department' => 'Marketing']);

        $response = $this->actingAs($user)->get(route('employees.index', ['department' => 'Technology']));
        $response->assertStatus(200);
    }

    public function test_deactivated_employees_not_shown_in_listing(): void
    {
        $user = $this->createHrUserWithEmployee();
        Employee::factory()->create(['full_name' => 'Active Employee']);
        Employee::factory()->deactivated()->create(['full_name' => 'Deactivated Employee']);

        $response = $this->actingAs($user)->get(route('employees.index'));
        $response->assertStatus(200);
        $response->assertSee('Active Employee');
        $response->assertDontSee('Deactivated Employee');
    }
}
