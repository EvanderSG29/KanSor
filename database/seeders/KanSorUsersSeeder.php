<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class KanSorUsersSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(['email' => 'admin@kansor.local'], [
            'name' => 'Admin KanSor',
            'password' => '12345678',
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'active' => true,
        ]);

        User::query()->updateOrCreate(['email' => 'petugas@kansor.local'], [
            'name' => 'Petugas KanSor',
            'password' => '12345678',
            'role' => User::ROLE_PETUGAS,
            'status' => User::STATUS_ACTIVE,
            'active' => true,
        ]);
    }
}
