<?php

namespace App\Services;

use App\Models\Penerimaan;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PenerimaanService
{
    public function __construct(
        private readonly PembayaranService $pembayaranService,
        private readonly PembayaranNonSppService $pembayaranNonSppService,
    ) {}

    /**
     * @param  array<int, int|string>  $idTagihanSpp
     * @param  Collection<int, array{id_tagihan_pembayaran: int, nominal_bayar: int}>  $tagihanNonSpp
     */
    public function bayar(
        User $user,
        Siswa $siswa,
        array $idTagihanSpp,
        Collection $tagihanNonSpp,
        ?string $kodePeriodeBam = null,
    ): Penerimaan {
        if ($idTagihanSpp === [] && $tagihanNonSpp->isEmpty()) {
            throw ValidationException::withMessages([
                'penerimaan' => 'Pilih minimal satu tagihan untuk dibayar.',
            ]);
        }

        return DB::transaction(function () use ($user, $siswa, $idTagihanSpp, $tagihanNonSpp, $kodePeriodeBam): Penerimaan {
            $tanggalBayar = now();
            $penerimaan = Penerimaan::create([
                'no_kwitansi' => 'PNR-'.$tanggalBayar->format('Ymd').'-'.Str::ulid(),
                'id_siswa' => $siswa->id_siswa,
                'id_user' => $user->id_user,
                'tanggal_bayar' => $tanggalBayar,
                'total_bayar' => 0,
                'status' => 'aktif',
            ]);

            $totalBayar = 0;

            if ($idTagihanSpp !== []) {
                $pembayaranSpp = $this->pembayaranService->bayar($user, $siswa, $idTagihanSpp);
                $pembayaranSpp->update(['id_penerimaan' => $penerimaan->id_penerimaan]);
                $totalBayar += (int) $pembayaranSpp->total_bayar;
            }

            if ($tagihanNonSpp->isNotEmpty()) {
                $pembayaranNonSpp = $this->pembayaranNonSppService->bayar(
                    $user,
                    $siswa,
                    $tagihanNonSpp,
                    $kodePeriodeBam,
                );
                $pembayaranNonSpp->update(['id_penerimaan' => $penerimaan->id_penerimaan]);
                $totalBayar += (int) $pembayaranNonSpp->nominal_bayar;
            }

            $penerimaan->update(['total_bayar' => $totalBayar]);

            return $penerimaan->fresh();
        });
    }
}
