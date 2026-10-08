<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JenisPembayaranSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('jenis_pembayaran')->insertOrIgnore([
            ['kode_jenis' => 'PTS', 'nama_jenis' => 'PTS', 'keterangan' => 'Penilaian Tengah Semester', 'target_tingkat' => null, 'aturan_pembayaran' => 'sekali_bayar', 'tipe_periode' => 'semester', 'aktif' => true, 'created_at' => $now, 'updated_at' => $now],
            ['kode_jenis' => 'PAS', 'nama_jenis' => 'PAS', 'keterangan' => 'Penilaian Akhir Semester', 'target_tingkat' => json_encode([1, 2]), 'aturan_pembayaran' => 'sekali_bayar', 'tipe_periode' => 'semester', 'aktif' => true, 'created_at' => $now, 'updated_at' => $now],
            ['kode_jenis' => 'PKL', 'nama_jenis' => 'PKL', 'keterangan' => 'Praktik Kerja Lapangan', 'target_tingkat' => json_encode([2]), 'aturan_pembayaran' => 'cicilan', 'tipe_periode' => 'tahunan', 'aktif' => true, 'created_at' => $now, 'updated_at' => $now],
            ['kode_jenis' => 'UJIKOM', 'nama_jenis' => 'UJIKOM', 'keterangan' => 'Uji Kompetensi', 'target_tingkat' => json_encode([3]), 'aturan_pembayaran' => 'cicilan', 'tipe_periode' => 'tahunan', 'aktif' => true, 'created_at' => $now, 'updated_at' => $now],
            ['kode_jenis' => 'BIAYA_AWAL_MASUK', 'nama_jenis' => 'Biaya Awal Masuk', 'keterangan' => 'Biaya awal masuk siswa baru', 'target_tingkat' => json_encode([1]), 'aturan_pembayaran' => 'cicilan', 'tipe_periode' => 'gelombang', 'aktif' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
