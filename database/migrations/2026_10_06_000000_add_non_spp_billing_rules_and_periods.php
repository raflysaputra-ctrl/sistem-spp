<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_pembayaran', function (Blueprint $table) {
            $table->string('aturan_pembayaran', 20)->default('cicilan')->after('target_tingkat');
            $table->string('tipe_periode', 20)->default('tahunan')->after('aturan_pembayaran');
        });

        DB::table('jenis_pembayaran')
            ->whereIn('kode_jenis', ['PTS', 'PAS'])
            ->update([
                'aturan_pembayaran' => 'sekali_bayar',
                'tipe_periode' => 'semester',
            ]);

        DB::table('jenis_pembayaran')
            ->whereIn('kode_jenis', ['PKL', 'UJIKOM'])
            ->update([
                'aturan_pembayaran' => 'cicilan',
                'tipe_periode' => 'tahunan',
            ]);

        DB::table('jenis_pembayaran')
            ->where('kode_jenis', 'BIAYA_AWAL_MASUK')
            ->update([
                'aturan_pembayaran' => 'cicilan',
                'tipe_periode' => 'gelombang',
            ]);

        Schema::table('tagihan_pembayaran', function (Blueprint $table) {
            $table->string('kode_periode', 20)->default('tahunan')->after('periode_keterangan');
        });

        Schema::table('tagihan_pembayaran', function (Blueprint $table) {
            $table->unique(
                ['id_siswa', 'id_jenis_pembayaran', 'id_tahun_ajaran', 'kode_periode'],
                'tagihan_non_spp_siswa_jenis_ta_periode_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('tagihan_pembayaran', function (Blueprint $table) {
            $table->dropUnique('tagihan_non_spp_siswa_jenis_ta_periode_unique');
            $table->dropColumn('kode_periode');
        });

        Schema::table('jenis_pembayaran', function (Blueprint $table) {
            $table->dropColumn(['aturan_pembayaran', 'tipe_periode']);
        });
    }
};
