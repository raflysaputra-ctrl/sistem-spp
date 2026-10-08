<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KategoriPengeluaranSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('kategori_pengeluaran')->insertOrIgnore([
            ['nama_kategori' => 'Alat Tulis Kantor', 'aktif' => true, 'created_at' => $now, 'updated_at' => $now],
            ['nama_kategori' => 'Listrik dan Internet', 'aktif' => true, 'created_at' => $now, 'updated_at' => $now],
            ['nama_kategori' => 'Pemeliharaan Sarana', 'aktif' => true, 'created_at' => $now, 'updated_at' => $now],
            ['nama_kategori' => 'Kegiatan Sekolah', 'aktif' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
