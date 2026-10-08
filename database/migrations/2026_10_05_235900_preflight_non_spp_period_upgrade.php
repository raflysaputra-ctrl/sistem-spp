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

        // This migration is intentionally ordered before the period unique index.
        // Ambiguous legacy rows must be reviewed before a schema change can assign
        // every historical semester/wave bill the same default period.
        $adaDuplikatPeriode = DB::table('tagihan_pembayaran as tagihan')
            ->join('jenis_pembayaran as jenis', 'jenis.id_jenis_pembayaran', '=', 'tagihan.id_jenis_pembayaran')
            ->whereIn('jenis.kode_jenis', ['PTS', 'PAS', 'BIAYA_AWAL_MASUK'])
            ->select('tagihan.id_siswa', 'tagihan.id_jenis_pembayaran', 'tagihan.id_tahun_ajaran')
            ->groupBy('tagihan.id_siswa', 'tagihan.id_jenis_pembayaran', 'tagihan.id_tahun_ajaran')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($adaDuplikatPeriode) {
            throw new RuntimeException(
                'Upgrade periode tagihan non-SPP dihentikan karena terdapat tagihan semester/gelombang lama yang ambigu. '
                .'Tetapkan periode setiap tagihan terlebih dahulu sebelum menjalankan migration R3.',
            );
        }
    }

    public function down(): void
    {
        // Preflight only. It does not change data or schema.
    }
};
