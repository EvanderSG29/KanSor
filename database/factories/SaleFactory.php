<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => now('Asia/Jakarta')->toDateString(),
            'supplier_id' => Supplier::factory(),
            'user_id' => User::factory(),
            'additional_users' => null,
            'total_supplier' => 0,
            'total_canteen' => 0,
            'status_i' => 'menunggu',
            'status_ii' => 'menunggu',
            'taken_note' => null,
            'paid_at' => null,
            'paid_amount' => null,
        ];
    }
}
