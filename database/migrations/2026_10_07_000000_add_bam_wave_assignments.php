<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE tagihan_pembayaran MODIFY status ENUM('belum_bayar', 'sebagian', 'lunas', 'tidak_berlaku') NOT NULL DEFAULT 'belum_bayar'");

        Schema::create('penetapan_gelombang_bam', function (Blueprint $table) {
            $table->id('id_penetapan_gelombang_bam');
            $table->unsignedBigInteger('id_siswa');
            $table->unsignedBigInteger('id_tahun_ajaran');
            $table->unsignedBigInteger('id_tagihan_pembayaran');
            $table->string('kode_periode', 20);
            $table->unsignedBigInteger('ditetapkan_oleh');
            $table->timestamp('ditetapkan_pada');
            $table->unsignedBigInteger('dikoreksi_oleh')->nullable();
            $table->timestamp('dikoreksi_pada')->nullable();
            $table->string('alasan_koreksi', 255)->nullable();
            $table->timestamps();

            $table->unique(['id_siswa', 'id_tahun_ajaran'], 'penetapan_bam_siswa_ta_unique');
            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->restrictOnDelete();
            $table->foreign('id_tahun_ajaran')->references('id_tahun_ajaran')->on('tahun_ajaran')->restrictOnDelete();
            $table->foreign('id_tagihan_pembayaran')->references('id_tagihan_pembayaran')->on('tagihan_pembayaran')->restrictOnDelete();
            $table->foreign('ditetapkan_oleh')->references('id_user')->on('users')->restrictOnDelete();
            $table->foreign('dikoreksi_oleh')->references('id_user')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penetapan_gelombang_bam');

        DB::statement("ALTER TABLE tagihan_pembayaran MODIFY status ENUM('belum_bayar', 'sebagian', 'lunas') NOT NULL DEFAULT 'belum_bayar'");
    }
};
