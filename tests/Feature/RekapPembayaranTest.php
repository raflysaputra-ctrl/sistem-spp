<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TagihanSpp;
use App\Models\TahunAjaran;
use App\Models\TarifSpp;
use App\Models\User;
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
            ]))
            ->assertOk()
            ->assertSee($pembayaran->no_kwitansi)
            ->assertSee('Juli 2037')
            ->assertDontSee('Agustus 2037')
            ->assertSee('15/08/2037 09:30')
            ->assertSee('Rp 150.000')
            ->assertSee('Bulan dan tahun selalu mengacu pada periode SPP.');
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
        $filters = ['bulan' => 7, 'tahun' => 2037];

        $excel = $this->actingAs($user)->get(route('rekap-pembayaran.export.excel', $filters));

        $excel->assertOk()
            ->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8')
            ->assertStreamed();
        $this->assertStringContainsString('Juli 2037', $excel->streamedContent());
        $this->assertStringContainsString($pembayaran->no_kwitansi, $excel->streamedContent());
        $this->assertStringNotContainsString('Agustus 2037', $excel->streamedContent());

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

    /**
     * @return array{0: User, 1: Pembayaran, 2: Jurusan, 3: Siswa}
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

        return [$user, $pembayaran, $jurusan, $siswa];
    }
}
