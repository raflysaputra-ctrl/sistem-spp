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
use App\Services\PembayaranService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use PDOException;
use RuntimeException;
use Tests\TestCase;

class PembayaranTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_access_payment_transaction(): void
    {
        $this->get(route('pembayaran.index'))
            ->assertRedirect(route('login'));
    }

    public function test_transaction_page_lists_active_students_and_filters_them(): void
    {
        $user = User::factory()->create();
        [$siswa, , $siswaKelas] = $this->siswaDenganTagihan();
        $kelasLain = Kelas::create([
            'id_jurusan' => $siswaKelas->kelas->id_jurusan,
            'tingkat' => 1,
            'rombel' => 5,
            'nama_kelas' => 'X PYM 5',
        ]);
        $siswaLain = Siswa::create([
            'nipd' => '4511003',
            'nama_siswa' => 'AAA Siswa Filter Lain',
            'jenis_kelamin' => 'P',
            'angkatan' => 2045,
            'status_siswa' => 'aktif',
        ]);
        SiswaKelas::create([
            'id_siswa' => $siswaLain->id_siswa,
            'id_kelas' => $kelasLain->id_kelas,
            'id_tahun_ajaran' => $siswaKelas->id_tahun_ajaran,
        ]);
        $siswaLulus = Siswa::create([
            'nipd' => '4511004',
            'nama_siswa' => 'ZZZ Siswa Lulus',
            'jenis_kelamin' => 'L',
            'angkatan' => 2044,
            'status_siswa' => 'lulus',
        ]);

        $this->actingAs($user)->get(route('pembayaran.index', ['cari' => 'AAA Siswa Filter']))
            ->assertOk()
            ->assertSeeText('AAA Siswa Filter Lain')
            ->assertViewHas('siswa', fn ($siswa) => $siswa->perPage() === 10);

        $this->actingAs($user)->get(route('pembayaran.index', [
            'cari' => $siswa->nipd,
            'id_jurusan' => $siswaKelas->kelas->id_jurusan,
            'tingkat' => $siswaKelas->kelas->tingkat,
            'rombel' => $siswaKelas->kelas->rombel,
            'status_siswa' => 'aktif',
        ]))
            ->assertOk()
            ->assertSeeText($siswa->nama_siswa)
            ->assertDontSeeText($siswaLain->nama_siswa)
            ->assertDontSeeText($siswaLulus->nama_siswa)
            ->assertSee('name="tingkat"', false)
            ->assertSee('name="rombel"', false)
            ->assertDontSee('name="id_kelas"', false);

        $this->actingAs($user)->get(route('pembayaran.index', [
            'id_jurusan' => $siswaKelas->kelas->id_jurusan,
            'tingkat' => $siswaKelas->kelas->tingkat,
            'rombel' => 5,
            'status_siswa' => 'aktif',
        ]))
            ->assertOk()
            ->assertSeeText($siswaLain->nama_siswa)
            ->assertDontSeeText($siswa->nama_siswa);

        $this->actingAs($user)->get(route('pembayaran.index', ['status_siswa' => 'lulus']))
            ->assertOk()
            ->assertSeeText($siswaLulus->nama_siswa)
            ->assertDontSeeText($siswa->nama_siswa);
    }

    public function test_petugas_can_process_single_payment_and_server_ignores_client_total(): void
    {
        $user = User::factory()->create();
        [$siswa, $tagihan] = $this->siswaDenganTagihan();

        $this->actingAs($user)->post(route('pembayaran.store', $siswa), [
            'id_tagihan' => [$tagihan->id_tagihan],
            'total_bayar' => 1,
        ])->assertRedirect(route('pembayaran.kwitansi.show', Pembayaran::query()
            ->where('id_siswa', $siswa->id_siswa)
            ->sole()));

        $pembayaran = Pembayaran::query()->where('id_siswa', $siswa->id_siswa)->sole();

        $this->assertMatchesRegularExpression('/^SPP-\d{8}-[0-9A-HJKMNP-TV-Z]{26}$/', $pembayaran->no_kwitansi);
        $this->assertDatabaseHas('pembayaran', [
            'id_pembayaran' => $pembayaran->id_pembayaran,
            'id_siswa' => $siswa->id_siswa,
            'id_user' => $user->id_user,
            'total_bayar' => 150000,
        ]);
        $this->assertDatabaseHas('detail_pembayaran', [
            'id_pembayaran' => $pembayaran->id_pembayaran,
            'id_tagihan' => $tagihan->id_tagihan,
            'nominal_bayar' => 150000,
        ]);
        $this->assertSame('lunas', $tagihan->fresh()->status);
        $this->assertNotNull($tagihan->fresh()->tanggal_lunas);
    }

    public function test_service_processes_multiple_past_current_and_future_bills_in_one_transaction(): void
    {
        $user = User::factory()->create();
        [$siswa, $tagihanJuli, $siswaKelas, $tarif] = $this->siswaDenganTagihan();
        $tagihanAgustus = $this->buatTagihan($siswa, $siswaKelas, $tarif, 8, 2045);
        $tagihanSeptember = $this->buatTagihan($siswa, $siswaKelas, $tarif, 9, 2045);

        $pembayaran = app(PembayaranService::class)->bayar($user, $siswa, [
            $tagihanJuli->id_tagihan,
            $tagihanAgustus->id_tagihan,
            $tagihanSeptember->id_tagihan,
        ]);

        $this->assertDatabaseHas('pembayaran', [
            'id_pembayaran' => $pembayaran->id_pembayaran,
            'total_bayar' => 450000,
        ]);
        $this->assertSame(3, $pembayaran->detailPembayaran()->count());
        $this->assertSame(3, TagihanSpp::query()->whereIn('id_tagihan', [
            $tagihanJuli->id_tagihan,
            $tagihanAgustus->id_tagihan,
            $tagihanSeptember->id_tagihan,
        ])->where('status', 'lunas')->count());
    }

    public function test_payment_requires_all_unpaid_previous_bills_to_be_selected(): void
    {
        $user = User::factory()->create();
        [$siswa, $tagihanJuli, $siswaKelas, $tarif] = $this->siswaDenganTagihan();
        $tagihanAgustus = $this->buatTagihan($siswa, $siswaKelas, $tarif, 8, 2045);

        $this->actingAs($user)
            ->from(route('pembayaran.show', $siswa))
            ->post(route('pembayaran.store', $siswa), ['id_tagihan' => [$tagihanAgustus->id_tagihan]])
            ->assertRedirect(route('pembayaran.show', $siswa))
            ->assertSessionHasErrors([
                'id_tagihan' => 'Pilih seluruh tagihan periode sebelumnya yang belum lunas dalam transaksi ini.',
            ]);

        $this->assertSame('belum_bayar', $tagihanJuli->fresh()->status);
        $this->assertSame('belum_bayar', $tagihanAgustus->fresh()->status);
        $this->assertSame(0, Pembayaran::query()->where('id_siswa', $siswa->id_siswa)->count());

        $this->actingAs($user)->post(route('pembayaran.store', $siswa), [
            'id_tagihan' => [$tagihanJuli->id_tagihan, $tagihanAgustus->id_tagihan],
        ])->assertRedirect();

        $this->assertSame('lunas', $tagihanJuli->fresh()->status);
        $this->assertSame('lunas', $tagihanAgustus->fresh()->status);
    }

    public function test_transaction_page_orders_academic_periods_from_july_to_june(): void
    {
        $user = User::factory()->create();
        [$siswa, $tagihanJuli, $siswaKelas, $tarif] = $this->siswaDenganTagihan();
        $this->buatTagihan($siswa, $siswaKelas, $tarif, 1, 2046);

        $this->actingAs($user)->get(route('pembayaran.show', $siswa))
            ->assertOk()
            ->assertSeeInOrder(["Juli {$tagihanJuli->tahun}", 'Januari 2046']);
    }

    public function test_transaction_page_requires_confirmation_before_payment_submission(): void
    {
        $user = User::factory()->create();
        [$siswa] = $this->siswaDenganTagihan();

        $this->actingAs($user)->get(route('pembayaran.show', $siswa))
            ->assertOk()
            ->assertSee('data-confirm-title="Proses pembayaran?"', false)
            ->assertSee('data-confirm-submit="Proses Pembayaran"', false);
    }

    public function test_payment_of_a_paid_bill_is_rejected_without_creating_new_payment(): void
    {
        $user = User::factory()->create();
        [$siswa, $tagihan] = $this->siswaDenganTagihan();
        $tagihan->update([
            'status' => 'lunas',
            'tanggal_lunas' => now(),
        ]);

        $this->actingAs($user)
            ->from(route('pembayaran.show', $siswa))
            ->post(route('pembayaran.store', $siswa), ['id_tagihan' => [$tagihan->id_tagihan]])
            ->assertRedirect(route('pembayaran.show', $siswa))
            ->assertSessionHasErrors('id_tagihan');

        $this->assertSame(0, Pembayaran::query()->where('id_siswa', $siswa->id_siswa)->count());
    }

    public function test_payment_requires_at_least_one_bill_with_a_clear_validation_message(): void
    {
        $user = User::factory()->create();
        [$siswa] = $this->siswaDenganTagihan();

        $this->actingAs($user)
            ->from(route('pembayaran.show', $siswa))
            ->post(route('pembayaran.store', $siswa), [])
            ->assertRedirect(route('pembayaran.show', $siswa))
            ->assertSessionHasErrors([
                'id_tagihan' => 'Pilih minimal satu tagihan untuk dibayar.',
            ]);
    }

    public function test_payment_is_rolled_back_when_creating_payment_detail_fails(): void
    {
        $user = User::factory()->create();
        [$siswa, $tagihan] = $this->siswaDenganTagihan();
        DetailPembayaran::creating(fn () => throw new RuntimeException('Simulasi kegagalan detail pembayaran.'));

        try {
            app(PembayaranService::class)->bayar($user, $siswa, [$tagihan->id_tagihan]);
            $this->fail('Pembayaran seharusnya gagal saat detail pembayaran dibuat.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulasi kegagalan detail pembayaran.', $exception->getMessage());
        } finally {
            DetailPembayaran::flushEventListeners();
        }

        $this->assertSame(0, Pembayaran::query()->where('id_siswa', $siswa->id_siswa)->count());
        $this->assertSame(0, DetailPembayaran::query()->where('id_tagihan', $tagihan->id_tagihan)->count());
        $this->assertSame('belum_bayar', $tagihan->fresh()->status);
        $this->assertNull($tagihan->fresh()->tanggal_lunas);
    }

    public function test_database_error_during_payment_is_reported_without_partial_data(): void
    {
        $user = User::factory()->create();
        [$siswa, $tagihan] = $this->siswaDenganTagihan();
        DetailPembayaran::creating(fn () => throw new QueryException(
            'mysql',
            'insert into detail_pembayaran',
            [],
            new PDOException('Simulasi constraint database.'),
        ));

        try {
            $this->actingAs($user)
                ->from(route('pembayaran.show', $siswa))
                ->post(route('pembayaran.store', $siswa), ['id_tagihan' => [$tagihan->id_tagihan]])
                ->assertRedirect(route('pembayaran.show', $siswa))
                ->assertSessionHasErrors([
                    'id_tagihan' => 'Pembayaran tidak dapat diproses karena data tagihan baru saja berubah. Muat ulang halaman dan coba lagi.',
                ]);
        } finally {
            DetailPembayaran::flushEventListeners();
        }

        $this->assertSame(0, Pembayaran::query()->where('id_siswa', $siswa->id_siswa)->count());
        $this->assertSame('belum_bayar', $tagihan->fresh()->status);
        $this->assertNull($tagihan->fresh()->tanggal_lunas);
    }

    public function test_service_rejects_tagihan_from_another_student(): void
    {
        $user = User::factory()->create();
        [$siswa] = $this->siswaDenganTagihan();
        [, $tagihanMilikSiswaLain] = $this->siswaDenganTagihan('4511002');

        $this->expectException(ValidationException::class);
        app(PembayaranService::class)->bayar($user, $siswa, [$tagihanMilikSiswaLain->id_tagihan]);
    }

    /**
     * @return array{0: Siswa, 1: TagihanSpp, 2: SiswaKelas, 3: TarifSpp}
     */
    private function siswaDenganTagihan(string $nipd = '4511001'): array
    {
        $jurusan = Jurusan::create([
            'kode_jurusan' => "PYM{$nipd}",
            'nama_jurusan' => "Pembayaran {$nipd}",
        ]);
        $kelas = Kelas::create([
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 1,
            'rombel' => 1,
            'nama_kelas' => "X PYM {$nipd}",
        ]);
        $tahunMulai = $nipd === '4511001' ? 2045 : 2046;
        $tahunAjaran = TahunAjaran::create([
            'tahun_ajaran' => "{$tahunMulai}/".($tahunMulai + 1),
            'tanggal_mulai' => "{$tahunMulai}-07-01",
            'tanggal_selesai' => ($tahunMulai + 1).'-06-30',
            'aktif' => $nipd === '4511001',
        ]);
        $tarif = TarifSpp::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tingkat' => 1,
            'nominal' => 150000,
        ]);
        $siswa = Siswa::create([
            'nipd' => $nipd,
            'nama_siswa' => "Siswa {$nipd}",
            'jenis_kelamin' => 'L',
            'angkatan' => 2045,
            'status_siswa' => 'aktif',
        ]);
        $siswaKelas = SiswaKelas::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
        ]);

        return [$siswa, $this->buatTagihan($siswa, $siswaKelas, $tarif, 7, $tahunMulai), $siswaKelas, $tarif];
    }

    private function buatTagihan(Siswa $siswa, SiswaKelas $siswaKelas, TarifSpp $tarif, int $bulan, int $tahun): TagihanSpp
    {
        return TagihanSpp::create([
            'id_siswa' => $siswa->id_siswa,
            'id_siswa_kelas' => $siswaKelas->id_siswa_kelas,
            'id_tarif' => $tarif->id_tarif,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'nominal' => $tarif->nominal,
            'status' => 'belum_bayar',
        ]);
    }
}
