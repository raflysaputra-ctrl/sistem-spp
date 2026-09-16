<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TarifSppSeeder extends Seeder
{
    public function run(): void
    {
        $idTahunAjaran = DB::table('tahun_ajaran')
            ->where('tahun_ajaran', '2026/2027')
            ->value('id_tahun_ajaran');
        $now = now();

        DB::table('tarif_spp')->upsert([
            ['id_tahun_ajaran' => $idTahunAjaran, 'tingkat' => 1, 'nominal' => 150000, 'created_at' => $now, 'updated_at' => $now],
            ['id_tahun_ajaran' => $idTahunAjaran, 'tingkat' => 2, 'nominal' => 120000, 'created_at' => $now, 'updated_at' => $now],
            ['id_tahun_ajaran' => $idTahunAjaran, 'tingkat' => 3, 'nominal' => 120000, 'created_at' => $now, 'updated_at' => $now],
        ], ['id_tahun_ajaran', 'tingkat'], ['nominal', 'updated_at']);
    }
}
