<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class KanSorDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            KanSorUsersSeeder::class,
            KanSorSuppliersSeeder::class,
            KanSorFoodsSeeder::class,
            KanSorTransactionsSeeder::class,
            KanSorFinanceSeeder::class,
        ]);
    }
}
