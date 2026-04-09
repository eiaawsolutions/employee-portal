<?php

namespace Database\Factories;

use App\Models\Onboarding;
use App\Models\PersonalDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonalDetailFactory extends Factory
{
    protected $model = PersonalDetail::class;

    public function definition(): array
    {
        return [
            'onboarding_id'           => Onboarding::factory(),
            'full_name'               => fake()->name(),
            'official_document_id'    => fake()->numerify('######-##-####'),
            'date_of_birth'           => fake()->dateTimeBetween('-50 years', '-20 years'),
            'sex'                     => fake()->randomElement(['male', 'female']),
            'marital_status'          => fake()->randomElement(['single', 'married']),
            'religion'                => fake()->randomElement(['Islam', 'Christianity', 'Buddhism', 'Hindu']),
            'race'                    => fake()->randomElement(['Malay', 'Chinese', 'Indian', 'Others']),
            'residential_address'     => fake()->address(),
            'personal_contact_number' => fake()->phoneNumber(),
            'personal_email'          => fake()->safeEmail(),
            'bank_account_number'     => fake()->numerify('##########'),
        ];
    }
}
