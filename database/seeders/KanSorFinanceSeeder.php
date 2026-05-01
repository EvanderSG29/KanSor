<?php

namespace Database\Seeders;

use App\Models\CanteenTotal;
use Illuminate\Database\Seeder;

class KanSorFinanceSeeder extends Seeder
{
    public function run(): void
    {
        CanteenTotal::query()->updateOrCreate([
            'date' => now('Asia/Jakarta')->toDateString(),
        ], [
            'total_amount' => 50000,
        ]);
    }
}
