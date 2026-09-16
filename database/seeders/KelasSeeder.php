<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KelasSeeder extends Seeder
{
    public function run(): void
    {
        $jurusan = DB::table('jurusan')->pluck('id_jurusan', 'kode_jurusan');
        $now = now();
        $kelas = [];
        $tingkatLabels = [1 => 'X', 2 => 'XI', 3 => 'XII'];

        foreach ($jurusan as $kodeJurusan => $idJurusan) {
            foreach ($tingkatLabels as $tingkat => $label) {
                foreach (range(1, 4) as $rombel) {
                    $kelas[] = [
                        'id_jurusan' => $idJurusan,
                        'tingkat' => $tingkat,
                        'rombel' => $rombel,
                        'nama_kelas' => "$label $kodeJurusan $rombel",
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        DB::table('kelas')->upsert(
            $kelas,
            ['id_jurusan', 'tingkat', 'rombel'],
            ['nama_kelas', 'updated_at'],
        );
    }
}
