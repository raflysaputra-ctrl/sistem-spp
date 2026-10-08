<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penerimaan', function (Blueprint $table) {
            $table->id('id_penerimaan');
            $table->string('no_kwitansi', 50)->unique();
            $table->unsignedBigInteger('id_siswa');
            $table->unsignedBigInteger('id_user');
            $table->dateTime('tanggal_bayar');
            $table->decimal('total_bayar', 12, 0);
            $table->enum('status', ['aktif', 'dibatalkan'])->default('aktif');
            $table->text('alasan_pembatalan')->nullable();
            $table->unsignedBigInteger('dibatalkan_oleh')->nullable();
            $table->dateTime('dibatalkan_pada')->nullable();
            $table->timestamps();

            $table->index('tanggal_bayar');
            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->restrictOnDelete();
            $table->foreign('id_user')->references('id_user')->on('users')->restrictOnDelete();
            $table->foreign('dibatalkan_oleh')->references('id_user')->on('users')->restrictOnDelete();
        });

        Schema::table('pembayaran', function (Blueprint $table) {
            $table->unsignedBigInteger('id_penerimaan')->nullable()->unique()->after('id_pembayaran');
            $table->foreign('id_penerimaan')->references('id_penerimaan')->on('penerimaan')->restrictOnDelete();
        });

        Schema::table('pembayaran_non_spp', function (Blueprint $table) {
            $table->unsignedBigInteger('id_penerimaan')->nullable()->unique()->after('id_pembayaran_non_spp');
            $table->foreign('id_penerimaan')->references('id_penerimaan')->on('penerimaan')->restrictOnDelete();
        });

        Schema::table('arsip_kwitansi_siswa', function (Blueprint $table) {
            $table->unsignedBigInteger('id_penerimaan')->nullable()->unique()->after('id_pembayaran');
            $table->foreign('id_penerimaan')->references('id_penerimaan')->on('penerimaan')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('arsip_kwitansi_siswa', function (Blueprint $table) {
            $table->dropForeign(['id_penerimaan']);
            $table->dropUnique(['id_penerimaan']);
            $table->dropColumn('id_penerimaan');
        });

        Schema::table('pembayaran_non_spp', function (Blueprint $table) {
            $table->dropForeign(['id_penerimaan']);
            $table->dropUnique(['id_penerimaan']);
            $table->dropColumn('id_penerimaan');
        });

        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropForeign(['id_penerimaan']);
            $table->dropUnique(['id_penerimaan']);
            $table->dropColumn('id_penerimaan');
        });

        Schema::dropIfExists('penerimaan');
    }
};
