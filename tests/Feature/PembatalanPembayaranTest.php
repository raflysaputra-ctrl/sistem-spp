<?php

namespace Tests\Feature;

use App\Models\DetailPembayaran;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TagihanSpp;
use App\Models\TahunAjaran;
use App\Models\TarifSpp;
use App\Models\User;
use App\Services\PembatalanPembayaranService;
use App\Services\PembayaranService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use RuntimeException;
use Tests\TestCase;

class PembatalanPembayaranTest extends TestCase
{
    use DatabaseTransactions;

    public function test_cancellation_requires_reason_and_correct_password(): void
    {
        [$user, $pembayaran] = $this->buatPembayaran();

        $this->actingAs($user)
            ->from(route('riwayat-pembayaran.show', $pembayaran))
            ->patch(route('riwayat-pembayaran.batalkan', $pembayaran), [])
            ->assertRedirect(route('riwayat-pembayaran.show', $pembayaran))
            ->assertSessionHasErrors(['alasan_pembatalan', 'password']);

        $this->patch(route('riwayat-pembayaran.batalkan', $pembayaran), [
            'alasan_pembatalan' => 'Salah input.',
            'password' => 'salah',
        ])->assertSessionHasErrors(['password' => 'Password yang dimasukkan tidak sesuai.']);

        $this->assertSame('aktif', $pembayaran->fresh()->status);
    }

    public function test_cancellation_preserves_history_restores_bills_and_allows_repayment(): void
    {
        [$user, $pembayaran, $siswa, $tagihan] = $this->buatPembayaran();
        $jumlahDetailSebelum = DetailPembayaran::query()->where('id_pembayaran', $pembayaran->id_pembayaran)->count();

        $this->actingAs($user)
            ->patch(route('riwayat-pembayaran.batalkan', $pembayaran), [
                'alasan_pembatalan' => 'Nominal perlu dikoreksi.',
                'password' => 'password',
            ])
            ->assertRedirect(route('riwayat-pembayaran.show', $pembayaran));

        $this->assertDatabaseHas('pembayaran', [
            'id_pembayaran' => $pembayaran->id_pembayaran,
            'status' => 'dibatalkan',
            'alasan_pembatalan' => 'Nominal perlu dikoreksi.',
            'dibatalkan_oleh' => $user->id_user,
        ]);
        $this->assertSame($jumlahDetailSebelum, DetailPembayaran::query()->where('id_pembayaran', $pembayaran->id_pembayaran)->count());
        $this->assertSame(1, Pembayaran::query()->whereKey($pembayaran->id_pembayaran)->count());

        foreach ($tagihan as $item) {
            $this->assertSame('belum_bayar', $item->fresh()->status);
            $this->assertNull($item->fresh()->tanggal_lunas);
        }

        $pembayaranBaru = app(PembayaranService::class)->bayar($user, $siswa, $tagihan->pluck('id_tagihan')->all());

        $this->assertSame('aktif', $pembayaranBaru->status);
        $this->assertSame(2, DetailPembayaran::query()->where('id_tagihan', $tagihan->first()->id_tagihan)->count());
        $this->assertSame(1, DetailPembayaran::query()
            ->where('id_tagihan', $tagihan->first()->id_tagihan)
            ->whereHas('pembayaran', fn ($query) => $query->where('status', 'aktif'))
            ->count());
    }

    public function test_transaction_cannot_be_cancelled_twice(): void
    {
        [$user, $pembayaran] = $this->buatPembayaran();

        app(PembatalanPembayaranService::class)->batalkan($user, $pembayaran, 'Salah transaksi.');

        $this->actingAs($user)
            ->from(route('riwayat-pembayaran.show', $pembayaran))
            ->patch(route('riwayat-pembayaran.batalkan', $pembayaran), [
                'alasan_pembatalan' => 'Coba lagi.',
                'password' => 'password',
            ])
            ->assertRedirect(route('riwayat-pembayaran.show', $pembayaran))
            ->assertSessionHasErrors(['pembayaran' => 'Transaksi ini sudah dibatalkan.']);
    }

    public function test_cancellation_rolls_back_when_restoring_a_bill_fails(): void
    {
        [$user, $pembayaran, , $tagihan] = $this->buatPembayaran();
        TagihanSpp::updating(fn () => throw new RuntimeException('Simulasi kegagalan pemulihan tagihan.'));

        try {
            app(PembatalanPembayaranService::class)->batalkan($user, $pembayaran, 'Salah transaksi.');
            $this->fail('Pembatalan seharusnya gagal.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulasi kegagalan pemulihan tagihan.', $exception->getMessage());
        } finally {
            TagihanSpp::flushEventListeners();
        }

        $this->assertSame('aktif', $pembayaran->fresh()->status);
        $this->assertNull($pembayaran->fresh()->dibatalkan_pada);
        $this->assertSame('lunas', $tagihan->first()->fresh()->status);
        $this->assertNotNull($tagihan->first()->fresh()->tanggal_lunas);
    }

    /**
     * @return array{0: User, 1: Pembayaran, 2: Siswa, 3: Collection<int, TagihanSpp>}
     */
    private function buatPembayaran(): array
    {
        $user = User::factory()->create();
        $jurusan = Jurusan::create(['kode_jurusan' => 'BTL', 'nama_jurusan' => 'Pembatalan']);
        $kelas = Kelas::create([
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 1,
            'rombel' => 1,
            'nama_kelas' => 'X BTL 1',
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
            'nipd' => 'BTL-001',
            'nama_siswa' => 'Siswa Pembatalan',
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

        return [$user, app(PembayaranService::class)->bayar($user, $siswa, $tagihan->pluck('id_tagihan')->all()), $siswa, $tagihan];
    }
}
