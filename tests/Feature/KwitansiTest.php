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
use App\Services\PembayaranService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class KwitansiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_view_receipt(): void
    {
        $pembayaran = $this->buatPembayaran();

        $this->get(route('pembayaran.kwitansi.show', $pembayaran))
            ->assertRedirect(route('login'));
    }

    public function test_petugas_can_preview_receipt_with_transaction_details(): void
    {
        $pembayaran = $this->buatPembayaran();

        $this->actingAs($pembayaran->user)
            ->get(route('pembayaran.kwitansi.show', $pembayaran))
            ->assertOk()
            ->assertSee($pembayaran->no_kwitansi)
            ->assertSee('Siswa Kwitansi')
            ->assertSee('KWT-001')
            ->assertSee('X KWT 1')
            ->assertSee('Juli 2045')
            ->assertSee('Rp 150.000')
            ->assertSee($pembayaran->user->nama)
            ->assertSee('Cetak Kwitansi')
            ->assertSee(asset('images/cbi.png'), false)
            ->assertSee('alt="Logo SMK Informatika CBI"', false)
            ->assertSee('body * { visibility: hidden; }', false)
            ->assertSee('.receipt-paper, .receipt-paper * { visibility: visible; }', false);
    }

    private function buatPembayaran()
    {
        $user = User::factory()->create();
        $jurusan = Jurusan::create([
            'kode_jurusan' => 'KWT',
            'nama_jurusan' => 'Kwitansi',
        ]);
        $kelas = Kelas::create([
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 1,
            'rombel' => 1,
            'nama_kelas' => 'X KWT 1',
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
            'nipd' => 'KWT-001',
            'nama_siswa' => 'Siswa Kwitansi',
            'jenis_kelamin' => 'L',
            'angkatan' => 2045,
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
            'id_tarif' => $tarif->id_tarif,
            'bulan' => 7,
            'tahun' => 2045,
            'nominal' => 150000,
            'status' => 'belum_bayar',
        ]);

        return app(PembayaranService::class)->bayar($user, $siswa, [$tagihan->id_tagihan]);
    }
}
