<?php

namespace Database\Factories;

use App\Models\Onboarding;
use Illuminate\Database\Eloquent\Factories\Factory;

class OnboardingFactory extends Factory
{
    protected $model = Onboarding::class;

    public function definition(): array
    {
        return [
            'status'   => 'pending',
            'hr_email' => fake()->safeEmail(),
            'it_email' => fake()->safeEmail(),
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed', 'is_expired' => true]);
    }
}
