<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'nama' => 'Administrator',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
                'created_by' => null,
            ]
        );

        User::firstOrCreate(
            ['username' => 'tata_usaha'],
            [
                'nama' => 'Tata Usaha',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_TU,
                'is_active' => true,
                'created_by' => null,
            ]
        );

        User::firstOrCreate(
            ['username' => 'kepala_sekolah'],
            [
                'nama' => 'Kepala Sekolah',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_KEPALA_SEKOLAH,
                'is_active' => true,
                'created_by' => null,
            ]
        );
    }
}
