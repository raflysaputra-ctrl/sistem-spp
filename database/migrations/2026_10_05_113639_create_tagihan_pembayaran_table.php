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
        Schema::create('tagihan_pembayaran', function (Blueprint $table) {
            $table->id('id_tagihan_pembayaran');
            $table->unsignedBigInteger('id_siswa');
            $table->unsignedBigInteger('id_jenis_pembayaran');
            $table->unsignedBigInteger('id_tahun_ajaran');
            $table->decimal('total_tagihan', 12, 0);
            $table->decimal('minimal_dp', 12, 0)->default(0);
            $table->string('periode_keterangan', 100)->nullable(); // contoh: Semester 1, Gelombang 1
            $table->enum('status', ['belum_bayar', 'sebagian', 'lunas'])->default('belum_bayar');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->onDelete('cascade');
            $table->foreign('id_jenis_pembayaran')->references('id_jenis_pembayaran')->on('jenis_pembayaran')->onDelete('cascade');
            $table->foreign('id_tahun_ajaran')->references('id_tahun_ajaran')->on('tahun_ajaran')->onDelete('cascade');
            $table->foreign('created_by')->references('id_user')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagihan_pembayaran');
    }
};
