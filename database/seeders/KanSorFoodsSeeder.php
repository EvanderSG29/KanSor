<?php

namespace Database\Seeders;

use App\Models\Food;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KanSorFoodsSeeder extends Seeder
{
    public function run(): void
    {
        $mapping = [
            'Supplier Gorengan' => ['Bakwan', 'Risol', 'Tahu Isi'],
            'Supplier Minuman' => ['Es Teh', 'Jus Jeruk'],
        ];

        foreach ($mapping as $supplierName => $foods) {
            $supplier = Supplier::query()->where('name', $supplierName)->first();

            if ($supplier === null) {
                continue;
            }

            foreach ($foods as $foodName) {
                Food::query()->updateOrCreate([
                    'supplier_id' => $supplier->getKey(),
                    'name' => Str::title(trim($foodName)),
                ], [
                    'unit' => 'pcs',
                    'default_price' => 5000,
                    'active' => true,
                ]);
            }
        }
    }
}
