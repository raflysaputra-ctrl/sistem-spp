<?php

namespace Tests\Feature;

use App\Models\DetailPembayaranNonSpp;
use App\Models\JenisPembayaran;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\PembayaranNonSpp;
use App\Models\PenetapanGelombangBam;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TagihanPembayaran;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\PembayaranNonSppService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class PembayaranNonSppTest extends TestCase
{
    use DatabaseTransactions;

    private static int $tahunBerikutnya = 2080;

    public function test_tu_creates_one_receipt_for_multiple_non_spp_bills(): void
    {
        [$admin, $tu, $siswa, $tahunAjaran] = $this->buatKonteks();
        $pts = $this->buatJenis('PTS', JenisPembayaran::ATURAN_SEKALI_BAYAR, JenisPembayaran::TIPE_PERIODE_SEMESTER);
        $pas = $this->buatJenis('PAS', JenisPembayaran::ATURAN_SEKALI_BAYAR, JenisPembayaran::TIPE_PERIODE_SEMESTER);
        $pkl = $this->buatJenis('PKL', JenisPembayaran::ATURAN_CICILAN, JenisPembayaran::TIPE_PERIODE_TAHUNAN);

        $tagihanPts = $this->buatTagihan($siswa, $tahunAjaran, $pts, 75_000, 0, false, 'semester_1', $admin);
        $tagihanPas = $this->buatTagihan($siswa, $tahunAjaran, $pas, 85_000, 0, false, 'semester_1', $admin);
        $tagihanPkl = $this->buatTagihan($siswa, $tahunAjaran, $pkl, 1_300_000, 800_000, true, 'tahunan', $admin);

        $this->actingAs($tu)->post(route('pembayaran-non-spp.store', $siswa), [
            'items' => [
                ['id_tagihan_pembayaran' => $tagihanPts->id_tagihan_pembayaran, 'selected' => 1, 'nominal_bayar' => 75_000],
                ['id_tagihan_pembayaran' => $tagihanPas->id_tagihan_pembayaran, 'selected' => 1, 'nominal_bayar' => 85_000],
                ['id_tagihan_pembayaran' => $tagihanPkl->id_tagihan_pembayaran, 'selected' => 1, 'nominal_bayar' => 800_000],
            ],
        ])->assertRedirect();

        $pembayaran = PembayaranNonSpp::query()->where('id_siswa', $siswa->id_siswa)->sole();

        $this->assertMatchesRegularExpression('/^NSP-\d{8}-[0-9A-HJKMNP-TV-Z]{26}$/', $pembayaran->no_kwitansi);
        $this->assertDatabaseHas('pembayaran_non_spp', [
            'id_pembayaran_non_spp' => $pembayaran->id_pembayaran_non_spp,
            'id_siswa' => $siswa->id_siswa,
            'id_user' => $tu->id_user,
            'nominal_bayar' => 960_000,
            'status' => 'aktif',
        ]);
        $this->assertSame(3, $pembayaran->detailPembayaranNonSpp()->count());
        $this->assertSame('lunas', $tagihanPts->fresh()->status);
        $this->assertSame('lunas', $tagihanPas->fresh()->status);
        $this->assertSame('sebagian', $tagihanPkl->fresh()->status);

        $this->actingAs($tu)->get(route('pembayaran-non-spp.kwitansi', $pembayaran))
            ->assertOk()
            ->assertSeeText([$pts->nama_jenis, $pas->nama_jenis, $pkl->nama_jenis, 'Rp 960.000']);
    }

    public function test_pts_or_pas_partial_payment_is_rejected(): void
    {
        [$admin, $tu, $siswa, $tahunAjaran] = $this->buatKonteks();
        $pts = $this->buatJenis('PTS', JenisPembayaran::ATURAN_SEKALI_BAYAR, JenisPembayaran::TIPE_PERIODE_SEMESTER);
        $tagihan = $this->buatTagihan($siswa, $tahunAjaran, $pts, 75_000, 0, false, 'semester_1', $admin);

        $this->actingAs($tu)
            ->from(route('pembayaran-non-spp.show', $siswa))
            ->post(route('pembayaran-non-spp.store', $siswa), [
                'items' => [[
                    'id_tagihan_pembayaran' => $tagihan->id_tagihan_pembayaran,
                    'selected' => 1,
                    'nominal_bayar' => 70_000,
                ]],
            ])
            ->assertRedirect(route('pembayaran-non-spp.show', $siswa))
            ->assertSessionHasErrors('items');

        $this->assertSame(0, PembayaranNonSpp::query()->count());
        $this->assertSame('belum_bayar', $tagihan->fresh()->status);
    }

    public function test_non_spp_payment_form_renders_a_visible_nominal_input_for_each_bill(): void
    {
        [$admin, $tu, $siswa, $tahunAjaran] = $this->buatKonteks();
        $pkl = $this->buatJenis('PKL', JenisPembayaran::ATURAN_CICILAN, JenisPembayaran::TIPE_PERIODE_TAHUNAN);
        $this->buatTagihan($siswa, $tahunAjaran, $pkl, 1_300_000, 800_000, true, 'tahunan', $admin);

        $this->actingAs($tu)
            ->get(route('pembayaran-non-spp.show', $siswa))
            ->assertOk()
            ->assertSeeHtml('class="payment-nominal"')
            ->assertSeeHtml('placeholder="Pilih tagihan dahulu"');
    }

    public function test_first_installment_requires_dp_and_following_installment_can_be_smaller(): void
    {
        [$admin, $tu, $siswa, $tahunAjaran] = $this->buatKonteks();
        $pkl = $this->buatJenis('PKL', JenisPembayaran::ATURAN_CICILAN, JenisPembayaran::TIPE_PERIODE_TAHUNAN);
        $tagihan = $this->buatTagihan($siswa, $tahunAjaran, $pkl, 1_300_000, 800_000, true, 'tahunan', $admin);

        $this->actingAs($tu)->post(route('pembayaran-non-spp.store', $siswa), [
            'items' => [[
                'id_tagihan_pembayaran' => $tagihan->id_tagihan_pembayaran,
                'selected' => 1,
                'nominal_bayar' => 700_000,
            ]],
        ])->assertSessionHasErrors('items');

        app(PembayaranNonSppService::class)->bayar($tu, $siswa, collect([[
            'id_tagihan_pembayaran' => $tagihan->id_tagihan_pembayaran,
            'nominal_bayar' => 800_000,
        ]]));

        app(PembayaranNonSppService::class)->bayar($tu, $siswa, collect([[
            'id_tagihan_pembayaran' => $tagihan->id_tagihan_pembayaran,
            'nominal_bayar' => 50_000,
        ]]));

        $this->assertSame('sebagian', $tagihan->fresh()->status);
        $this->assertSame(850_000, $tagihan->fresh()->total_dibayar);
    }

    public function test_issued_installment_bill_keeps_its_snapshot_when_the_master_rule_changes(): void
    {
        [$admin, $tu, $siswa, $tahunAjaran] = $this->buatKonteks();
        $pkl = $this->buatJenis('PKL', JenisPembayaran::ATURAN_CICILAN, JenisPembayaran::TIPE_PERIODE_TAHUNAN);
        $tagihan = $this->buatTagihan($siswa, $tahunAjaran, $pkl, 1_300_000, 800_000, true, 'tahunan', $admin);

        $pkl->update(['aturan_pembayaran' => JenisPembayaran::ATURAN_SEKALI_BAYAR]);

        app(PembayaranNonSppService::class)->bayar($tu, $siswa, collect([[
            'id_tagihan_pembayaran' => $tagihan->id_tagihan_pembayaran,
            'nominal_bayar' => 800_000,
        ]]));

        $this->assertSame('sebagian', $tagihan->fresh()->status);
        $this->assertSame(800_000, $tagihan->fresh()->total_dibayar);
    }

    public function test_payment_rolls_back_when_a_receipt_detail_fails(): void
    {
        [$admin, $tu, $siswa, $tahunAjaran] = $this->buatKonteks();
        $pts = $this->buatJenis('PTS', JenisPembayaran::ATURAN_SEKALI_BAYAR, JenisPembayaran::TIPE_PERIODE_SEMESTER);
        $tagihan = $this->buatTagihan($siswa, $tahunAjaran, $pts, 75_000, 0, false, 'semester_1', $admin);
        DetailPembayaranNonSpp::creating(fn () => throw new RuntimeException('Simulasi kegagalan detail non-SPP.'));

        try {
            app(PembayaranNonSppService::class)->bayar($tu, $siswa, collect([[
                'id_tagihan_pembayaran' => $tagihan->id_tagihan_pembayaran,
                'nominal_bayar' => 75_000,
            ]]));
            $this->fail('Pembayaran seharusnya gagal saat detail dibuat.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulasi kegagalan detail non-SPP.', $exception->getMessage());
        } finally {
            DetailPembayaranNonSpp::flushEventListeners();
        }

        $this->assertSame(0, PembayaranNonSpp::query()->count());
        $this->assertSame(0, DetailPembayaranNonSpp::query()->count());
        $this->assertSame('belum_bayar', $tagihan->fresh()->status);
    }

    public function test_admin_cancels_entire_receipt_and_recalculates_each_bill(): void
    {
        [$admin, $tu, $siswa, $tahunAjaran] = $this->buatKonteks();
        $pts = $this->buatJenis('PTS', JenisPembayaran::ATURAN_SEKALI_BAYAR, JenisPembayaran::TIPE_PERIODE_SEMESTER);
        $pkl = $this->buatJenis('PKL', JenisPembayaran::ATURAN_CICILAN, JenisPembayaran::TIPE_PERIODE_TAHUNAN);
        $tagihanPts = $this->buatTagihan($siswa, $tahunAjaran, $pts, 75_000, 0, false, 'semester_1', $admin);
        $tagihanPkl = $this->buatTagihan($siswa, $tahunAjaran, $pkl, 1_300_000, 800_000, true, 'tahunan', $admin);

        $pembayaranPertama = app(PembayaranNonSppService::class)->bayar($tu, $siswa, collect([
            ['id_tagihan_pembayaran' => $tagihanPts->id_tagihan_pembayaran, 'nominal_bayar' => 75_000],
            ['id_tagihan_pembayaran' => $tagihanPkl->id_tagihan_pembayaran, 'nominal_bayar' => 800_000],
        ]));

        $this->actingAs($admin)
            ->patch(route('riwayat-pembayaran-non-spp.batalkan', $pembayaranPertama), [
                'alasan_pembatalan' => 'Nominal pada kwitansi pertama perlu dikoreksi.',
                'password' => 'password',
            ])
            ->assertRedirect(route('riwayat-pembayaran-non-spp.show', $pembayaranPertama));

        $this->assertDatabaseHas('pembayaran_non_spp', [
            'id_pembayaran_non_spp' => $pembayaranPertama->id_pembayaran_non_spp,
            'status' => 'dibatalkan',
            'dibatalkan_oleh' => $admin->id_user,
        ]);
        $this->assertSame('belum_bayar', $tagihanPts->fresh()->status);
        $this->assertSame('belum_bayar', $tagihanPkl->fresh()->status);
        $this->assertSame(0, $tagihanPkl->fresh()->total_dibayar);

        $this->actingAs($tu)->post(route('pembayaran-non-spp.store', $siswa), [
            'items' => [[
                'id_tagihan_pembayaran' => $tagihanPkl->id_tagihan_pembayaran,
                'selected' => 1,
                'nominal_bayar' => 1,
            ]],
        ])->assertSessionHasErrors('items');
        $this->assertSame(0, $tagihanPkl->fresh()->total_dibayar);

        $this->actingAs($tu)
            ->patch(route('riwayat-pembayaran-non-spp.batalkan', $pembayaranPertama), [
                'alasan_pembatalan' => 'Tidak berwenang.',
                'password' => 'password',
            ])
            ->assertForbidden();
    }

    public function test_admin_cannot_cancel_a_dp_receipt_that_would_leave_an_under_dp_installment(): void
    {
        [$admin, $tu, $siswa, $tahunAjaran] = $this->buatKonteks();
        $pkl = $this->buatJenis('PKL', JenisPembayaran::ATURAN_CICILAN, JenisPembayaran::TIPE_PERIODE_TAHUNAN);
        $tagihan = $this->buatTagihan($siswa, $tahunAjaran, $pkl, 1_300_000, 800_000, true, 'tahunan', $admin);

        $pembayaranDp = app(PembayaranNonSppService::class)->bayar($tu, $siswa, collect([[
            'id_tagihan_pembayaran' => $tagihan->id_tagihan_pembayaran,
            'nominal_bayar' => 800_000,
        ]]));
        $pembayaranCicilan = app(PembayaranNonSppService::class)->bayar($tu, $siswa, collect([[
            'id_tagihan_pembayaran' => $tagihan->id_tagihan_pembayaran,
            'nominal_bayar' => 200_000,
        ]]));

        $this->actingAs($admin)
            ->from(route('riwayat-pembayaran-non-spp.show', $pembayaranDp))
            ->patch(route('riwayat-pembayaran-non-spp.batalkan', $pembayaranDp), [
                'alasan_pembatalan' => 'Koreksi pembayaran awal.',
                'password' => 'password',
            ])
            ->assertRedirect(route('riwayat-pembayaran-non-spp.show', $pembayaranDp))
            ->assertSessionHasErrors('pembayaran');

        $this->assertSame('aktif', $pembayaranDp->fresh()->status);
        $this->assertSame('aktif', $pembayaranCicilan->fresh()->status);
        $this->assertSame(1_000_000, $tagihan->fresh()->total_dibayar);

        $this->actingAs($admin)
            ->patch(route('riwayat-pembayaran-non-spp.batalkan', $pembayaranCicilan), [
                'alasan_pembatalan' => 'Batalkan cicilan terakhir terlebih dahulu.',
                'password' => 'password',
            ])
            ->assertRedirect(route('riwayat-pembayaran-non-spp.show', $pembayaranCicilan));

        $this->actingAs($admin)
            ->patch(route('riwayat-pembayaran-non-spp.batalkan', $pembayaranDp), [
                'alasan_pembatalan' => 'Batalkan pembayaran awal setelah cicilan terakhir.',
                'password' => 'password',
            ])
            ->assertRedirect(route('riwayat-pembayaran-non-spp.show', $pembayaranDp));

        $this->assertSame('belum_bayar', $tagihan->fresh()->status);
    }

    public function test_core_pts_type_cannot_be_made_installment_by_a_tampered_bill_snapshot(): void
    {
        [$admin, $tu, $siswa, $tahunAjaran] = $this->buatKonteks();
        $pts = JenisPembayaran::firstOrCreate(
            ['kode_jenis' => 'PTS'],
            [
                'nama_jenis' => 'PTS',
                'aturan_pembayaran' => JenisPembayaran::ATURAN_SEKALI_BAYAR,
                'tipe_periode' => JenisPembayaran::TIPE_PERIODE_SEMESTER,
                'aktif' => true,
            ],
        );
        $pts->update(['aturan_pembayaran' => JenisPembayaran::ATURAN_CICILAN]);
        $tagihan = $this->buatTagihan($siswa, $tahunAjaran, $pts, 75_000, 1, true, 'semester_1', $admin);

        $this->actingAs($tu)->post(route('pembayaran-non-spp.store', $siswa), [
            'items' => [[
                'id_tagihan_pembayaran' => $tagihan->id_tagihan_pembayaran,
                'selected' => 1,
                'nominal_bayar' => 1,
            ]],
        ])->assertSessionHasErrors('items');

        $this->assertSame(0, PembayaranNonSpp::query()->count());
        $this->assertSame('belum_bayar', $tagihan->fresh()->status);
    }

    public function test_cancelled_legacy_receipt_without_a_cancellation_actor_remains_viewable(): void
    {
        [$admin, $tu, $siswa, $tahunAjaran] = $this->buatKonteks();
        $jenis = $this->buatJenis('PKL', JenisPembayaran::ATURAN_CICILAN, JenisPembayaran::TIPE_PERIODE_TAHUNAN);
        $tagihan = $this->buatTagihan($siswa, $tahunAjaran, $jenis, 800_000, 500_000, true, 'tahunan', $admin);
        $pembayaran = PembayaranNonSpp::create([
            'no_kwitansi' => 'NSP-LEGACY-BATAL',
            'id_siswa' => $siswa->id_siswa,
            'id_user' => $tu->id_user,
            'tanggal_bayar' => now(),
            'nominal_bayar' => 500_000,
            'status' => 'dibatalkan',
            'alasan_pembatalan' => 'Data pembatalan lama.',
            'dibatalkan_pada' => now(),
        ]);
        $pembayaran->detailPembayaranNonSpp()->create([
            'id_tagihan_pembayaran' => $tagihan->id_tagihan_pembayaran,
            'nominal_bayar' => 500_000,
            'total_terbayar_setelah' => 500_000,
        ]);

        $this->actingAs($admin)
            ->get(route('riwayat-pembayaran-non-spp.show', $pembayaran))
            ->assertOk()
            ->assertSeeText('Tidak tercatat (legacy)');
    }

    public function test_admin_generates_all_periods_for_pts_pas_and_biaya_awal_masuk_from_one_form(): void
    {
        [$admin, , $siswa, $tahunAjaran] = $this->buatKonteks(true);
        $pts = $this->buatJenis('PTS', JenisPembayaran::ATURAN_SEKALI_BAYAR, JenisPembayaran::TIPE_PERIODE_SEMESTER, [1]);
        $pas = $this->buatJenis('PAS', JenisPembayaran::ATURAN_SEKALI_BAYAR, JenisPembayaran::TIPE_PERIODE_SEMESTER, [1]);
        $biayaAwalMasuk = $this->buatJenis('BIAYA_AWAL_MASUK', JenisPembayaran::ATURAN_CICILAN, JenisPembayaran::TIPE_PERIODE_GELOMBANG, [1]);

        $payload = [
            'id_jenis_pembayaran' => $pts->id_jenis_pembayaran,
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tagihan' => [
                ['kode_periode' => 'semester_1', 'total_tagihan' => 75_000],
                ['kode_periode' => 'semester_2', 'total_tagihan' => 75_000],
            ],
        ];

        $this->actingAs($admin)->post(route('master.tagihan-non-spp.store'), $payload)
            ->assertRedirect(route('master.tagihan-non-spp.index'));

        $this->assertDatabaseHas('tagihan_pembayaran', [
            'id_siswa' => $siswa->id_siswa,
            'id_jenis_pembayaran' => $pts->id_jenis_pembayaran,
            'kode_periode' => 'semester_1',
            'bisa_cicil' => false,
            'minimal_dp' => 0,
        ]);
        $this->assertDatabaseHas('tagihan_pembayaran', [
            'id_siswa' => $siswa->id_siswa,
            'id_jenis_pembayaran' => $pts->id_jenis_pembayaran,
            'kode_periode' => 'semester_2',
            'bisa_cicil' => false,
            'minimal_dp' => 0,
        ]);

        $this->actingAs($admin)->post(route('master.tagihan-non-spp.store'), $payload)
            ->assertRedirect(route('master.tagihan-non-spp.index'));
        $this->assertSame(2, TagihanPembayaran::query()->where('id_siswa', $siswa->id_siswa)->count());

        $this->actingAs($admin)->post(route('master.tagihan-non-spp.store'), [
            ...$payload,
            'id_jenis_pembayaran' => $pas->id_jenis_pembayaran,
            'tagihan' => [
                ['kode_periode' => 'semester_1', 'total_tagihan' => 85_000],
                ['kode_periode' => 'semester_2', 'total_tagihan' => 85_000],
            ],
        ])->assertRedirect(route('master.tagihan-non-spp.index'));
        $this->assertSame(4, TagihanPembayaran::query()->where('id_siswa', $siswa->id_siswa)->count());

        $this->actingAs($admin)->post(route('master.tagihan-non-spp.store'), [
            'id_jenis_pembayaran' => $biayaAwalMasuk->id_jenis_pembayaran,
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tagihan' => [
                ['kode_periode' => 'gelombang_1', 'total_tagihan' => 2_000_000, 'minimal_dp' => 500_000],
                ['kode_periode' => 'gelombang_2', 'total_tagihan' => 2_300_000, 'minimal_dp' => 600_000],
                ['kode_periode' => 'gelombang_3', 'total_tagihan' => 2_500_000, 'minimal_dp' => 700_000],
            ],
        ])->assertRedirect(route('master.tagihan-non-spp.index'));

        $this->assertSame(7, TagihanPembayaran::query()->where('id_siswa', $siswa->id_siswa)->count());
        $this->assertDatabaseHas('tagihan_pembayaran', [
            'id_siswa' => $siswa->id_siswa,
            'id_jenis_pembayaran' => $biayaAwalMasuk->id_jenis_pembayaran,
            'kode_periode' => 'gelombang_2',
            'total_tagihan' => 2_300_000,
            'minimal_dp' => 600_000,
            'bisa_cicil' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('master.tagihan-non-spp.index', ['id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran]))
            ->assertOk()
            ->assertSeeText(['Biaya Tagihan', 'Rp 2.300.000'])
            ->assertDontSeeText('Total Nilai Tagihan');
    }

    public function test_admin_must_complete_every_period_from_the_same_batch_form(): void
    {
        [$admin, , $siswa, $tahunAjaran] = $this->buatKonteks(true);
        $pts = $this->buatJenis('PTS', JenisPembayaran::ATURAN_SEKALI_BAYAR, JenisPembayaran::TIPE_PERIODE_SEMESTER, [1]);

        $this->actingAs($admin)
            ->from(route('master.tagihan-non-spp.create'))
            ->post(route('master.tagihan-non-spp.store'), [
                'id_jenis_pembayaran' => $pts->id_jenis_pembayaran,
                'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
                'tagihan' => [
                    ['kode_periode' => 'semester_1', 'total_tagihan' => 75_000],
                ],
            ])
            ->assertRedirect(route('master.tagihan-non-spp.create'))
            ->assertSessionHasErrors('tagihan');

        $this->assertSame(0, TagihanPembayaran::query()->where('id_siswa', $siswa->id_siswa)->count());
    }

    public function test_non_spp_routes_enforce_the_confirmed_roles(): void
    {
        [, $tu] = $this->buatKonteks();
        $admin = User::factory()->admin()->create();
        $kepalaSekolah = User::factory()->kepalaSekolah()->create();

        $this->get(route('pembayaran-non-spp.index'))->assertRedirect(route('login'));
        $this->actingAs($admin)->get(route('pembayaran-non-spp.index'))->assertForbidden();
        $this->actingAs($kepalaSekolah)->get(route('pembayaran-non-spp.index'))->assertForbidden();
        $this->actingAs($tu)->get(route('pembayaran-non-spp.index'))->assertOk();

        $this->actingAs($admin)->get(route('riwayat-pembayaran-non-spp.index'))->assertOk();
        $this->actingAs($tu)->get(route('riwayat-pembayaran-non-spp.index'))->assertOk();
        $this->actingAs($kepalaSekolah)->get(route('riwayat-pembayaran-non-spp.index'))->assertForbidden();
    }

    public function test_tu_sets_bam_wave_on_first_payment_and_other_waves_become_inapplicable(): void
    {
        [$admin, $tu, $siswa, $tahunAjaran] = $this->buatKonteks();
        $bam = $this->buatJenisBam();
        $gelombangSatu = $this->buatTagihan($siswa, $tahunAjaran, $bam, 2_000_000, 500_000, true, 'gelombang_1', $admin);
        $gelombangDua = $this->buatTagihan($siswa, $tahunAjaran, $bam, 2_300_000, 600_000, true, 'gelombang_2', $admin);
        $gelombangTiga = $this->buatTagihan($siswa, $tahunAjaran, $bam, 2_500_000, 700_000, true, 'gelombang_3', $admin);

        $this->actingAs($tu)
            ->get(route('pembayaran-non-spp.show', $siswa))
            ->assertOk()
            ->assertSeeText('Gelombang Biaya Awal Masuk');

        $this->actingAs($tu)->post(route('pembayaran-non-spp.store', $siswa), [
            'kode_periode_bam' => 'gelombang_2',
            'items' => [[
                'id_tagihan_pembayaran' => $gelombangDua->id_tagihan_pembayaran,
                'selected' => 1,
                'nominal_bayar' => 600_000,
            ]],
        ])->assertRedirect();

        $this->assertDatabaseHas('penetapan_gelombang_bam', [
            'id_siswa' => $siswa->id_siswa,
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'id_tagihan_pembayaran' => $gelombangDua->id_tagihan_pembayaran,
            'kode_periode' => 'gelombang_2',
            'ditetapkan_oleh' => $tu->id_user,
        ]);
        $this->assertSame('tidak_berlaku', $gelombangSatu->fresh()->status);
        $this->assertSame('sebagian', $gelombangDua->fresh()->status);
        $this->assertSame('tidak_berlaku', $gelombangTiga->fresh()->status);

        $this->actingAs($tu)
            ->from(route('pembayaran-non-spp.show', $siswa))
            ->post(route('pembayaran-non-spp.store', $siswa), [
                'kode_periode_bam' => 'gelombang_1',
                'items' => [[
                    'id_tagihan_pembayaran' => $gelombangSatu->id_tagihan_pembayaran,
                    'selected' => 1,
                    'nominal_bayar' => 500_000,
                ]],
            ])
            ->assertRedirect(route('pembayaran-non-spp.show', $siswa))
            ->assertSessionHasErrors('items');
    }

    public function test_admin_can_correct_bam_wave_only_after_active_payments_are_cancelled(): void
    {
        [$admin, $tu, $siswa, $tahunAjaran] = $this->buatKonteks();
        $bam = $this->buatJenisBam();
        $gelombangSatu = $this->buatTagihan($siswa, $tahunAjaran, $bam, 2_000_000, 500_000, true, 'gelombang_1', $admin);
        $gelombangDua = $this->buatTagihan($siswa, $tahunAjaran, $bam, 2_300_000, 600_000, true, 'gelombang_2', $admin);
        $gelombangTiga = $this->buatTagihan($siswa, $tahunAjaran, $bam, 2_500_000, 700_000, true, 'gelombang_3', $admin);

        $pembayaran = app(PembayaranNonSppService::class)->bayar($tu, $siswa, collect([[
            'id_tagihan_pembayaran' => $gelombangSatu->id_tagihan_pembayaran,
            'nominal_bayar' => 500_000,
        ]]), 'gelombang_1');

        $this->actingAs($admin)
            ->from(route('master.tagihan-non-spp.detail', [
                'id_jenis_pembayaran' => $bam->id_jenis_pembayaran,
                'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
                'kode_periode' => 'gelombang_1',
            ]))
            ->patch(route('master.tagihan-non-spp.gelombang-bam.koreksi', $siswa), [
                'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
                'kode_periode' => 'gelombang_2',
                'alasan_koreksi' => 'Gelombang pendaftaran dikoreksi.',
            ])
            ->assertSessionHasErrors('items');

        $this->actingAs($admin)->patch(route('riwayat-pembayaran-non-spp.batalkan', $pembayaran), [
            'alasan_pembatalan' => 'Pembayaran BAM perlu dikoreksi.',
            'password' => 'password',
        ])->assertRedirect();

        $this->actingAs($admin)
            ->patch(route('master.tagihan-non-spp.gelombang-bam.koreksi', $siswa), [
                'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
                'kode_periode' => 'gelombang_2',
                'alasan_koreksi' => 'Gelombang pendaftaran dikoreksi.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $penetapan = PenetapanGelombangBam::query()->sole();
        $this->assertSame('gelombang_2', $penetapan->kode_periode);
        $this->assertSame($admin->id_user, $penetapan->dikoreksi_oleh);
        $this->assertSame('tidak_berlaku', $gelombangSatu->fresh()->status);
        $this->assertSame('belum_bayar', $gelombangDua->fresh()->status);
        $this->assertSame('tidak_berlaku', $gelombangTiga->fresh()->status);
    }

    /**
     * @return array{0: User, 1: User, 2: Siswa, 3: TahunAjaran}
     */
    private function buatKonteks(bool $denganKelas = false): array
    {
        $suffix = strtoupper(substr(str_replace('-', '', (string) Str::uuid()), 0, 8));
        $tahunMulai = self::$tahunBerikutnya++;
        $admin = User::factory()->admin()->create();
        $tu = User::factory()->tu()->create();
        $tahunAjaran = TahunAjaran::create([
            'tahun_ajaran' => "{$tahunMulai}/".($tahunMulai + 1),
            'tanggal_mulai' => "{$tahunMulai}-07-01",
            'tanggal_selesai' => ($tahunMulai + 1).'-06-30',
            'aktif' => false,
            'status' => 'persiapan',
        ]);
        $siswa = Siswa::factory()->create([
            'nipd' => "NSP-{$suffix}",
            'angkatan' => $tahunMulai,
            'status_siswa' => 'aktif',
        ]);

        if ($denganKelas) {
            $jurusan = Jurusan::create([
                'kode_jurusan' => "NS{$suffix}",
                'nama_jurusan' => "Jurusan {$suffix}",
            ]);
            $kelas = Kelas::create([
                'id_jurusan' => $jurusan->id_jurusan,
                'tingkat' => 1,
                'rombel' => 1,
                'nama_kelas' => "X NSP {$suffix}",
            ]);
            SiswaKelas::create([
                'id_siswa' => $siswa->id_siswa,
                'id_kelas' => $kelas->id_kelas,
                'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            ]);
        }

        return [$admin, $tu, $siswa, $tahunAjaran];
    }

    private function buatJenis(
        string $prefix,
        string $aturanPembayaran,
        string $tipePeriode,
        ?array $targetTingkat = null,
    ): JenisPembayaran {
        $suffix = strtoupper(substr(str_replace('-', '', (string) Str::uuid()), 0, 8));

        return JenisPembayaran::create([
            'kode_jenis' => "{$prefix}_{$suffix}",
            'nama_jenis' => "{$prefix} {$suffix}",
            'target_tingkat' => $targetTingkat,
            'aturan_pembayaran' => $aturanPembayaran,
            'tipe_periode' => $tipePeriode,
            'aktif' => true,
        ]);
    }

    private function buatJenisBam(): JenisPembayaran
    {
        return JenisPembayaran::create([
            'kode_jenis' => JenisPembayaran::KODE_JENIS_BIAYA_AWAL_MASUK,
            'nama_jenis' => 'Biaya Awal Masuk',
            'aturan_pembayaran' => JenisPembayaran::ATURAN_CICILAN,
            'tipe_periode' => JenisPembayaran::TIPE_PERIODE_GELOMBANG,
            'aktif' => true,
        ]);
    }

    private function buatTagihan(
        Siswa $siswa,
        TahunAjaran $tahunAjaran,
        JenisPembayaran $jenis,
        int $total,
        int $minimalDp,
        bool $bisaCicil,
        string $kodePeriode,
        User $admin,
    ): TagihanPembayaran {
        return TagihanPembayaran::create([
            'id_siswa' => $siswa->id_siswa,
            'id_jenis_pembayaran' => $jenis->id_jenis_pembayaran,
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'total_tagihan' => $total,
            'minimal_dp' => $minimalDp,
            'bisa_cicil' => $bisaCicil,
            'kode_periode' => $kodePeriode,
            'periode_keterangan' => JenisPembayaran::labelPeriode($kodePeriode),
            'status' => 'belum_bayar',
            'created_by' => $admin->id_user,
        ]);
    }
}
