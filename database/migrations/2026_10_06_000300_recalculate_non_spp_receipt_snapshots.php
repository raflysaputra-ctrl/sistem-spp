<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('detail_pembayaran_non_spp')) {
            return;
        }

        $detailPerTagihan = DB::table('detail_pembayaran_non_spp as detail')
            ->join('pembayaran_non_spp as pembayaran', 'pembayaran.id_pembayaran_non_spp', '=', 'detail.id_pembayaran_non_spp')
            ->select([
                'detail.id_detail_pembayaran_non_spp',
                'detail.id_pembayaran_non_spp',
                'detail.id_tagihan_pembayaran',
                'detail.nominal_bayar',
                'pembayaran.tanggal_bayar',
                'pembayaran.status',
                'pembayaran.dibatalkan_pada',
            ])
            ->orderBy('detail.id_tagihan_pembayaran')
            ->orderBy('pembayaran.tanggal_bayar')
            ->orderBy('detail.id_pembayaran_non_spp')
            ->get()
            ->groupBy('id_tagihan_pembayaran');

        foreach ($detailPerTagihan as $detailTagihan) {
            foreach ($detailTagihan as $detailSaatIni) {
                $totalPadaSaatItu = 0;

                foreach ($detailTagihan as $detailSebelumnya) {
                    $dibuatSebelumSaatIni = $detailSebelumnya->tanggal_bayar < $detailSaatIni->tanggal_bayar
                        || ($detailSebelumnya->tanggal_bayar === $detailSaatIni->tanggal_bayar
                            && $detailSebelumnya->id_pembayaran_non_spp <= $detailSaatIni->id_pembayaran_non_spp);
                    $masihAktifSaatItu = $detailSebelumnya->status === 'aktif'
                        || $detailSebelumnya->dibatalkan_pada === null
                        || $detailSebelumnya->dibatalkan_pada > $detailSaatIni->tanggal_bayar;

                    if ($dibuatSebelumSaatIni && $masihAktifSaatItu) {
                        $totalPadaSaatItu += (int) $detailSebelumnya->nominal_bayar;
                    }
                }

                DB::table('detail_pembayaran_non_spp')
                    ->where('id_detail_pembayaran_non_spp', $detailSaatIni->id_detail_pembayaran_non_spp)
                    ->update(['total_terbayar_setelah' => $totalPadaSaatItu]);
            }
        }
    }

    public function down(): void
    {
        // Historical receipt snapshots are intentionally irreversible.
    }
};
