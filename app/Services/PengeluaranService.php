<?php

namespace App\Services;

use App\Models\KategoriPengeluaran;
use App\Models\Pengeluaran;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PengeluaranService
{
    /**
     * @param  array{id_kategori_pengeluaran: int, tanggal_pengeluaran: string, keterangan: string, nominal: int}  $data
     */
    public function catat(User $user, array $data): Pengeluaran
    {
        return DB::transaction(function () use ($user, $data): Pengeluaran {
            $kategori = KategoriPengeluaran::query()
                ->whereKey($data['id_kategori_pengeluaran'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $kategori->aktif) {
                throw ValidationException::withMessages([
                    'id_kategori_pengeluaran' => 'Kategori pengeluaran sudah tidak aktif.',
                ]);
            }

            return Pengeluaran::create([
                ...$data,
                'id_user' => $user->id_user,
            ]);
        });
    }
}
