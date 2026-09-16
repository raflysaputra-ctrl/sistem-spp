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
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RiwayatPembayaranTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_view_payment_history(): void
    {
        $this->get(route('riwayat-pembayaran.index'))
            ->assertRedirect(route('login'));
    }

    public function test_petugas_can_view_payment_history_and_transaction_detail(): void
    {
        [$user, $pembayaran] = $this->buatPembayaranDuaPeriode();

        $this->actingAs($user)
            ->get(route('riwayat-pembayaran.index'))
            ->assertOk()
            ->assertSee($pembayaran->no_kwitansi)
            ->assertSee('Siswa Riwayat')
            ->assertSee('RWT-001')
            ->assertSee('Juli 2045, Agustus 2045')
            ->assertSee('Rp 300.000')
            ->assertSee($user->nama);

        $this->get(route('riwayat-pembayaran.show', $pembayaran))
            ->assertOk()
            ->assertSee($pembayaran->no_kwitansi)
            ->assertSee('Juli 2045')
            ->assertSee('Agustus 2045')
            ->assertSee('X RWT 1')
            ->assertSee('Rp 150.000')
            ->assertSee('Rp 300.000')
            ->assertSee('Lihat Kwitansi');
    }

    public function test_petugas_can_search_and_filter_payment_history(): void
    {
        [$user, $pembayaran] = $this->buatPembayaranDuaPeriode();
        $siswaLain = Siswa::create([
            'nipd' => 'RWT-002',
            'nama_siswa' => 'Siswa Riwayat Lain',
            'jenis_kelamin' => 'P',
            'angkatan' => 2045,
            'status_siswa' => 'aktif',
        ]);
        $pembayaranLain = Pembayaran::create([
            'no_kwitansi' => 'RWT-FILTER-002',
            'id_siswa' => $siswaLain->id_siswa,
            'id_user' => $user->id_user,
            'tanggal_bayar' => now()->subDays(7),
            'total_bayar' => 150000,
        ]);

        $this->actingAs($user)
            ->get(route('riwayat-pembayaran.index', ['cari' => $pembayaran->no_kwitansi]))
            ->assertOk()
            ->assertSee($pembayaran->no_kwitansi)
            ->assertDontSee($pembayaranLain->no_kwitansi);

        $this->get(route('riwayat-pembayaran.index', ['cari' => 'RWT-001']))
            ->assertOk()
            ->assertSee($pembayaran->no_kwitansi)
            ->assertDontSee($pembayaranLain->no_kwitansi);

        $this->get(route('riwayat-pembayaran.index', ['cari' => 'Siswa Riwayat']))
            ->assertOk()
            ->assertSee($pembayaran->no_kwitansi)
            ->assertSee($pembayaranLain->no_kwitansi);

        $tanggalHariIni = now()->toDateString();

        $this->get(route('riwayat-pembayaran.index', [
            'tanggal_mulai' => $tanggalHariIni,
            'tanggal_selesai' => $tanggalHariIni,
        ]))->assertOk()
            ->assertSee($pembayaran->no_kwitansi)
            ->assertDontSee($pembayaranLain->no_kwitansi);
    }

    /**
     * @return array{0: User, 1: Pembayaran}
     */
    private function buatPembayaranDuaPeriode(): array
    {
        $user = User::factory()->create();
        $jurusan = Jurusan::create([
            'kode_jurusan' => 'RWT',
            'nama_jurusan' => 'Riwayat',
        ]);
        $kelas = Kelas::create([
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 1,
            'rombel' => 1,
            'nama_kelas' => 'X RWT 1',
        ]);
        $tahunAjaran = TahunAjaran::create([
            'tahun_ajaran' => '2045/2046',
            'tanggal_mulai' => '2045-07-01',
            'tanggal_selesai' => '2046-06-30',
            'aktif' => true,
        ]);
        $tarif = TarifSpp::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tingkat' => 1,
            'nominal' => 150000,
        ]);
        $siswa = Siswa::create([
            'nipd' => 'RWT-001',
            'nama_siswa' => 'Siswa Riwayat',
            'jenis_kelamin' => 'L',
            'angkatan' => 2045,
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
            'tahun' => 2045,
            'nominal' => 150000,
            'status' => 'belum_bayar',
        ]));

        return [$user, app(PembayaranService::class)->bayar($user, $siswa, $tagihan->pluck('id_tagihan')->all())];
    }
}
