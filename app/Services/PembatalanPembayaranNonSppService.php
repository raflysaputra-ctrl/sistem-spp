<?php

namespace App\Services;

use App\Models\DetailPembayaranNonSpp;
use App\Models\PembayaranNonSpp;
use App\Models\TagihanPembayaran;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PembatalanPembayaranNonSppService
{
    public function batalkan(User $user, PembayaranNonSpp $pembayaran, string $alasan): PembayaranNonSpp
    {
        return DB::transaction(function () use ($user, $pembayaran, $alasan): PembayaranNonSpp {
            $pembayaran = PembayaranNonSpp::query()
                ->whereKey($pembayaran->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($pembayaran->status === 'dibatalkan') {
                throw ValidationException::withMessages([
                    'pembayaran' => 'Transaksi ini sudah dibatalkan.',
                ]);
            }

            $detail = DetailPembayaranNonSpp::query()
                ->where('id_pembayaran_non_spp', $pembayaran->id_pembayaran_non_spp)
                ->orderBy('id_tagihan_pembayaran')
                ->lockForUpdate()
                ->get();

            if ($detail->isEmpty()) {
                throw ValidationException::withMessages([
                    'pembayaran' => 'Rincian tagihan transaksi tidak ditemukan.',
                ]);
            }

            $idTagihan = $detail->pluck('id_tagihan_pembayaran')->sort()->values();
            $tagihan = TagihanPembayaran::query()
                ->with('jenisPembayaran')
                ->whereIn('id_tagihan_pembayaran', $idTagihan)
                ->orderBy('id_tagihan_pembayaran')
                ->lockForUpdate()
                ->get()
                ->keyBy('id_tagihan_pembayaran');

            if ($tagihan->count() !== $idTagihan->count()) {
                throw ValidationException::withMessages([
                    'pembayaran' => 'Tagihan transaksi tidak lagi lengkap dan pembatalan tidak dapat diproses.',
                ]);
            }

            $totalDibayarSetelahPembatalan = DetailPembayaranNonSpp::query()
                ->whereIn('id_tagihan_pembayaran', $idTagihan)
                ->where('id_pembayaran_non_spp', '!=', $pembayaran->id_pembayaran_non_spp)
                ->whereHas('pembayaranNonSpp', fn ($query) => $query->where('status', 'aktif'))
                ->selectRaw('id_tagihan_pembayaran, SUM(nominal_bayar) as total')
                ->groupBy('id_tagihan_pembayaran')
                ->pluck('total', 'id_tagihan_pembayaran');

            foreach ($tagihan as $item) {
                $total = (int) ($totalDibayarSetelahPembatalan->get($item->id_tagihan_pembayaran, 0));

                if ($item->bisa_cicil && $total > 0 && $total < (int) $item->minimal_dp) {
                    throw ValidationException::withMessages([
                        'pembayaran' => "Pembatalan tidak dapat diproses karena sisa pembayaran aktif {$item->jenisPembayaran->nama_jenis} menjadi di bawah minimal DP. Batalkan kwitansi cicilan berikutnya terlebih dahulu.",
                    ]);
                }
            }

            $pembayaran->update([
                'status' => 'dibatalkan',
                'alasan_pembatalan' => $alasan,
                'dibatalkan_oleh' => $user->id_user,
                'dibatalkan_pada' => now(),
            ]);

            foreach ($tagihan as $item) {
                $total = (int) ($totalDibayarSetelahPembatalan->get($item->id_tagihan_pembayaran, 0));
                $item->update([
                    'status' => $total >= (int) $item->total_tagihan
                        ? 'lunas'
                        : ($total > 0 ? 'sebagian' : 'belum_bayar'),
                ]);
            }

            return $pembayaran->fresh();
        });
    }
}
