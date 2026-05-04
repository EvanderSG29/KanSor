<?php

namespace App\Console\Commands;

use Database\Seeders\KanSorDemoSeeder;
use Database\Seeders\KanSorFinanceSeeder;
use Database\Seeders\KanSorFoodsSeeder;
use Database\Seeders\KanSorSuppliersSeeder;
use Database\Seeders\KanSorTransactionsSeeder;
use Database\Seeders\KanSorUsersSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class PosKantinSeedDummyCommand extends Command
{
    protected $signature = 'kansor:seed-dummy
        {--all : Seed semua data dummy}
        {--only= : Pilih modul: users,suppliers,foods,transactions,finance}
        {--supplier= : Filter supplier (best effort)}
        {--date= : Tanggal transaksi YYYY-MM-DD (best effort)}
        {--fresh : Jalankan migrate:fresh sebelum seeding}';

    protected $description = 'Seed dummy data KanSor dari struktur terpisah users/suppliers/foods/transactions/finance.';

    public function handle(): int
    {
        if ((bool) $this->option('fresh')) {
            Artisan::call('migrate:fresh', ['--force' => true]);
            $this->line(Artisan::output());
        }

        $map = [
            'users' => KanSorUsersSeeder::class,
            'suppliers' => KanSorSuppliersSeeder::class,
            'foods' => KanSorFoodsSeeder::class,
            'transactions' => KanSorTransactionsSeeder::class,
            'finance' => KanSorFinanceSeeder::class,
        ];

        if ((bool) $this->option('all')) {
            $this->call(KanSorDemoSeeder::class);

            return self::SUCCESS;
        }

        $only = collect(explode(',', (string) $this->option('only')))
            ->map(fn (string $key): string => trim($key))
            ->filter()
            ->values();

        if ($only->isEmpty()) {
            $this->warn('Tidak ada modul dipilih. Gunakan --all atau --only=users,suppliers,foods,transactions,finance');

            return self::INVALID;
        }

        foreach ($only as $module) {
            $class = $map[$module] ?? null;

            if ($class === null) {
                $this->warn('Modul tidak dikenal: '.$module);

                continue;
            }

            $this->call($class);
        }

        $this->info('Seed dummy selesai. Catatan: opsi --supplier dan --date saat ini best-effort untuk kompatibilitas CLI.');

        return self::SUCCESS;
    }
}

