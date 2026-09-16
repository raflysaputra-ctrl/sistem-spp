<?php

namespace App\Services;

use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TagihanSpp;
use App\Models\TahunAjaran;
use App\Models\TarifSpp;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LogicException;

class TagihanSppService
{
    /**
     * @return Collection<int, TagihanSpp>
     */
    public function generateUntukTahunAjaran(Siswa $siswa, TahunAjaran $tahunAjaran): Collection
    {
        $tanggalMulai = CarbonImmutable::parse($tahunAjaran->tanggal_mulai);
        $tanggalSelesai = CarbonImmutable::parse($tahunAjaran->tanggal_selesai);

        if (
            $tanggalMulai->format('m-d') !== '07-01'
            || $tanggalSelesai->format('m-d') !== '06-30'
            || $tanggalSelesai->year !== $tanggalMulai->year + 1
        ) {
            throw new LogicException('Tahun ajaran harus memiliki periode 1 Juli sampai 30 Juni.');
        }

        $tagihanSpp = collect();

        for ($periode = $tanggalMulai; $periode->lte($tanggalSelesai); $periode = $periode->addMonth()) {
            $tagihanSpp->push($this->generate($siswa, $periode->month, $periode->year));
        }

        return $tagihanSpp;
    }

    /**
     * @return Collection<int, TagihanSpp>
     */
    public function generateDenganKonteks(
        Siswa $siswa,
        TahunAjaran $tahunAjaran,
        SiswaKelas $siswaKelas,
        TarifSpp $tarif,
    ): Collection {
        $tanggalMulai = CarbonImmutable::parse($tahunAjaran->tanggal_mulai);
        $tanggalSelesai = CarbonImmutable::parse($tahunAjaran->tanggal_selesai);

        if (
            $tanggalMulai->format('m-d') !== '07-01'
            || $tanggalSelesai->format('m-d') !== '06-30'
            || $tanggalSelesai->year !== $tanggalMulai->year + 1
        ) {
            throw new LogicException('Tahun ajaran harus memiliki periode 1 Juli sampai 30 Juni.');
        }

        $tagihanSpp = collect();

        for ($periode = $tanggalMulai; $periode->lte($tanggalSelesai); $periode = $periode->addMonth()) {
            $tagihanSpp->push(TagihanSpp::query()->firstOrCreate(
                [
                    'id_siswa' => $siswa->id_siswa,
                    'bulan' => $periode->month,
                    'tahun' => $periode->year,
                ],
                [
                    'id_siswa_kelas' => $siswaKelas->id_siswa_kelas,
                    'id_tarif' => $tarif->id_tarif,
                    'nominal' => $tarif->nominal,
                    'status' => 'belum_bayar',
                ],
            ));
        }

        return $tagihanSpp;
    }

    public function generate(Siswa $siswa, int $bulan, int $tahun): TagihanSpp
    {
        if ($bulan < 1 || $bulan > 12) {
            throw new InvalidArgumentException('Bulan tagihan harus antara 1 dan 12.');
        }

        $tagihan = TagihanSpp::query()
            ->where('id_siswa', $siswa->id_siswa)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        if ($tagihan) {
            return $tagihan;
        }

        $periode = CarbonImmutable::create($tahun, $bulan, 1);
        $tahunAjaran = TahunAjaran::query()
            ->whereDate('tanggal_mulai', '<=', $periode)
            ->whereDate('tanggal_selesai', '>=', $periode)
            ->first();

        if (! $tahunAjaran) {
            throw new LogicException('Tahun ajaran untuk periode tagihan tidak ditemukan.');
        }

        $siswaKelas = SiswaKelas::query()
            ->with('kelas')
            ->where('id_siswa', $siswa->id_siswa)
            ->where('id_tahun_ajaran', $tahunAjaran->id_tahun_ajaran)
            ->first();

        if (! $siswaKelas) {
            throw new LogicException('Penempatan kelas siswa untuk tahun ajaran tagihan tidak ditemukan.');
        }

        $tarif = TarifSpp::query()
            ->where('id_tahun_ajaran', $tahunAjaran->id_tahun_ajaran)
            ->where('tingkat', $siswaKelas->kelas->tingkat)
            ->first();

        if (! $tarif) {
            throw new LogicException('Tarif SPP untuk tingkat dan tahun ajaran tagihan tidak ditemukan.');
        }

        return TagihanSpp::query()->firstOrCreate(
            [
                'id_siswa' => $siswa->id_siswa,
                'bulan' => $bulan,
                'tahun' => $tahun,
            ],
            [
                'id_siswa_kelas' => $siswaKelas->id_siswa_kelas,
                'id_tarif' => $tarif->id_tarif,
                'nominal' => $tarif->nominal,
                'status' => 'belum_bayar',
            ],
        );
    }
}
