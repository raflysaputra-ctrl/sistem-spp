<?php

namespace Tests\Feature;

use App\Models\DetailPembayaranNonSpp;
use App\Models\JenisPembayaran;
use App\Models\PembayaranNonSpp;
use App\Models\Siswa;
use App\Models\TagihanPembayaran;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class NonSppLegacyMigrationTest extends TestCase
{
    use DatabaseTransactions;

    private static int $tahunBerikutnya = 2090;

    public function test_legacy_bill_hardening_corrects_fixed_payment_rules_and_infers_periods(): void
    {
        [$admin, $siswa, $tahunAjaran] = $this->buatKonteks();
        $siswaLain = Siswa::factory()->create(['nipd' => 'LEG-'.strtoupper(Str::random(8))]);
        $pts = $this->buatAtauResetJenis(
            'PTS',
            JenisPembayaran::ATURAN_CICILAN,
            JenisPembayaran::TIPE_PERIODE_SEMESTER,
        );
        $biayaAwalMasuk = $this->buatAtauResetJenis(
            'BIAYA_AWAL_MASUK',
            JenisPembayaran::ATURAN_CICILAN,
            JenisPembayaran::TIPE_PERIODE_GELOMBANG,
        );

        $tagihanPts = $this->buatTagihanLegacy(
            $siswa,
            $tahunAjaran,
            $pts,
            $admin,
            'Sem 2',
            true,
            50_000,
        );
        $tagihanGelombang = $this->buatTagihanLegacy(
            $siswa,
            $tahunAjaran,
            $biayaAwalMasuk,
            $admin,
            'Gelombang 3',
            true,
            250_000,
        );
        $tagihanTanpaPeriode = $this->buatTagihanLegacy(
            $siswaLain,
            $tahunAjaran,
            $biayaAwalMasuk,
            $admin,
            'Tagihan lama tanpa periode terstruktur',
            true,
            250_000,
        );

        $this->jalankanMigration('2026_10_06_000200_harden_non_spp_legacy_bills.php');

        $tagihanPts->refresh();
        $tagihanGelombang->refresh();
        $tagihanTanpaPeriode->refresh();

        $this->assertFalse($tagihanPts->bisa_cicil);
        $this->assertSame(0, (int) $tagihanPts->minimal_dp);
        $this->assertSame('semester_2', $tagihanPts->kode_periode);
        $this->assertSame('Semester 2', $tagihanPts->periode_keterangan);
        $this->assertSame('gelombang_3', $tagihanGelombang->kode_periode);
        $this->assertSame('Gelombang 3', $tagihanGelombang->periode_keterangan);
        $this->assertSame('legacy', $tagihanTanpaPeriode->kode_periode);
        $this->assertSame('Tagihan lama tanpa periode terstruktur', $tagihanTanpaPeriode->periode_keterangan);
        $this->assertSame('Tagihan lama tanpa periode terstruktur', $tagihanTanpaPeriode->periode_label);
    }

    public function test_receipt_snapshot_recalculation_preserves_the_historical_effect_of_a_later_cancellation(): void
    {
        [$admin, $siswa, $tahunAjaran] = $this->buatKonteks();
        $jenis = JenisPembayaran::create([
            'kode_jenis' => 'LEGACY_'.strtoupper(Str::random(8)),
            'nama_jenis' => 'Tagihan Legacy',
            'aturan_pembayaran' => JenisPembayaran::ATURAN_CICILAN,
            'tipe_periode' => JenisPembayaran::TIPE_PERIODE_TAHUNAN,
            'aktif' => true,
        ]);
        $tagihan = TagihanPembayaran::create([
            'id_siswa' => $siswa->id_siswa,
            'id_jenis_pembayaran' => $jenis->id_jenis_pembayaran,
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'total_tagihan' => 1_000_000,
            'minimal_dp' => 200_000,
            'bisa_cicil' => true,
            'kode_periode' => 'tahunan',
            'periode_keterangan' => 'Satu kali per tahun ajaran',
            'status' => 'sebagian',
            'created_by' => $admin->id_user,
        ]);

        $pembayaranPertama = $this->buatPembayaran($siswa, $admin, '2090-07-01 08:00:00', 300_000);
        $pembayaranDibatalkan = $this->buatPembayaran(
            $siswa,
            $admin,
            '2090-08-01 08:00:00',
            200_000,
            'dibatalkan',
            '2090-09-01 08:00:00',
        );
        $pembayaranBerikutnya = $this->buatPembayaran($siswa, $admin, '2090-10-01 08:00:00', 100_000);

        $detailPertama = $this->buatDetail($pembayaranPertama, $tagihan, 300_000);
        $detailDibatalkan = $this->buatDetail($pembayaranDibatalkan, $tagihan, 200_000);
        $detailBerikutnya = $this->buatDetail($pembayaranBerikutnya, $tagihan, 100_000);

        $this->jalankanMigration('2026_10_06_000300_recalculate_non_spp_receipt_snapshots.php');
        $this->jalankanMigration('2026_10_06_000300_recalculate_non_spp_receipt_snapshots.php');

        $this->assertSame(300_000, (int) $detailPertama->fresh()->total_terbayar_setelah);
        $this->assertSame(500_000, (int) $detailDibatalkan->fresh()->total_terbayar_setelah);
        $this->assertSame(400_000, (int) $detailBerikutnya->fresh()->total_terbayar_setelah);
    }

    public function test_period_collision_preflight_blocks_duplicate_legacy_bills_before_the_unique_key_is_added(): void
    {
        [$admin, $siswa, $tahunAjaran] = $this->buatKonteks();
        $jenis = JenisPembayaran::create([
            'kode_jenis' => 'PKL_'.strtoupper(Str::random(8)),
            'nama_jenis' => 'PKL Legacy',
            'aturan_pembayaran' => JenisPembayaran::ATURAN_CICILAN,
            'tipe_periode' => JenisPembayaran::TIPE_PERIODE_TAHUNAN,
            'aktif' => true,
        ]);
        $this->buatTagihanLegacy($siswa, $tahunAjaran, $jenis, $admin, 'Tagihan lama pertama', true, 100_000, 'semester_1');
        $this->buatTagihanLegacy($siswa, $tahunAjaran, $jenis, $admin, 'Tagihan lama kedua', true, 100_000, 'semester_2');

        Schema::shouldReceive('hasTable')->once()->with('tagihan_pembayaran')->andReturnTrue();
        Schema::shouldReceive('hasColumn')->once()->with('tagihan_pembayaran', 'kode_periode')->andReturnFalse();

        $this->expectException(RuntimeException::class);
        $this->jalankanMigration('2026_10_05_235950_preflight_all_non_spp_period_collisions.php');
    }

    /**
     * @return array{0: User, 1: Siswa, 2: TahunAjaran}
     */
    private function buatKonteks(): array
    {
        $tahunMulai = self::$tahunBerikutnya++;

        return [
            User::factory()->admin()->create(),
            Siswa::factory()->create(['nipd' => 'LEG-'.strtoupper(Str::random(8))]),
            TahunAjaran::create([
                'tahun_ajaran' => "{$tahunMulai}/".($tahunMulai + 1),
                'tanggal_mulai' => "{$tahunMulai}-07-01",
                'tanggal_selesai' => ($tahunMulai + 1).'-06-30',
                'aktif' => false,
                'status' => 'persiapan',
            ]),
        ];
    }

    private function buatAtauResetJenis(string $kode, string $aturan, string $tipePeriode): JenisPembayaran
    {
        $jenis = JenisPembayaran::firstOrCreate(
            ['kode_jenis' => $kode],
            [
                'nama_jenis' => $kode,
                'aturan_pembayaran' => $aturan,
                'tipe_periode' => $tipePeriode,
                'aktif' => true,
            ],
        );

        $jenis->update([
            'aturan_pembayaran' => $aturan,
            'tipe_periode' => $tipePeriode,
            'aktif' => true,
        ]);

        return $jenis;
    }

    private function buatTagihanLegacy(
        Siswa $siswa,
        TahunAjaran $tahunAjaran,
        JenisPembayaran $jenis,
        User $admin,
        string $periodeKeterangan,
        bool $bisaCicil,
        int $minimalDp,
        string $kodePeriode = 'tahunan',
    ): TagihanPembayaran {
        return TagihanPembayaran::create([
            'id_siswa' => $siswa->id_siswa,
            'id_jenis_pembayaran' => $jenis->id_jenis_pembayaran,
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'total_tagihan' => 1_000_000,
            'minimal_dp' => $minimalDp,
            'bisa_cicil' => $bisaCicil,
            'kode_periode' => $kodePeriode,
            'periode_keterangan' => $periodeKeterangan,
            'status' => 'belum_bayar',
            'created_by' => $admin->id_user,
        ]);
    }

    private function buatPembayaran(
        Siswa $siswa,
        User $user,
        string $tanggalBayar,
        int $nominal,
        string $status = 'aktif',
        ?string $dibatalkanPada = null,
    ): PembayaranNonSpp {
        return PembayaranNonSpp::create([
            'no_kwitansi' => 'LEG-'.strtoupper(Str::random(16)),
            'id_siswa' => $siswa->id_siswa,
            'id_user' => $user->id_user,
            'tanggal_bayar' => $tanggalBayar,
            'nominal_bayar' => $nominal,
            'status' => $status,
            'dibatalkan_pada' => $dibatalkanPada,
        ]);
    }

    private function buatDetail(
        PembayaranNonSpp $pembayaran,
        TagihanPembayaran $tagihan,
        int $nominal,
    ): DetailPembayaranNonSpp {
        return DetailPembayaranNonSpp::create([
            'id_pembayaran_non_spp' => $pembayaran->id_pembayaran_non_spp,
            'id_tagihan_pembayaran' => $tagihan->id_tagihan_pembayaran,
            'nominal_bayar' => $nominal,
            'total_terbayar_setelah' => 0,
        ]);
    }

    private function jalankanMigration(string $namaFile): void
    {
        (require database_path("migrations/{$namaFile}"))->up();
    }
}
