<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'contact_info' => fake()->phoneNumber(),
            'percentage_cut' => fake()->randomElement([10, 15, 20]),
            'active' => true,
        ];
    }
}
