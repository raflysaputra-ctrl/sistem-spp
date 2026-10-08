<?php

namespace App\Services;

use App\Models\Pengeluaran;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PembatalanPengeluaranService
{
    public function batalkan(User $user, Pengeluaran $pengeluaran, string $alasan): Pengeluaran
    {
        return DB::transaction(function () use ($user, $pengeluaran, $alasan): Pengeluaran {
            $pengeluaran = Pengeluaran::query()
                ->whereKey($pengeluaran->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($pengeluaran->status === 'dibatalkan') {
                throw ValidationException::withMessages([
                    'pengeluaran' => 'Pengeluaran ini sudah dibatalkan.',
                ]);
            }

            $pengeluaran->update([
                'status' => 'dibatalkan',
                'alasan_pembatalan' => $alasan,
                'dibatalkan_oleh' => $user->id_user,
                'dibatalkan_pada' => now(),
            ]);

            return $pengeluaran->fresh();
        });
    }
}
