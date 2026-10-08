<?php

namespace App\Services;

use App\Models\Penerimaan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PembatalanPenerimaanService
{
    public function __construct(
        private readonly PembatalanPembayaranService $pembatalanPembayaranService,
        private readonly PembatalanPembayaranNonSppService $pembatalanPembayaranNonSppService,
    ) {}

    public function batalkan(User $user, Penerimaan $penerimaan, string $alasan): Penerimaan
    {
        return DB::transaction(function () use ($user, $penerimaan, $alasan): Penerimaan {
            $penerimaan = Penerimaan::query()
                ->with(['pembayaranSpp', 'pembayaranNonSpp'])
                ->whereKey($penerimaan->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($penerimaan->status === 'dibatalkan') {
                throw ValidationException::withMessages([
                    'penerimaan' => 'Kwitansi penerimaan ini sudah dibatalkan.',
                ]);
            }

            if ($penerimaan->pembayaranSpp) {
                $this->pembatalanPembayaranService->batalkan($user, $penerimaan->pembayaranSpp, $alasan);
            }

            if ($penerimaan->pembayaranNonSpp) {
                $this->pembatalanPembayaranNonSppService->batalkan($user, $penerimaan->pembayaranNonSpp, $alasan);
            }

            $penerimaan->update([
                'status' => 'dibatalkan',
                'alasan_pembatalan' => $alasan,
                'dibatalkan_oleh' => $user->id_user,
                'dibatalkan_pada' => now(),
            ]);

            return $penerimaan->fresh();
        });
    }
}
