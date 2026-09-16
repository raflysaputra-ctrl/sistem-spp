<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tahun_ajaran', function (Blueprint $table) {
            $table->enum('status', ['persiapan', 'aktif', 'ditutup'])
                ->default('persiapan')
                ->after('aktif');
        });

        $tahunAjaranAktif = DB::table('tahun_ajaran')
            ->where('aktif', true)
            ->orderByDesc('tanggal_mulai')
            ->first();

        if (! $tahunAjaranAktif) {
            return;
        }

        DB::table('tahun_ajaran')
            ->where('aktif', true)
            ->update(['status' => 'aktif']);

        DB::table('tahun_ajaran')
            ->where('aktif', false)
            ->whereDate('tanggal_selesai', '<', $tahunAjaranAktif->tanggal_mulai)
            ->update(['status' => 'ditutup']);
    }

    public function down(): void
    {
        Schema::table('tahun_ajaran', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
