<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pembayaran_non_spp', function (Blueprint $table) {
            $table->id('id_pembayaran_non_spp');
            $table->string('no_kwitansi', 50)->unique();
            $table->unsignedBigInteger('id_tagihan_pembayaran');
            $table->unsignedBigInteger('id_siswa');
            $table->unsignedBigInteger('id_user');
            $table->dateTime('tanggal_bayar');
            $table->decimal('nominal_bayar', 12, 0);
            $table->text('keterangan')->nullable();
            $table->enum('status', ['aktif', 'dibatalkan'])->default('aktif');
            $table->text('alasan_pembatalan')->nullable();
            $table->unsignedBigInteger('dibatalkan_oleh')->nullable();
            $table->dateTime('dibatalkan_pada')->nullable();
            $table->timestamps();

            $table->foreign('id_tagihan_pembayaran')->references('id_tagihan_pembayaran')->on('tagihan_pembayaran')->onDelete('cascade');
            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->onDelete('cascade');
            $table->foreign('id_user')->references('id_user')->on('users');
            $table->foreign('dibatalkan_oleh')->references('id_user')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran_non_spp');
    }
};
