<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tagihan_pembayaran') || Schema::hasColumn('tagihan_pembayaran', 'kode_periode')) {
            return;
        }

        // This supplements the earlier applied preflight before the period
        // migration assigns every old bill the same default period value.
        $adaBenturanPeriode = DB::table('tagihan_pembayaran')
            ->select('id_siswa', 'id_jenis_pembayaran', 'id_tahun_ajaran')
            ->groupBy('id_siswa', 'id_jenis_pembayaran', 'id_tahun_ajaran')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($adaBenturanPeriode) {
            throw new RuntimeException(
                'Upgrade periode tagihan non-SPP dihentikan karena terdapat lebih dari satu tagihan lama untuk kombinasi siswa, jenis pembayaran, dan tahun ajaran yang sama. '
                .'Tetapkan atau koreksi periode setiap tagihan terlebih dahulu sebelum menjalankan migration R3.',
            );
        }
    }

    public function down(): void
    {
        // Preflight only. It does not change data or schema.
    }
};
