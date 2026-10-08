<?php

namespace Tests\Feature;

use App\Models\JenisPembayaran;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Penerimaan;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TagihanPembayaran;
use App\Models\TagihanSpp;
use App\Models\TahunAjaran;
use App\Models\TarifSpp;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PenerimaanGabunganTest extends TestCase
{
    use DatabaseTransactions;

    public function test_tu_creates_one_receipt_for_spp_and_non_spp(): void
    {
        [$admin, $tu, $siswa, $tagihanSpp, $tagihanNonSpp] = $this->buatKonteks();

        $this->actingAs($tu)
            ->get(route('penerimaan.show', $siswa))
            ->assertOk()
            ->assertSee('id="filter_jenis"', false)
            ->assertSee('inputmode="numeric"', false)
            ->assertSeeText(['Input Pembayaran', 'SPP', 'PKL']);

        $this->actingAs($tu)->post(route('penerimaan.store', $siswa), [
            'id_tagihan_spp' => [$tagihanSpp->id_tagihan],
            'items' => [[
                'id_tagihan_pembayaran' => $tagihanNonSpp->id_tagihan_pembayaran,
                'selected' => 1,
                'nominal_bayar' => 800_000,
            ]],
        ])->assertRedirect();

        $penerimaan = Penerimaan::query()->sole();
        $this->assertMatchesRegularExpression('/^PNR-\d{8}-[0-9A-HJKMNP-TV-Z]{26}$/', $penerimaan->no_kwitansi);
        $this->assertSame(950_000, (int) $penerimaan->total_bayar);
        $this->assertNotNull($penerimaan->pembayaranSpp);
        $this->assertNotNull($penerimaan->pembayaranNonSpp);
        $this->assertSame('lunas', $tagihanSpp->fresh()->status);
        $this->assertSame('sebagian', $tagihanNonSpp->fresh()->status);

        $this->actingAs($tu)->get(route('penerimaan.kwitansi', $penerimaan))
            ->assertOk()
            ->assertSeeText(['Kwitansi Pembayaran', $penerimaan->no_kwitansi, 'SPP', 'PKL', 'Rp 950.000']);
    }

    public function test_spp_section_keeps_paid_bills_and_marks_arrears(): void
    {
        [$admin, $tu, $siswa, $tagihanTunggakan] = $this->buatKonteks();
        $tagihanTunggakan->update([
            'bulan' => now()->subMonthNoOverflow()->month,
            'tahun' => now()->subMonthNoOverflow()->year,
        ]);
        $tagihanLunas = TagihanSpp::create([
            'id_siswa' => $tagihanTunggakan->id_siswa,
            'id_siswa_kelas' => $tagihanTunggakan->id_siswa_kelas,
            'id_tarif' => $tagihanTunggakan->id_tarif,
            'bulan' => 7,
            'tahun' => 2098,
            'nominal' => 150_000,
            'status' => 'lunas',
            'tanggal_lunas' => now(),
        ]);

        $this->actingAs($tu)->get(route('penerimaan.show', $siswa))
            ->assertOk()
            ->assertSeeText(['Tunggakan', 'Lunas'])
            ->assertSee('data-status="belum_bayar"', false)
            ->assertSee('value="'.$tagihanLunas->id_tagihan.'"', false);
    }

    public function test_combined_receipt_rolls_back_when_non_spp_validation_fails(): void
    {
        [, $tu, $siswa, $tagihanSpp, $tagihanNonSpp] = $this->buatKonteks();

        $this->actingAs($tu)
            ->from(route('penerimaan.show', $siswa))
            ->post(route('penerimaan.store', $siswa), [
                'id_tagihan_spp' => [$tagihanSpp->id_tagihan],
                'items' => [[
                    'id_tagihan_pembayaran' => $tagihanNonSpp->id_tagihan_pembayaran,
                    'selected' => 1,
                    'nominal_bayar' => 700_000,
                ]],
            ])
            ->assertRedirect(route('penerimaan.show', $siswa))
            ->assertSessionHasErrors('items');

        $this->assertSame(0, Penerimaan::query()->count());
        $this->assertSame('belum_bayar', $tagihanSpp->fresh()->status);
        $this->assertSame('belum_bayar', $tagihanNonSpp->fresh()->status);
    }

    public function test_admin_cancels_every_payment_in_a_combined_receipt(): void
    {
        [$admin, $tu, $siswa, $tagihanSpp, $tagihanNonSpp] = $this->buatKonteks();

        $this->actingAs($tu)->post(route('penerimaan.store', $siswa), [
            'id_tagihan_spp' => [$tagihanSpp->id_tagihan],
            'items' => [[
                'id_tagihan_pembayaran' => $tagihanNonSpp->id_tagihan_pembayaran,
                'selected' => 1,
                'nominal_bayar' => 800_000,
            ]],
        ])->assertRedirect();

        $penerimaan = Penerimaan::query()->sole();
        $this->actingAs($admin)->patch(route('penerimaan.batalkan', $penerimaan), [
            'alasan_pembatalan' => 'Kwitansi gabungan salah input.',
            'password' => 'password',
        ])->assertRedirect(route('penerimaan.detail', $penerimaan));

        $this->assertSame('dibatalkan', $penerimaan->fresh()->status);
        $this->assertSame('dibatalkan', $penerimaan->pembayaranSpp->fresh()->status);
        $this->assertSame('dibatalkan', $penerimaan->pembayaranNonSpp->fresh()->status);
        $this->assertSame('belum_bayar', $tagihanSpp->fresh()->status);
        $this->assertSame('belum_bayar', $tagihanNonSpp->fresh()->status);
    }

    public function test_student_uploads_one_photo_for_a_combined_receipt(): void
    {
        Storage::fake('local');
        [, $tu, $siswa, $tagihanSpp, $tagihanNonSpp] = $this->buatKonteks();

        $this->actingAs($tu)->post(route('penerimaan.store', $siswa), [
            'id_tagihan_spp' => [$tagihanSpp->id_tagihan],
            'items' => [[
                'id_tagihan_pembayaran' => $tagihanNonSpp->id_tagihan_pembayaran,
                'selected' => 1,
                'nominal_bayar' => 800_000,
            ]],
        ])->assertRedirect();

        $penerimaan = Penerimaan::query()->sole();
        $akunSiswa = User::create([
            'nama' => $siswa->nama_siswa,
            'username' => 'siswa-'.$siswa->nipd,
            'password' => 'password',
            'role' => 'siswa',
            'id_siswa' => $siswa->id_siswa,
        ]);

        $this->actingAs($akunSiswa, 'siswa')->post(route('siswa.kwitansi.store'), [
            'id_penerimaan' => $penerimaan->id_penerimaan,
            'foto' => UploadedFile::fake()->image('kwitansi-gabungan.jpg', 800, 600),
        ])->assertRedirect(route('siswa.status'));

        $this->assertDatabaseHas('arsip_kwitansi_siswa', [
            'id_siswa' => $siswa->id_siswa,
            'id_pembayaran' => null,
            'id_penerimaan' => $penerimaan->id_penerimaan,
        ]);
        $this->actingAs($akunSiswa, 'siswa')->get(route('siswa.status'))
            ->assertOk()
            ->assertSeeText($penerimaan->no_kwitansi);
    }

    /**
     * @return array{User, User, Siswa, TagihanSpp, TagihanPembayaran}
     */
    private function buatKonteks(): array
    {
        $suffix = strtoupper(substr(str_replace('-', '', (string) Str::uuid()), 0, 8));
        $admin = User::factory()->admin()->create();
        $tu = User::factory()->tu()->create();
        $jurusan = Jurusan::create(['kode_jurusan' => "PG{$suffix}", 'nama_jurusan' => "Penerimaan {$suffix}"]);
        $kelas = Kelas::create(['id_jurusan' => $jurusan->id_jurusan, 'tingkat' => 2, 'rombel' => 1, 'nama_kelas' => "XI PG {$suffix}"]);
        $tahunAjaran = TahunAjaran::create([
            'tahun_ajaran' => '2098/2099',
            'tanggal_mulai' => '2098-07-01',
            'tanggal_selesai' => '2099-06-30',
            'aktif' => true,
        ]);
        $tarif = TarifSpp::create(['id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran, 'tingkat' => 2, 'nominal' => 150_000]);
        $siswa = Siswa::factory()->create(['nipd' => "PG-{$suffix}", 'status_siswa' => 'aktif']);
        $siswaKelas = SiswaKelas::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
        ]);
        $tagihanSpp = TagihanSpp::create([
            'id_siswa' => $siswa->id_siswa,
            'id_siswa_kelas' => $siswaKelas->id_siswa_kelas,
            'id_tarif' => $tarif->id_tarif,
            'bulan' => 7,
            'tahun' => 2098,
            'nominal' => 150_000,
            'status' => 'belum_bayar',
        ]);
        $jenis = JenisPembayaran::create([
            'kode_jenis' => "PKL_{$suffix}",
            'nama_jenis' => 'PKL',
            'aturan_pembayaran' => JenisPembayaran::ATURAN_CICILAN,
            'tipe_periode' => JenisPembayaran::TIPE_PERIODE_TAHUNAN,
            'aktif' => true,
        ]);
        $tagihanNonSpp = TagihanPembayaran::create([
            'id_siswa' => $siswa->id_siswa,
            'id_jenis_pembayaran' => $jenis->id_jenis_pembayaran,
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'total_tagihan' => 1_300_000,
            'minimal_dp' => 800_000,
            'bisa_cicil' => true,
            'kode_periode' => 'tahunan',
            'periode_keterangan' => 'Tahunan',
            'status' => 'belum_bayar',
            'created_by' => $admin->id_user,
        ]);

        return [$admin, $tu, $siswa, $tagihanSpp, $tagihanNonSpp];
    }
}
