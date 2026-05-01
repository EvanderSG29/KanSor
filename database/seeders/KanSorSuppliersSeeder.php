<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class KanSorSuppliersSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Supplier Gorengan', 'contact_info' => 'Pak A', 'percentage_cut' => 10],
            ['name' => 'Supplier Minuman', 'contact_info' => 'Bu B', 'percentage_cut' => 12],
        ] as $supplier) {
            Supplier::query()->updateOrCreate(['name' => $supplier['name']], $supplier + ['active' => true]);
        }
    }
}
