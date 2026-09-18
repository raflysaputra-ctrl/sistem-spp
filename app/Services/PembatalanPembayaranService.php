<?php

namespace App\Services;

use App\Models\DetailPembayaran;
use App\Models\Pembayaran;
use App\Models\TagihanSpp;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PembatalanPembayaranService
{
    public function batalkan(User $user, Pembayaran $pembayaran, string $alasan): Pembayaran
    {
        return DB::transaction(function () use ($user, $pembayaran, $alasan) {
            $pembayaran = Pembayaran::query()
                ->whereKey($pembayaran->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($pembayaran->status === 'dibatalkan') {
                throw ValidationException::withMessages([
                    'pembayaran' => 'Transaksi ini sudah dibatalkan.',
                ]);
            }

            $detailPembayaran = DetailPembayaran::query()
                ->where('id_pembayaran', $pembayaran->id_pembayaran)
                ->lockForUpdate()
                ->get();

            $idTagihan = $detailPembayaran->pluck('id_tagihan')->sort()->values();

            $tagihanSpp = TagihanSpp::query()
                ->whereIn('id_tagihan', $idTagihan)
                ->orderBy('id_tagihan')
                ->lockForUpdate()
                ->get();

            if ($tagihanSpp->count() !== $idTagihan->count()) {
                throw ValidationException::withMessages([
                    'pembayaran' => 'Tagihan transaksi tidak lagi lengkap dan pembatalan tidak dapat diproses.',
                ]);
            }

            $pembayaran->update([
                'status' => 'dibatalkan',
                'alasan_pembatalan' => $alasan,
                'dibatalkan_oleh' => $user->id_user,
                'dibatalkan_pada' => now(),
            ]);

            foreach ($tagihanSpp as $tagihan) {
                $tagihan->update([
                    'status' => 'belum_bayar',
                    'tanggal_lunas' => null,
                ]);
            }

            return $pembayaran->fresh();
        });
    }
}
