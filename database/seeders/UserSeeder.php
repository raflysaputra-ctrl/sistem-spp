<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'nama' => 'Test',
                'username' => 'tu',
                'password' => 'tu123456',
                'role' => User::ROLE_TU,
            ],
            [
                'nama' => 'Admin',
                'username' => 'admin',
                'password' => 'admin123',
                'role' => User::ROLE_ADMIN,
            ],
            [
                'nama' => 'Kepala Sekolah',
                'username' => 'kepsek',
                'password' => 'kepsek123',
                'role' => User::ROLE_KEPALA_SEKOLAH,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['username' => $user['username']],
                [
                    'nama' => $user['nama'],
                    'password' => Hash::make($user['password']),
                    'role' => $user['role'],
                    'id_siswa' => null,
                ],
            );
        }
    }
}
