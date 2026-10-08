<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_pembayaran_non_spp', function (Blueprint $table) {
            $table->id('id_detail_pembayaran_non_spp');
            $table->unsignedBigInteger('id_pembayaran_non_spp');
            $table->unsignedBigInteger('id_tagihan_pembayaran');
            $table->decimal('nominal_bayar', 12, 0);
            $table->decimal('total_terbayar_setelah', 12, 0);
            $table->timestamps();

            $table->unique(
                ['id_pembayaran_non_spp', 'id_tagihan_pembayaran'],
                'detail_non_spp_pembayaran_tagihan_unique',
            );
            $table->foreign('id_pembayaran_non_spp')
                ->references('id_pembayaran_non_spp')
                ->on('pembayaran_non_spp')
                ->restrictOnDelete();
            $table->foreign('id_tagihan_pembayaran')
                ->references('id_tagihan_pembayaran')
                ->on('tagihan_pembayaran')
                ->restrictOnDelete();
        });

        // Existing rows represented one bill per receipt. Preserve them as detail rows
        // before new receipts start using the header-detail structure.
        $runningTotals = [];

        foreach (DB::table('pembayaran_non_spp')
            ->orderBy('tanggal_bayar')
            ->orderBy('id_pembayaran_non_spp')
            ->cursor() as $pembayaran) {
            if ($pembayaran->id_tagihan_pembayaran === null) {
                continue;
            }

            $idTagihan = (int) $pembayaran->id_tagihan_pembayaran;
            $runningTotals[$idTagihan] = ($runningTotals[$idTagihan] ?? 0) + (int) $pembayaran->nominal_bayar;

            DB::table('detail_pembayaran_non_spp')->insert([
                'id_pembayaran_non_spp' => $pembayaran->id_pembayaran_non_spp,
                'id_tagihan_pembayaran' => $idTagihan,
                'nominal_bayar' => $pembayaran->nominal_bayar,
                'total_terbayar_setelah' => $runningTotals[$idTagihan],
                'created_at' => $pembayaran->created_at,
                'updated_at' => $pembayaran->updated_at,
            ]);
        }

        Schema::table('pembayaran_non_spp', function (Blueprint $table) {
            $table->dropForeign(['id_tagihan_pembayaran']);
        });

        Schema::table('pembayaran_non_spp', function (Blueprint $table) {
            // Retained only for legacy rows. New receipt headers use detail rows as
            // the source of truth and therefore leave this column null.
            $table->unsignedBigInteger('id_tagihan_pembayaran')->nullable()->change();
            $table->foreign('id_tagihan_pembayaran')
                ->references('id_tagihan_pembayaran')
                ->on('tagihan_pembayaran')
                ->restrictOnDelete();
        });

        Schema::table('tagihan_pembayaran', function (Blueprint $table) {
            $table->dropForeign(['id_siswa']);
            $table->dropForeign(['id_jenis_pembayaran']);
            $table->dropForeign(['id_tahun_ajaran']);

            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->restrictOnDelete();
            $table->foreign('id_jenis_pembayaran')
                ->references('id_jenis_pembayaran')
                ->on('jenis_pembayaran')
                ->restrictOnDelete();
            $table->foreign('id_tahun_ajaran')
                ->references('id_tahun_ajaran')
                ->on('tahun_ajaran')
                ->restrictOnDelete();
        });

        Schema::table('pembayaran_non_spp', function (Blueprint $table) {
            $table->dropForeign(['id_siswa']);
            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('detail_pembayaran_non_spp')->exists()) {
            throw new RuntimeException('Rollback pembayaran non-SPP tidak aman karena akan menghapus histori detail kwitansi.');
        }

        Schema::table('pembayaran_non_spp', function (Blueprint $table) {
            $table->dropForeign(['id_tagihan_pembayaran']);
            $table->dropForeign(['id_siswa']);
            $table->unsignedBigInteger('id_tagihan_pembayaran')->nullable(false)->change();
            $table->foreign('id_tagihan_pembayaran')
                ->references('id_tagihan_pembayaran')
                ->on('tagihan_pembayaran')
                ->cascadeOnDelete();
            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->cascadeOnDelete();
        });

        Schema::table('tagihan_pembayaran', function (Blueprint $table) {
            $table->dropForeign(['id_siswa']);
            $table->dropForeign(['id_jenis_pembayaran']);
            $table->dropForeign(['id_tahun_ajaran']);

            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->cascadeOnDelete();
            $table->foreign('id_jenis_pembayaran')
                ->references('id_jenis_pembayaran')
                ->on('jenis_pembayaran')
                ->cascadeOnDelete();
            $table->foreign('id_tahun_ajaran')
                ->references('id_tahun_ajaran')
                ->on('tahun_ajaran')
                ->cascadeOnDelete();
        });

        Schema::dropIfExists('detail_pembayaran_non_spp');
    }
};
