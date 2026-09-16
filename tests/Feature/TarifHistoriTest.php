<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TagihanSpp;
use App\Models\TahunAjaran;
use App\Models\TarifSpp;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TarifHistoriTest extends TestCase
{
    use DatabaseTransactions;

    public function test_existing_bills_preserve_snapshot_amount_when_rate_changes(): void
    {
        $jurusan = Jurusan::create([
            'kode_jurusan' => 'TRH',
            'nama_jurusan' => 'Tarif Histori',
        ]);

        $kelas = Kelas::create([
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 1,
            'rombel' => 1,
            'nama_kelas' => 'X TRH 1',
        ]);

        $tahunAjaran = TahunAjaran::create([
            'tahun_ajaran' => '2060/2061',
            'tanggal_mulai' => '2060-07-01',
            'tanggal_selesai' => '2061-06-30',
            'aktif' => true,
        ]);

        $tarifAwal = TarifSpp::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tingkat' => 1,
            'nominal' => 150000,
        ]);

        $siswa = Siswa::create([
            'nipd' => 'TRH-001',
            'nama_siswa' => 'Siswa Snapshot Tarif',
            'jenis_kelamin' => 'L',
            'angkatan' => 2026,
            'status_siswa' => 'aktif',
        ]);

        $siswaKelas = SiswaKelas::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
        ]);

        $tagihan = TagihanSpp::create([
            'id_siswa' => $siswa->id_siswa,
            'id_siswa_kelas' => $siswaKelas->id_siswa_kelas,
            'id_tarif' => $tarifAwal->id_tarif,
            'bulan' => 7,
            'tahun' => 2026,
            'nominal' => 150000,
            'status' => 'belum_bayar',
        ]);

        // When a new academic year gets created with a different rate for grade 1
        $taBerikutnya = TahunAjaran::create([
            'tahun_ajaran' => '2061/2062',
            'tanggal_mulai' => '2061-07-01',
            'tanggal_selesai' => '2062-06-30',
            'aktif' => false,
        ]);

        TarifSpp::create([
            'id_tahun_ajaran' => $taBerikutnya->id_tahun_ajaran,
            'tingkat' => 1,
            'nominal' => 200000,
        ]);

        // The old bill's nominal MUST remain 150000 (BR-14)
        $tagihanRefresh = $tagihan->fresh();
        $this->assertEquals(150000, $tagihanRefresh->nominal);
    }
}
