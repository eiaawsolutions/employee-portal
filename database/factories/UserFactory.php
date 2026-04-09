<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name'       => fake()->name(),
            'work_email' => fake()->unique()->safeEmail(),
            'password'   => Hash::make('password123'),
            'role'       => 'employee',
            'is_active'  => true,
        ];
    }

    public function hrManager(): static
    {
        return $this->state(['role' => 'hr_manager']);
    }

    public function hrExecutive(): static
    {
        return $this->state(['role' => 'hr_executive']);
    }

    public function hrIntern(): static
    {
        return $this->state(['role' => 'hr_intern']);
    }

    public function itManager(): static
    {
        return $this->state(['role' => 'it_manager']);
    }

    public function itExecutive(): static
    {
        return $this->state(['role' => 'it_executive']);
    }

    public function itIntern(): static
    {
        return $this->state(['role' => 'it_intern']);
    }

    public function superadmin(): static
    {
        return $this->state(['role' => 'superadmin']);
    }

    public function systemAdmin(): static
    {
        return $this->state(['role' => 'system_admin']);
    }

    public function financeManager(): static
    {
        return $this->state(['role' => 'finance_manager']);
    }

    public function withTwoFactor(): static
    {
        return $this->state([
            'two_factor_secret'       => \Illuminate\Support\Facades\Crypt::encryptString('JBSWY3DPEHPK3PXP'),
            'two_factor_recovery_codes' => \Illuminate\Support\Facades\Crypt::encryptString(json_encode(['code1', 'code2'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'is_active'           => false,
            'deactivation_reason' => 'manual',
            'deactivated_at'      => now(),
        ]);
    }
}
