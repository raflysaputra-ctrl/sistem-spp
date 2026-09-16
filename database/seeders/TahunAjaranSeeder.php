<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TahunAjaranSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('tahun_ajaran')->upsert([
            [
                'tahun_ajaran' => '2026/2027',
                'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2027-06-30',
                'aktif' => true,
                'status' => 'aktif',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['tahun_ajaran'], ['tanggal_mulai', 'tanggal_selesai', 'aktif', 'status', 'updated_at']);
    }
}
