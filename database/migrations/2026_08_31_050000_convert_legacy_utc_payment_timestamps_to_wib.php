<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tagihan_spp')
            ->whereNotNull('tanggal_lunas')
            ->update(['tanggal_lunas' => DB::raw('DATE_ADD(tanggal_lunas, INTERVAL 7 HOUR)')]);

        DB::table('pembayaran')
            ->whereNotNull('tanggal_bayar')
            ->update(['tanggal_bayar' => DB::raw('DATE_ADD(tanggal_bayar, INTERVAL 7 HOUR)')]);
    }

    public function down(): void
    {
        DB::table('tagihan_spp')
            ->whereNotNull('tanggal_lunas')
            ->update(['tanggal_lunas' => DB::raw('DATE_SUB(tanggal_lunas, INTERVAL 7 HOUR)')]);

        DB::table('pembayaran')
            ->whereNotNull('tanggal_bayar')
            ->update(['tanggal_bayar' => DB::raw('DATE_SUB(tanggal_bayar, INTERVAL 7 HOUR)')]);
    }
};
