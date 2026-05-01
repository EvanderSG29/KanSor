<?php

namespace Database\Seeders;

use App\Models\Food;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Seeder;

class KanSorTransactionsSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('role', User::ROLE_PETUGAS)->first();
        $foods = Food::query()->where('active', true)->limit(3)->get();

        if ($user === null || $foods->isEmpty()) {
            return;
        }

        $sale = Sale::query()->create([
            'date' => now('Asia/Jakarta')->toDateString(),
            'supplier_id' => $foods->first()->supplier_id,
            'user_id' => $user->getKey(),
            'total_supplier' => 0,
            'total_canteen' => 0,
            'status_i' => Sale::STATUS_PENDING,
            'status_ii' => Sale::STATUS_PENDING,
        ]);

        $supplierTotal = 0;
        $canteenTotal = 0;

        foreach ($foods as $food) {
            $qty = 5;
            $gross = $qty * (int) $food->default_price;
            $cut = (int) round($gross * 0.1);
            $sale->items()->create([
                'food_id' => $food->getKey(),
                'unit' => $food->unit,
                'quantity' => $qty,
                'leftover' => 1,
                'price_per_unit' => (int) $food->default_price,
                'total_item' => $gross,
                'cut_amount' => $cut,
            ]);
            $supplierTotal += ($gross - $cut);
            $canteenTotal += $cut;
        }

        $sale->update(['total_supplier' => $supplierTotal, 'total_canteen' => $canteenTotal]);
    }
}
