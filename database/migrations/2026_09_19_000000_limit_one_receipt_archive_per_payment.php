<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arsip_kwitansi_siswa', function (Blueprint $table) {
            $table->unique('id_pembayaran');
        });
    }

    public function down(): void
    {
        Schema::table('arsip_kwitansi_siswa', function (Blueprint $table) {
            $table->dropUnique(['id_pembayaran']);
        });
    }
};
