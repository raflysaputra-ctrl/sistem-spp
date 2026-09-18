<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['petugas', 'siswa'])->default('petugas')->after('password');
            $table->unsignedBigInteger('id_siswa')->nullable()->unique()->after('role');
            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->cascadeOnDelete();
        });

        Schema::create('arsip_kwitansi_siswa', function (Blueprint $table) {
            $table->id('id_arsip_kwitansi');
            $table->unsignedBigInteger('id_siswa');
            $table->unsignedBigInteger('id_pembayaran')->nullable();
            $table->string('path', 255)->unique();
            $table->string('mime_type', 50);
            $table->unsignedInteger('ukuran_file');
            $table->timestamps();

            $table->index('id_siswa');
            $table->index('id_pembayaran');
            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->cascadeOnDelete();
            $table->foreign('id_pembayaran')->references('id_pembayaran')->on('pembayaran')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arsip_kwitansi_siswa');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_siswa']);
            $table->dropUnique(['id_siswa']);
            $table->dropColumn(['role', 'id_siswa']);
        });
    }
};
