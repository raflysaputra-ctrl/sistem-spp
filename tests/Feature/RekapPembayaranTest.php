<?php

namespace Tests\Feature;

use App\Models\DetailPembayaranNonSpp;
use App\Models\JenisPembayaran;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\PembayaranNonSpp;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TagihanPembayaran;
use App\Models\TagihanSpp;
use App\Models\TahunAjaran;
use App\Models\TarifSpp;
use App\Models\User;
use App\Services\PembatalanPembayaranService;
use App\Services\PembayaranService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RekapPembayaranTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_view_payment_recap(): void
    {
        $this->get(route('rekap-pembayaran.index'))
            ->assertRedirect(route('login'));
    }

    public function test_recap_filters_by_spp_period_and_keeps_transaction_date_separate(): void
    {
        [$user, $pembayaran, $jurusan, $siswa] = $this->buatPembayaranDuaPeriode();

        $this->actingAs($user)
            ->get(route('rekap-pembayaran.index', [
                'bulan' => 7,
                'tahun' => 2037,
                'id_jurusan' => $jurusan->id_jurusan,
                'tingkat' => 2,
                'rombel' => 5,
                'cari' => $siswa->nipd,
                'tanggal_mulai' => '2037-08-01',
                'tanggal_selesai' => '2037-08-31',
            ]))
            ->assertOk()
            ->assertSee($pembayaran->no_kwitansi)
            ->assertSee('Juli 2037')
            ->assertDontSee('Agustus 2037')
            ->assertSee('15/08/2037 09:30')
            ->assertSee('Rp 150.000')
            ->assertSee('Periode SPP mengacu pada bulan dan tahun tagihan.')
            ->assertSee('Tanggal Transaksi: Mulai')
            ->assertSee('Tanggal Transaksi: Selesai');
    }

    public function test_recap_searches_students_by_name_or_nis(): void
    {
        [$user, $pembayaran, , $siswa] = $this->buatPembayaranDuaPeriode();

        $this->actingAs($user)
            ->get(route('rekap-pembayaran.index', ['cari' => $siswa->nama_siswa]))
            ->assertOk()
            ->assertSee($pembayaran->no_kwitansi)
            ->assertSeeHtml('name="cari"');

        $this->get(route('rekap-pembayaran.index', ['cari' => 'tidak-ditemukan']))
            ->assertOk()
            ->assertDontSee($pembayaran->no_kwitansi)
            ->assertSee('Tidak ada pembayaran yang sesuai dengan filter rekap.');
    }

    public function test_petugas_can_export_filtered_recap_to_excel_and_pdf(): void
    {
        [$user, $pembayaran] = $this->buatPembayaranDuaPeriode();
        $filters = [
            'bulan' => 7,
            'tahun' => 2037,
            'tanggal_mulai' => '2037-08-15',
            'tanggal_selesai' => '2037-08-15',
            'status' => 'aktif',
        ];

        $excel = $this->actingAs($user)->get(route('rekap-pembayaran.export.excel', $filters));

        $excel->assertOk()
            ->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8')
            ->assertStreamed();
        $this->assertStringContainsString('Juli 2037', $excel->streamedContent());
        $this->assertStringContainsString($pembayaran->no_kwitansi, $excel->streamedContent());
        $this->assertStringNotContainsString('Agustus 2037', $excel->streamedContent());
        $this->assertStringContainsString('ss:StyleID="border"', $excel->streamedContent());
        $this->assertStringContainsString('ss:StyleID="header"', $excel->streamedContent());

        $pdf = $this->actingAs($user)->get(route('rekap-pembayaran.export.pdf', $filters));

        $pdf->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_excel_export_neutralizes_formula_like_student_data(): void
    {
        [$user, , , $siswa] = $this->buatPembayaranDuaPeriode();
        $siswa->update(['nama_siswa' => '=HYPERLINK("https://example.test")']);

        $excel = $this->actingAs($user)->get(route('rekap-pembayaran.export.excel', [
            'bulan' => 7,
            'tahun' => 2037,
        ]));

        $this->assertStringContainsString("'=HYPERLINK(&quot;https://example.test&quot;)", $excel->streamedContent());
    }

    public function test_recap_defaults_to_active_and_can_show_cancelled_transactions(): void
    {
        [$user, $pembayaran] = $this->buatPembayaranDuaPeriode();
        app(PembatalanPembayaranService::class)->batalkan($user, $pembayaran, 'Salah input.');

        $this->actingAs($user)
            ->get(route('rekap-pembayaran.index'))
            ->assertOk()
            ->assertDontSee($pembayaran->no_kwitansi)
            ->assertSeeText('Total Penerimaan Aktif');

        $this->get(route('rekap-pembayaran.index', ['status' => 'dibatalkan']))
            ->assertOk()
            ->assertSee($pembayaran->no_kwitansi)
            ->assertSeeText('Dibatalkan')
            ->assertSeeText('Transaksi Dibatalkan');

        $this->get(route('rekap-pembayaran.index', [
            'bulan' => 7,
            'tahun' => 2037,
            'tanggal_mulai' => '2037-08-15',
            'tanggal_selesai' => '2037-08-15',
            'status' => 'dibatalkan',
        ]))
            ->assertOk()
            ->assertSee($pembayaran->no_kwitansi)
            ->assertSee('Juli 2037')
            ->assertDontSee('Agustus 2037');
    }

    public function test_recap_distinguishes_spp_and_a_multi_bill_non_spp_receipt(): void
    {
        [$user, $pembayaranSpp, , $siswa, $tahunAjaran] = $this->buatPembayaranDuaPeriode();
        $pembayaranNonSpp = $this->buatPenerimaanNonSpp($user, $siswa, $tahunAjaran);

        $response = $this->actingAs($user)
            ->get(route('rekap-pembayaran.index'))
            ->assertOk()
            ->assertSeeText([
                'Rekap Penerimaan',
                'Penerimaan SPP',
                'Penerimaan Non-SPP',
                $pembayaranSpp->no_kwitansi,
                $pembayaranNonSpp->no_kwitansi,
                'PTS Rekap',
                'PKL Rekap',
                'Rp 600.000',
            ]);

        $this->assertSame(1, substr_count($response->getContent(), $pembayaranNonSpp->no_kwitansi));

        $this->get(route('rekap-pembayaran.index', ['sumber' => 'spp']))
            ->assertOk()
            ->assertSee($pembayaranSpp->no_kwitansi)
            ->assertDontSee($pembayaranNonSpp->no_kwitansi);

        $this->get(route('rekap-pembayaran.index', ['sumber' => 'non_spp']))
            ->assertOk()
            ->assertSee($pembayaranNonSpp->no_kwitansi)
            ->assertDontSee($pembayaranSpp->no_kwitansi);

        $excel = $this->get(route('rekap-pembayaran.export.excel', ['sumber' => 'semua']));
        $this->assertStringContainsString($pembayaranSpp->no_kwitansi, $excel->streamedContent());
        $this->assertStringNotContainsString($pembayaranNonSpp->no_kwitansi, $excel->streamedContent());
    }

    public function test_recap_filters_cancelled_non_spp_receipts_and_hides_actions_from_kepala_sekolah(): void
    {
        [$user, , , $siswa, $tahunAjaran] = $this->buatPembayaranDuaPeriode();
        $pembayaranNonSpp = $this->buatPenerimaanNonSpp($user, $siswa, $tahunAjaran);
        $pembayaranNonSpp->update(['status' => 'dibatalkan']);

        $this->actingAs($user)
            ->get(route('rekap-pembayaran.index', ['sumber' => 'non_spp']))
            ->assertOk()
            ->assertDontSee($pembayaranNonSpp->no_kwitansi);

        $kepalaSekolah = User::factory()->kepalaSekolah()->create();
        $this->actingAs($kepalaSekolah)
            ->get(route('rekap-pembayaran.index', [
                'sumber' => 'non_spp',
                'status' => 'dibatalkan',
            ]))
            ->assertOk()
            ->assertSeeText([$pembayaranNonSpp->no_kwitansi, 'Dibatalkan'])
            ->assertDontSee('href="'.route('riwayat-pembayaran-non-spp.show', $pembayaranNonSpp).'"', false);
    }

    /**
     * @return array{0: User, 1: Pembayaran, 2: Jurusan, 3: Siswa, 4: TahunAjaran}
     */
    private function buatPembayaranDuaPeriode(): array
    {
        $user = User::factory()->create();
        $jurusan = Jurusan::create([
            'kode_jurusan' => 'RKP',
            'nama_jurusan' => 'Rekap',
        ]);
        $kelas = Kelas::create([
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 2,
            'rombel' => 5,
            'nama_kelas' => 'XI RKP 5',
        ]);
        $tahunAjaran = TahunAjaran::create([
            'tahun_ajaran' => '2037/2038',
            'tanggal_mulai' => '2037-07-01',
            'tanggal_selesai' => '2038-06-30',
            'aktif' => true,
        ]);
        $tarif = TarifSpp::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tingkat' => 2,
            'nominal' => 150000,
        ]);
        $siswa = Siswa::create([
            'nipd' => 'RKP-001',
            'nama_siswa' => 'Siswa Rekap',
            'jenis_kelamin' => 'P',
            'angkatan' => 2036,
            'status_siswa' => 'aktif',
        ]);
        $siswaKelas = SiswaKelas::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
        ]);
        $tagihan = collect([7, 8])->map(fn (int $bulan) => TagihanSpp::create([
            'id_siswa' => $siswa->id_siswa,
            'id_siswa_kelas' => $siswaKelas->id_siswa_kelas,
            'id_tarif' => $tarif->id_tarif,
            'bulan' => $bulan,
            'tahun' => 2037,
            'nominal' => 150000,
            'status' => 'belum_bayar',
        ]));

        Carbon::setTestNow(Carbon::parse('2037-08-15 09:30:00', 'Asia/Jakarta'));

        try {
            $pembayaran = app(PembayaranService::class)->bayar($user, $siswa, $tagihan->pluck('id_tagihan')->all());
        } finally {
            Carbon::setTestNow();
        }

        return [$user, $pembayaran, $jurusan, $siswa, $tahunAjaran];
    }

    private function buatPenerimaanNonSpp(User $user, Siswa $siswa, TahunAjaran $tahunAjaran): PembayaranNonSpp
    {
        $pts = JenisPembayaran::create([
            'kode_jenis' => 'RKP_PTS',
            'nama_jenis' => 'PTS Rekap',
            'aturan_pembayaran' => JenisPembayaran::ATURAN_SEKALI_BAYAR,
            'tipe_periode' => JenisPembayaran::TIPE_PERIODE_SEMESTER,
            'aktif' => true,
        ]);
        $pkl = JenisPembayaran::create([
            'kode_jenis' => 'RKP_PKL',
            'nama_jenis' => 'PKL Rekap',
            'aturan_pembayaran' => JenisPembayaran::ATURAN_CICILAN,
            'tipe_periode' => JenisPembayaran::TIPE_PERIODE_TAHUNAN,
            'aktif' => true,
        ]);
        $tagihanPts = TagihanPembayaran::create([
            'id_siswa' => $siswa->id_siswa,
            'id_jenis_pembayaran' => $pts->id_jenis_pembayaran,
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'total_tagihan' => 75_000,
            'minimal_dp' => 0,
            'bisa_cicil' => false,
            'kode_periode' => 'semester_1',
            'periode_keterangan' => 'Semester 1',
            'status' => 'lunas',
            'created_by' => $user->id_user,
        ]);
        $tagihanPkl = TagihanPembayaran::create([
            'id_siswa' => $siswa->id_siswa,
            'id_jenis_pembayaran' => $pkl->id_jenis_pembayaran,
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'total_tagihan' => 225_000,
            'minimal_dp' => 100_000,
            'bisa_cicil' => true,
            'kode_periode' => 'tahunan',
            'periode_keterangan' => 'Satu kali per tahun ajaran',
            'status' => 'lunas',
            'created_by' => $user->id_user,
        ]);
        $pembayaran = PembayaranNonSpp::create([
            'no_kwitansi' => 'NSP-RKP-001',
            'id_siswa' => $siswa->id_siswa,
            'id_user' => $user->id_user,
            'tanggal_bayar' => '2037-08-15 10:00:00',
            'nominal_bayar' => 300_000,
            'status' => 'aktif',
        ]);

        DetailPembayaranNonSpp::create([
            'id_pembayaran_non_spp' => $pembayaran->id_pembayaran_non_spp,
            'id_tagihan_pembayaran' => $tagihanPts->id_tagihan_pembayaran,
            'nominal_bayar' => 75_000,
            'total_terbayar_setelah' => 75_000,
        ]);
        DetailPembayaranNonSpp::create([
            'id_pembayaran_non_spp' => $pembayaran->id_pembayaran_non_spp,
            'id_tagihan_pembayaran' => $tagihanPkl->id_tagihan_pembayaran,
            'nominal_bayar' => 225_000,
            'total_terbayar_setelah' => 225_000,
        ]);

        return $pembayaran;
    }
}
