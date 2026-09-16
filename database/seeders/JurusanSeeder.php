<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JurusanSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('jurusan')->upsert([
            ['kode_jurusan' => 'PPLG', 'nama_jurusan' => 'PPLG', 'created_at' => $now, 'updated_at' => $now],
            ['kode_jurusan' => 'DKV', 'nama_jurusan' => 'DKV', 'created_at' => $now, 'updated_at' => $now],
        ], ['kode_jurusan'], ['nama_jurusan', 'updated_at']);
    }
}
