<?php

namespace App\Services;

use App\Models\DetailPembayaran;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\TagihanSpp;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PembayaranService
{
    /**
     * @param  array<int, int|string>  $idTagihan
     */
    public function bayar(User $user, Siswa $siswa, array $idTagihan): Pembayaran
    {
        /** @var Collection<int, int> $idTagihanUnik */
        $idTagihanUnik = collect($idTagihan)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($idTagihanUnik->isEmpty()) {
            throw ValidationException::withMessages([
                'id_tagihan' => 'Pilih minimal satu tagihan untuk dibayar.',
            ]);
        }

        return DB::transaction(function () use ($idTagihanUnik, $siswa, $user) {
            $tagihanSpp = TagihanSpp::query()
                ->where('id_siswa', $siswa->id_siswa)
                ->whereIn('id_tagihan', $idTagihanUnik)
                ->lockForUpdate()
                ->get();

            if ($tagihanSpp->count() !== $idTagihanUnik->count()) {
                throw ValidationException::withMessages([
                    'id_tagihan' => 'Tagihan yang dipilih tidak ditemukan untuk siswa ini.',
                ]);
            }

            if ($tagihanSpp->contains('status', 'lunas')) {
                throw ValidationException::withMessages([
                    'id_tagihan' => 'Tagihan yang sudah lunas tidak dapat dibayar kembali.',
                ]);
            }

            if (DetailPembayaran::query()->whereIn('id_tagihan', $idTagihanUnik)->exists()) {
                throw ValidationException::withMessages([
                    'id_tagihan' => 'Tagihan yang dipilih sudah tercatat dalam transaksi pembayaran.',
                ]);
            }

            $periodeTerakhir = $tagihanSpp
                ->sortBy(fn (TagihanSpp $tagihan) => sprintf('%04d%02d', $tagihan->tahun, $tagihan->bulan))
                ->last();

            $adaTagihanSebelumnya = TagihanSpp::query()
                ->where('id_siswa', $siswa->id_siswa)
                ->where('status', 'belum_bayar')
                ->where(function ($query) use ($periodeTerakhir) {
                    $query->where('tahun', '<', $periodeTerakhir->tahun)
                        ->orWhere(function ($query) use ($periodeTerakhir) {
                            $query->where('tahun', $periodeTerakhir->tahun)
                                ->where('bulan', '<=', $periodeTerakhir->bulan);
                        });
                })
                ->whereNotIn('id_tagihan', $idTagihanUnik)
                ->lockForUpdate()
                ->exists();

            if ($adaTagihanSebelumnya) {
                throw ValidationException::withMessages([
                    'id_tagihan' => 'Pilih seluruh tagihan periode sebelumnya yang belum lunas dalam transaksi ini.',
                ]);
            }

            $tanggalBayar = now();
            $pembayaran = Pembayaran::create([
                'no_kwitansi' => 'SPP-'.$tanggalBayar->format('Ymd').'-'.Str::ulid(),
                'id_siswa' => $siswa->id_siswa,
                'id_user' => $user->id_user,
                'tanggal_bayar' => $tanggalBayar,
                'total_bayar' => $tagihanSpp->sum(fn (TagihanSpp $tagihan) => (int) $tagihan->nominal),
            ]);

            $pembayaran->detailPembayaran()->createMany(
                $tagihanSpp->map(fn (TagihanSpp $tagihan) => [
                    'id_tagihan' => $tagihan->id_tagihan,
                    'nominal_bayar' => $tagihan->nominal,
                ])->all(),
            );

            TagihanSpp::query()
                ->whereIn('id_tagihan', $idTagihanUnik)
                ->update([
                    'status' => 'lunas',
                    'tanggal_lunas' => $tanggalBayar,
                ]);

            return $pembayaran;
        });
    }
}
