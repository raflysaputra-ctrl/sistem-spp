<?php

namespace App\Services;

use App\Models\DetailPembayaranNonSpp;
use App\Models\JenisPembayaran;
use App\Models\PenetapanGelombangBam;
use App\Models\Siswa;
use App\Models\TagihanPembayaran;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenetapanGelombangBamService
{
    /**
     * This method must be called inside the payment transaction.
     */
    public function tetapkanSaatPembayaran(User $user, Siswa $siswa, TagihanPembayaran $tagihan, ?string $kodePeriode): void
    {
        $penetapan = PenetapanGelombangBam::query()
            ->where('id_siswa', $siswa->id_siswa)
            ->where('id_tahun_ajaran', $tagihan->id_tahun_ajaran)
            ->lockForUpdate()
            ->first();

        if ($penetapan) {
            if ($penetapan->kode_periode !== $tagihan->kode_periode) {
                throw ValidationException::withMessages([
                    'items' => 'Siswa ini hanya dapat membayar BAM '.JenisPembayaran::labelPeriode($penetapan->kode_periode).'.',
                ]);
            }

            return;
        }

        if ($kodePeriode !== $tagihan->kode_periode) {
            throw ValidationException::withMessages([
                'kode_periode_bam' => 'Pilih gelombang BAM yang sesuai sebelum memproses pembayaran.',
            ]);
        }

        $tagihanBam = $this->tagihanBamTerkunci($siswa, $tagihan->id_tahun_ajaran);
        $this->pastikanTagihanBamLengkap($tagihanBam, $tagihan->id_tagihan_pembayaran);
        $this->pastikanTidakAdaPembayaranAktif($tagihanBam);

        PenetapanGelombangBam::create([
            'id_siswa' => $siswa->id_siswa,
            'id_tahun_ajaran' => $tagihan->id_tahun_ajaran,
            'id_tagihan_pembayaran' => $tagihan->id_tagihan_pembayaran,
            'kode_periode' => $tagihan->kode_periode,
            'ditetapkan_oleh' => $user->id_user,
            'ditetapkan_pada' => now(),
        ]);

        $this->perbaruiStatusTagihan($tagihanBam, $tagihan->id_tagihan_pembayaran);
    }

    public function koreksi(User $user, Siswa $siswa, TahunAjaran $tahunAjaran, string $kodePeriode, string $alasan): void
    {
        DB::transaction(function () use ($user, $siswa, $tahunAjaran, $kodePeriode, $alasan): void {
            $penetapan = PenetapanGelombangBam::query()
                ->where('id_siswa', $siswa->id_siswa)
                ->where('id_tahun_ajaran', $tahunAjaran->id_tahun_ajaran)
                ->lockForUpdate()
                ->first();

            if (! $penetapan) {
                throw ValidationException::withMessages([
                    'kode_periode' => 'Gelombang BAM siswa ini belum ditetapkan oleh TU.',
                ]);
            }

            $tagihanBam = $this->tagihanBamTerkunci($siswa, $tahunAjaran->id_tahun_ajaran);
            $tagihanTujuan = $tagihanBam->firstWhere('kode_periode', $kodePeriode);
            $this->pastikanTagihanBamLengkap($tagihanBam, $tagihanTujuan?->id_tagihan_pembayaran);
            $this->pastikanTidakAdaPembayaranAktif($tagihanBam);

            $penetapan->update([
                'id_tagihan_pembayaran' => $tagihanTujuan->id_tagihan_pembayaran,
                'kode_periode' => $kodePeriode,
                'dikoreksi_oleh' => $user->id_user,
                'dikoreksi_pada' => now(),
                'alasan_koreksi' => $alasan,
            ]);

            $this->perbaruiStatusTagihan($tagihanBam, $tagihanTujuan->id_tagihan_pembayaran);
        });
    }

    /**
     * @return Collection<int, TagihanPembayaran>
     */
    private function tagihanBamTerkunci(Siswa $siswa, int $idTahunAjaran): Collection
    {
        return TagihanPembayaran::query()
            ->with('jenisPembayaran')
            ->where('id_siswa', $siswa->id_siswa)
            ->where('id_tahun_ajaran', $idTahunAjaran)
            ->whereHas('jenisPembayaran', fn ($query) => $query->where('kode_jenis', JenisPembayaran::KODE_JENIS_BIAYA_AWAL_MASUK))
            ->orderBy('id_tagihan_pembayaran')
            ->lockForUpdate()
            ->get();
    }

    /**
     * @param  Collection<int, TagihanPembayaran>  $tagihanBam
     */
    private function pastikanTagihanBamLengkap(Collection $tagihanBam, ?int $idTagihanPilihan): void
    {
        $periodeDiharapkan = collect(['gelombang_1', 'gelombang_2', 'gelombang_3']);

        if (
            ! $idTagihanPilihan
            || $tagihanBam->count() !== $periodeDiharapkan->count()
            || $tagihanBam->pluck('kode_periode')->sort()->values()->all() !== $periodeDiharapkan->sort()->values()->all()
        ) {
            throw ValidationException::withMessages([
                'items' => 'Tagihan BAM siswa belum lengkap untuk Gelombang 1, 2, dan 3.',
            ]);
        }
    }

    /**
     * @param  Collection<int, TagihanPembayaran>  $tagihanBam
     */
    private function pastikanTidakAdaPembayaranAktif(Collection $tagihanBam): void
    {
        if (DetailPembayaranNonSpp::query()
            ->whereIn('id_tagihan_pembayaran', $tagihanBam->pluck('id_tagihan_pembayaran'))
            ->whereHas('pembayaranNonSpp', fn ($query) => $query->where('status', 'aktif'))
            ->exists()) {
            throw ValidationException::withMessages([
                'items' => 'Gelombang BAM tidak dapat diubah karena masih ada pembayaran BAM aktif.',
            ]);
        }
    }

    /**
     * @param  Collection<int, TagihanPembayaran>  $tagihanBam
     */
    private function perbaruiStatusTagihan(Collection $tagihanBam, int $idTagihanAktif): void
    {
        foreach ($tagihanBam as $tagihan) {
            $tagihan->update([
                'status' => $tagihan->id_tagihan_pembayaran === $idTagihanAktif ? 'belum_bayar' : 'tidak_berlaku',
            ]);
        }
    }
}
