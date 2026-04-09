<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'full_name'               => fake()->name(),
            'company_email'           => fake()->unique()->companyEmail(),
            'designation'             => fake()->jobTitle(),
            'department'              => fake()->randomElement(['Technology', 'Human Resources', 'Marketing', 'Finance']),
            'company'                 => 'Claritas Asia Sdn. Bhd.',
            'office_location'         => 'Kuala Lumpur HQ',
            'employment_type'         => 'permanent',
            'work_role'               => 'employee',
            'start_date'              => fake()->dateTimeBetween('-2 years', '-1 month'),
            'active_from'             => fake()->dateTimeBetween('-2 years', '-1 month'),
            'sex'                     => fake()->randomElement(['male', 'female']),
            'date_of_birth'           => fake()->dateTimeBetween('-50 years', '-20 years'),
            'personal_contact_number' => fake()->phoneNumber(),
            'personal_email'          => fake()->safeEmail(),
            'residential_address'     => fake()->address(),
        ];
    }

    public function withUser(?User $user = null): static
    {
        return $this->state(fn () => [
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }

    public function deactivated(): static
    {
        return $this->state([
            'active_until' => now()->subDay(),
            'exit_date'    => now()->subDay(),
        ]);
    }
}
