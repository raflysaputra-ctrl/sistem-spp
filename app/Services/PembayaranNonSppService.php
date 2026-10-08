<?php

namespace App\Services;

use App\Models\DetailPembayaranNonSpp;
use App\Models\PembayaranNonSpp;
use App\Models\Siswa;
use App\Models\TagihanPembayaran;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PembayaranNonSppService
{
    public function __construct(private readonly PenetapanGelombangBamService $penetapanGelombangBamService) {}

    /**
     * @param  Collection<int, array{id_tagihan_pembayaran: int, nominal_bayar: int}>  $items
     */
    public function bayar(User $user, Siswa $siswa, Collection $items, ?string $kodePeriodeBam = null): PembayaranNonSpp
    {
        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Pilih minimal satu tagihan untuk dibayar.',
            ]);
        }

        if ($items->pluck('id_tagihan_pembayaran')->unique()->count() !== $items->count()) {
            throw ValidationException::withMessages([
                'items' => 'Satu tagihan hanya boleh dipilih satu kali dalam satu kwitansi.',
            ]);
        }

        return DB::transaction(function () use ($items, $siswa, $user, $kodePeriodeBam): PembayaranNonSpp {
            $idTagihan = $items->pluck('id_tagihan_pembayaran')->sort()->values();
            $tagihanById = TagihanPembayaran::query()
                ->with('jenisPembayaran')
                ->where('id_siswa', $siswa->id_siswa)
                ->whereIn('id_tagihan_pembayaran', $idTagihan)
                ->orderBy('id_tagihan_pembayaran')
                ->lockForUpdate()
                ->get()
                ->keyBy('id_tagihan_pembayaran');

            if ($tagihanById->count() !== $idTagihan->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Salah satu tagihan tidak ditemukan untuk siswa ini.',
                ]);
            }

            $tagihanBam = $tagihanById->filter(
                fn (TagihanPembayaran $tagihan): bool => $tagihan->jenisPembayaran->adalahBiayaAwalMasuk()
            );

            if ($tagihanBam->count() > 1) {
                throw ValidationException::withMessages([
                    'items' => 'Satu kwitansi hanya dapat memuat satu tagihan BAM.',
                ]);
            }

            if ($tagihanBam->isNotEmpty()) {
                $this->penetapanGelombangBamService->tetapkanSaatPembayaran(
                    $user,
                    $siswa,
                    $tagihanBam->sole(),
                    $kodePeriodeBam,
                );
            }

            $totalDibayarAktif = DetailPembayaranNonSpp::query()
                ->whereIn('id_tagihan_pembayaran', $idTagihan)
                ->whereHas('pembayaranNonSpp', fn ($query) => $query->where('status', 'aktif'))
                ->selectRaw('id_tagihan_pembayaran, SUM(nominal_bayar) as total')
                ->groupBy('id_tagihan_pembayaran')
                ->pluck('total', 'id_tagihan_pembayaran');

            $detail = [];
            $totalBayar = 0;

            foreach ($items as $item) {
                /** @var TagihanPembayaran $tagihan */
                $tagihan = $tagihanById->get($item['id_tagihan_pembayaran']);
                $sudahDibayar = (int) ($totalDibayarAktif->get($tagihan->id_tagihan_pembayaran, 0));
                $sisaTagihan = (int) $tagihan->total_tagihan - $sudahDibayar;
                $nominalBayar = $item['nominal_bayar'];

                if ($nominalBayar < 1) {
                    throw ValidationException::withMessages([
                        'items' => 'Nominal pembayaran harus lebih dari Rp 0.',
                    ]);
                }

                if ($tagihan->status === 'tidak_berlaku') {
                    throw ValidationException::withMessages([
                        'items' => "Tagihan {$tagihan->jenisPembayaran->nama_jenis} ini tidak berlaku untuk siswa.",
                    ]);
                }

                if ($tagihan->status === 'lunas' || $sisaTagihan <= 0) {
                    throw ValidationException::withMessages([
                        'items' => "Tagihan {$tagihan->jenisPembayaran->nama_jenis} sudah lunas.",
                    ]);
                }

                if ($nominalBayar > $sisaTagihan) {
                    throw ValidationException::withMessages([
                        'items' => "Nominal {$tagihan->jenisPembayaran->nama_jenis} tidak boleh melebihi sisa tagihan.",
                    ]);
                }

                // Issued bills keep their installment rule snapshot. Only the fixed
                // PTS/PAS codes may override a corrupted historical snapshot.
                $wajibLunas = $tagihan->jenisPembayaran->wajibLunasKarenaKodeTetap() || ! $tagihan->bisa_cicil;

                if ($wajibLunas && $nominalBayar !== $sisaTagihan) {
                    throw ValidationException::withMessages([
                        'items' => "Tagihan {$tagihan->jenisPembayaran->nama_jenis} wajib dibayar lunas sebesar Rp ".number_format($sisaTagihan, 0, ',', '.').'.',
                    ]);
                }

                if (! $wajibLunas && $sudahDibayar + $nominalBayar < (int) $tagihan->minimal_dp) {
                    throw ValidationException::withMessages([
                        'items' => "Total pembayaran aktif {$tagihan->jenisPembayaran->nama_jenis} harus mencapai minimal DP Rp ".number_format((int) $tagihan->minimal_dp, 0, ',', '.').'.',
                    ]);
                }

                $totalSetelahBayar = $sudahDibayar + $nominalBayar;
                $detail[] = [
                    'id_tagihan_pembayaran' => $tagihan->id_tagihan_pembayaran,
                    'nominal_bayar' => $nominalBayar,
                    'total_terbayar_setelah' => $totalSetelahBayar,
                ];
                $totalBayar += $nominalBayar;
            }

            $tanggalBayar = now();
            $pembayaran = PembayaranNonSpp::create([
                'no_kwitansi' => 'NSP-'.$tanggalBayar->format('Ymd').'-'.Str::ulid(),
                'id_siswa' => $siswa->id_siswa,
                'id_user' => $user->id_user,
                'tanggal_bayar' => $tanggalBayar,
                // This existing column now stores the total receipt amount.
                'nominal_bayar' => $totalBayar,
                'status' => 'aktif',
            ]);

            $pembayaran->detailPembayaranNonSpp()->createMany($detail);

            foreach ($detail as $item) {
                /** @var TagihanPembayaran $tagihan */
                $tagihan = $tagihanById->get($item['id_tagihan_pembayaran']);
                $tagihan->update([
                    'status' => $item['total_terbayar_setelah'] >= (int) $tagihan->total_tagihan ? 'lunas' : 'sebagian',
                ]);
            }

            return $pembayaran;
        });
    }
}
