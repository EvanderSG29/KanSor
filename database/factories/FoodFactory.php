<?php

namespace Database\Factories;

use App\Models\Food;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Food>
 */
class FoodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'name' => fake()->words(2, true),
            'unit' => fake()->randomElement(['pcs', 'porsi', 'gelas', 'bungkus']),
            'default_price' => fake()->numberBetween(1000, 15000),
            'active' => true,
        ];
    }
}
