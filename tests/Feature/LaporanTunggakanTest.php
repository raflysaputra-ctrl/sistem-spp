<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TagihanSpp;
use App\Models\TahunAjaran;
use App\Models\TarifSpp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LaporanTunggakanTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_view_arrears_report(): void
    {
        $this->get(route('laporan-tunggakan.index'))
            ->assertRedirect(route('login'));
    }

    public function test_report_includes_only_unpaid_past_periods_for_non_graduated_active_students(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 15, 10, 0, 0, 'Asia/Jakarta'));

        try {
            $data = $this->buatFixtureTunggakan();

            $response = $this->actingAs($data['user'])
                ->get(route('laporan-tunggakan.index'));

            $response
                ->assertOk()
                ->assertSeeText('Tunggakan Aktif')
                ->assertSeeText('Tunggakan DKV')
                ->assertSeeText('X PPLG Tunggakan 2')
                ->assertSeeText('Rp 450.000')
                ->assertDontSeeText('Tunggakan Lulus')
                ->assertDontSeeText('Tunggakan Dihapus')
                ->assertSee(route('pembayaran.show', $data['siswaAktif']), false)
                ->assertSeeText('Bayar SPP');

            $tunggakan = $response->viewData('tunggakan')->keyBy('id_siswa');

            $this->assertCount(2, $tunggakan);
            $this->assertSame(2, (int) $tunggakan[$data['siswaAktif']->id_siswa]->jumlah_tagihan);
            $this->assertSame(300000, (int) $tunggakan[$data['siswaAktif']->id_siswa]->total_tunggakan);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_report_filters_historical_class_and_academic_year_and_exports_same_data(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 15, 10, 0, 0, 'Asia/Jakarta'));

        try {
            $data = $this->buatFixtureTunggakan();
            $filters = [
                'id_jurusan' => $data['jurusanPplg']->id_jurusan,
                'tingkat' => 1,
                'rombel' => 2,
                'id_tahun_ajaran' => $data['tahunAjaranSekarang']->id_tahun_ajaran,
                'cari' => $data['siswaAktif']->nipd,
            ];

            $response = $this->actingAs($data['user'])
                ->get(route('laporan-tunggakan.index', $filters));

            $response->assertOk()
                ->assertSeeText('Tunggakan Aktif')
                ->assertSeeText('Rp 150.000')
                ->assertDontSeeText('Tunggakan DKV');

            $tunggakan = $response->viewData('tunggakan')->keyBy('id_siswa');

            $this->assertCount(1, $tunggakan);
            $this->assertSame(1, (int) $tunggakan[$data['siswaAktif']->id_siswa]->jumlah_tagihan);
            $this->assertSame(150000, (int) $tunggakan[$data['siswaAktif']->id_siswa]->total_tunggakan);

            $excel = $this->actingAs($data['user'])
                ->get(route('laporan-tunggakan.export.excel', $filters));

            $excel->assertOk()
                ->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8')
                ->assertStreamed();
            $this->assertStringContainsString('Tunggakan SPP', $excel->streamedContent());
            $this->assertStringContainsString('Tunggakan Aktif', $excel->streamedContent());
            $this->assertStringContainsString('Jml Tagihan', $excel->streamedContent());
            $this->assertStringContainsString('Total Tunggakan', $excel->streamedContent());
            $this->assertStringContainsString('Total Nominal', $excel->streamedContent());
            $this->assertStringContainsString('ss:StyleID="border"', $excel->streamedContent());
            $this->assertStringNotContainsString('Tunggakan DKV', $excel->streamedContent());

            $pdf = $this->actingAs($data['user'])
                ->get(route('laporan-tunggakan.export.pdf', $filters));

            $pdf->assertOk()
                ->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $pdf->getContent());
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * @return array{
     *     user: User,
     *     jurusanPplg: Jurusan,
     *     tahunAjaranSekarang: TahunAjaran,
     *     siswaAktif: Siswa
     * }
     */
    private function buatFixtureTunggakan(): array
    {
        $user = User::factory()->create();
        $jurusanPplg = Jurusan::create([
            'kode_jurusan' => 'LTG-PPLG',
            'nama_jurusan' => 'PPLG Tunggakan',
        ]);
        $jurusanDkv = Jurusan::create([
            'kode_jurusan' => 'LTG-DKV',
            'nama_jurusan' => 'DKV Tunggakan',
        ]);
        $kelasPplg = Kelas::create([
            'id_jurusan' => $jurusanPplg->id_jurusan,
            'tingkat' => 1,
            'rombel' => 2,
            'nama_kelas' => 'X PPLG Tunggakan 2',
        ]);
        $kelasDkv = Kelas::create([
            'id_jurusan' => $jurusanDkv->id_jurusan,
            'tingkat' => 2,
            'rombel' => 3,
            'nama_kelas' => 'XI DKV Tunggakan 3',
        ]);
        $tahunAjaranLama = TahunAjaran::create([
            'tahun_ajaran' => '2025/2026',
            'tanggal_mulai' => '2025-07-01',
            'tanggal_selesai' => '2026-06-30',
        ]);
        $tahunAjaranSekarang = TahunAjaran::create([
            'tahun_ajaran' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'aktif' => true,
        ]);
        $tarifLama = TarifSpp::create([
            'id_tahun_ajaran' => $tahunAjaranLama->id_tahun_ajaran,
            'tingkat' => 1,
            'nominal' => 150000,
        ]);
        $tarifPplg = TarifSpp::create([
            'id_tahun_ajaran' => $tahunAjaranSekarang->id_tahun_ajaran,
            'tingkat' => 1,
            'nominal' => 150000,
        ]);
        $tarifDkv = TarifSpp::create([
            'id_tahun_ajaran' => $tahunAjaranSekarang->id_tahun_ajaran,
            'tingkat' => 2,
            'nominal' => 150000,
        ]);
        $siswaAktif = Siswa::create([
            'nipd' => 'LTG-001',
            'nama_siswa' => 'Tunggakan Aktif',
            'jenis_kelamin' => 'L',
            'angkatan' => 2026,
            'status_siswa' => 'aktif',
        ]);
        $siswaKelasLama = SiswaKelas::create([
            'id_siswa' => $siswaAktif->id_siswa,
            'id_kelas' => $kelasPplg->id_kelas,
            'id_tahun_ajaran' => $tahunAjaranLama->id_tahun_ajaran,
        ]);
        $siswaKelasSekarang = SiswaKelas::create([
            'id_siswa' => $siswaAktif->id_siswa,
            'id_kelas' => $kelasPplg->id_kelas,
            'id_tahun_ajaran' => $tahunAjaranSekarang->id_tahun_ajaran,
        ]);
        $siswaDkv = Siswa::create([
            'nipd' => 'LTG-002',
            'nama_siswa' => 'Tunggakan DKV',
            'jenis_kelamin' => 'P',
            'angkatan' => 2025,
            'status_siswa' => 'aktif',
        ]);
        $siswaKelasDkv = SiswaKelas::create([
            'id_siswa' => $siswaDkv->id_siswa,
            'id_kelas' => $kelasDkv->id_kelas,
            'id_tahun_ajaran' => $tahunAjaranSekarang->id_tahun_ajaran,
        ]);

        $this->buatTagihan($siswaAktif, $siswaKelasLama, $tarifLama, 7, 2025, 150000);
        $this->buatTagihan($siswaAktif, $siswaKelasSekarang, $tarifPplg, 8, 2026, 150000);
        $this->buatTagihan($siswaAktif, $siswaKelasSekarang, $tarifPplg, 9, 2026, 150000);
        $this->buatTagihan($siswaAktif, $siswaKelasSekarang, $tarifPplg, 10, 2026, 150000);
        $this->buatTagihan($siswaAktif, $siswaKelasSekarang, $tarifPplg, 6, 2026, 150000, 'lunas');
        $this->buatTagihan($siswaDkv, $siswaKelasDkv, $tarifDkv, 8, 2026, 150000);

        $siswaLulus = Siswa::create([
            'nipd' => 'LTG-003',
            'nama_siswa' => 'Tunggakan Lulus',
            'jenis_kelamin' => 'L',
            'angkatan' => 2024,
            'status_siswa' => 'lulus',
        ]);
        $siswaKelasLulus = SiswaKelas::create([
            'id_siswa' => $siswaLulus->id_siswa,
            'id_kelas' => $kelasPplg->id_kelas,
            'id_tahun_ajaran' => $tahunAjaranSekarang->id_tahun_ajaran,
        ]);
        $this->buatTagihan($siswaLulus, $siswaKelasLulus, $tarifPplg, 8, 2026, 150000);

        $siswaDihapus = Siswa::create([
            'nipd' => 'LTG-004',
            'nama_siswa' => 'Tunggakan Dihapus',
            'jenis_kelamin' => 'P',
            'angkatan' => 2026,
            'status_siswa' => 'aktif',
        ]);
        $siswaKelasDihapus = SiswaKelas::create([
            'id_siswa' => $siswaDihapus->id_siswa,
            'id_kelas' => $kelasPplg->id_kelas,
            'id_tahun_ajaran' => $tahunAjaranSekarang->id_tahun_ajaran,
        ]);
        $this->buatTagihan($siswaDihapus, $siswaKelasDihapus, $tarifPplg, 8, 2026, 150000);
        $siswaDihapus->delete();

        return compact('user', 'jurusanPplg', 'tahunAjaranSekarang', 'siswaAktif');
    }

    private function buatTagihan(
        Siswa $siswa,
        SiswaKelas $siswaKelas,
        TarifSpp $tarif,
        int $bulan,
        int $tahun,
        int $nominal,
        string $status = 'belum_bayar',
    ): TagihanSpp {
        return TagihanSpp::create([
            'id_siswa' => $siswa->id_siswa,
            'id_siswa_kelas' => $siswaKelas->id_siswa_kelas,
            'id_tarif' => $tarif->id_tarif,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'nominal' => $nominal,
            'status' => $status,
        ]);
    }
}
