<?php

namespace Database\Factories;

use App\Models\AssetInventory;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetInventoryFactory extends Factory
{
    protected $model = AssetInventory::class;

    public function definition(): array
    {
        return [
            'asset_tag'            => strtoupper(fake()->bothify('???-###')),
            'asset_type'           => fake()->randomElement(['laptop', 'monitor', 'converter', 'access_card']),
            'brand'                => fake()->randomElement(['Dell', 'HP', 'Lenovo', 'Apple']),
            'model'                => fake()->word(),
            'serial_number'        => fake()->unique()->bothify('SN-????-####'),
            'status'               => 'available',
            'asset_condition'      => 'good',
            'purchase_vendor'      => fake()->company(),
            'purchase_cost'        => fake()->randomFloat(2, 100, 5000),
            'purchase_date'        => fake()->dateTimeBetween('-2 years', '-1 month'),
            'maintenance_status'   => 'none',
        ];
    }
}
