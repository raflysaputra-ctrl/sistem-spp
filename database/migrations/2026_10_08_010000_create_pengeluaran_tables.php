<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_pengeluaran', function (Blueprint $table) {
            $table->id('id_kategori_pengeluaran');
            $table->string('nama_kategori', 100)->unique();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('pengeluaran', function (Blueprint $table) {
            $table->id('id_pengeluaran');
            $table->unsignedBigInteger('id_kategori_pengeluaran');
            $table->unsignedBigInteger('id_user');
            $table->date('tanggal_pengeluaran');
            $table->text('keterangan');
            $table->decimal('nominal', 12, 0);
            $table->enum('status', ['aktif', 'dibatalkan'])->default('aktif');
            $table->text('alasan_pembatalan')->nullable();
            $table->unsignedBigInteger('dibatalkan_oleh')->nullable();
            $table->dateTime('dibatalkan_pada')->nullable();
            $table->timestamps();

            $table->index(['tanggal_pengeluaran', 'status']);
            $table->foreign('id_kategori_pengeluaran')->references('id_kategori_pengeluaran')->on('kategori_pengeluaran')->restrictOnDelete();
            $table->foreign('id_user')->references('id_user')->on('users')->restrictOnDelete();
            $table->foreign('dibatalkan_oleh')->references('id_user')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengeluaran');
        Schema::dropIfExists('kategori_pengeluaran');
    }
};
