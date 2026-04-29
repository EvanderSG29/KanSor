<?php

namespace Database\Factories;

use App\Models\Food;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItem>
 */
class SaleItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);
        $pricePerUnit = fake()->numberBetween(1000, 12000);
        $totalItem = $quantity * $pricePerUnit;

        return [
            'sale_id' => Sale::factory(),
            'food_id' => Food::factory(),
            'unit' => fake()->randomElement(['pcs', 'porsi', 'gelas', 'bungkus']),
            'quantity' => $quantity,
            'leftover' => fake()->optional()->numberBetween(0, $quantity),
            'price_per_unit' => $pricePerUnit,
            'total_item' => $totalItem,
            'cut_amount' => (int) round($totalItem * 0.1),
        ];
    }
}
