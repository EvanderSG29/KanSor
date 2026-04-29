<?php

namespace Database\Factories;

use App\Models\CanteenTotal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CanteenTotal>
 */
class CanteenTotalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => now('Asia/Jakarta')->toDateString(),
            'total_amount' => fake()->numberBetween(10000, 100000),
            'status_iii' => 'belum',
            'status_iv' => null,
            'taken_note' => null,
            'paid_at' => null,
            'paid_amount' => null,
        ];
    }
}
