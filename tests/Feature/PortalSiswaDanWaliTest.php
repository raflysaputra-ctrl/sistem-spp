<?php

namespace Tests\Feature;

use App\Models\ArsipKwitansiSiswa;
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
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortalSiswaDanWaliTest extends TestCase
{
    use DatabaseTransactions;

    public function test_wali_can_find_bills_and_only_active_payment_marks_them_paid(): void
    {
        [$siswa, $tagihan, $petugas] = $this->buatSiswaDanTagihan();
        $tagihan->update(['status' => 'lunas', 'tanggal_lunas' => '2044-07-11 08:00:00']);

        $dibatalkan = Pembayaran::create([
            'no_kwitansi' => 'BATAL-RAHASIA',
            'id_siswa' => $siswa->id_siswa,
            'id_user' => $petugas->id_user,
            'tanggal_bayar' => '2044-07-11 08:00:00',
            'total_bayar' => 150000,
            'status' => 'dibatalkan',
        ]);
        DetailPembayaran::create([
            'id_pembayaran' => $dibatalkan->id_pembayaran,
            'id_tagihan' => $tagihan->id_tagihan,
            'nominal_bayar' => 150000,
        ]);
        $aktif = Pembayaran::create([
            'no_kwitansi' => 'AKTIF-RAHASIA',
            'id_siswa' => $siswa->id_siswa,
            'id_user' => $petugas->id_user,
            'tanggal_bayar' => '2044-07-20 09:15:00',
            'total_bayar' => 150000,
            'status' => 'aktif',
        ]);
        DetailPembayaran::create([
            'id_pembayaran' => $aktif->id_pembayaran,
            'id_tagihan' => $tagihan->id_tagihan,
            'nominal_bayar' => 150000,
        ]);

        $this->get(route('wali.portal', ['nipd' => $siswa->nipd]))
            ->assertOk()
            ->assertSeeText(['Siswa Portal', 'X PRT 1', 'Juli 2044', 'Sudah dibayar', '20/07/2044'])
            ->assertDontSee(['150.000', 'AKTIF-RAHASIA', 'BATAL-RAHASIA', 'Login Petugas']);
    }

    public function test_wali_sees_clear_messages_for_unknown_student_and_missing_bills(): void
    {
        $this->get(route('wali.portal', ['nipd' => 'tidak-ada']))
            ->assertOk()
            ->assertSeeText('Data siswa dengan NIPD tersebut tidak ditemukan.');

        [$siswa] = $this->buatSiswaDanTagihan(false);

        $this->get(route('wali.portal', ['nipd' => $siswa->nipd]))
            ->assertOk()
            ->assertSeeText('Belum ada tagihan SPP untuk siswa ini.');
    }

    public function test_student_can_only_access_their_portal_and_tu_can_create_and_reset_the_account(): void
    {
        [$siswa, , $petugas] = $this->buatSiswaDanTagihan();

        $this->actingAs($petugas)
            ->post(route('master.siswa.akun.store', $siswa), [
                'username' => 'siswa.portal',
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertRedirect(route('master.siswa.akun.show', $siswa));

        $akunSiswa = User::query()->where('username', 'siswa.portal')->firstOrFail();
        $this->assertSame('siswa', $akunSiswa->role);
        $this->assertSame($siswa->id_siswa, $akunSiswa->id_siswa);
        $this->assertTrue(Hash::check('password-baru', $akunSiswa->password));

        $this->actingAs($petugas)
            ->patch(route('master.siswa.akun.password', $siswa), [
                'password' => 'password-reset',
                'password_confirmation' => 'password-reset',
            ])
            ->assertRedirect(route('master.siswa.akun.show', $siswa));
        $this->assertTrue(Hash::check('password-reset', $akunSiswa->fresh()->password));

        $this->post(route('logout'));
        $this->post(route('siswa.login.attempt'), [
            'username' => 'siswa.portal',
            'password' => 'password-reset',
        ])->assertRedirect(route('siswa.status'));
        $this->assertAuthenticatedAs($akunSiswa, 'siswa');

        $this->get(route('siswa.status'))->assertOk()->assertSeeText(['Siswa Portal', 'Juli 2044']);
        $this->get(route('home'))->assertRedirect(route('login'));
        $this->get(route('master.siswa.index'))->assertRedirect(route('login'));
        $this->get(route('arsip-kwitansi.index'))->assertRedirect(route('login'));
    }

    public function test_student_uploads_a_private_compressed_archive_without_changing_payment_or_bill(): void
    {
        Storage::fake('local');
        [$siswa, $tagihan, $petugas] = $this->buatSiswaDanTagihan();
        $tagihan->update(['status' => 'lunas', 'tanggal_lunas' => '2044-07-20 09:15:00']);
        $pembayaran = Pembayaran::create([
            'no_kwitansi' => 'ARSIP-001',
            'id_siswa' => $siswa->id_siswa,
            'id_user' => $petugas->id_user,
            'tanggal_bayar' => '2044-07-20 09:15:00',
            'total_bayar' => 150000,
            'status' => 'aktif',
        ]);
        DetailPembayaran::create([
            'id_pembayaran' => $pembayaran->id_pembayaran,
            'id_tagihan' => $tagihan->id_tagihan,
            'nominal_bayar' => 150000,
        ]);
        $akunSiswa = $this->buatAkunSiswa($siswa);
        $tanggalLunas = $tagihan->tanggal_lunas->toDateTimeString();

        $this->actingAs($akunSiswa, 'siswa')
            ->post(route('siswa.kwitansi.store'), [
                'id_pembayaran' => $pembayaran->id_pembayaran,
                'foto' => UploadedFile::fake()->image('kwitansi.jpg', 1200, 800),
            ])
            ->assertRedirect(route('siswa.status'));

        $arsip = ArsipKwitansiSiswa::query()->firstOrFail();
        Storage::disk('local')->assertExists($arsip->path);
        $this->assertSame($siswa->id_siswa, $arsip->id_siswa);
        $this->assertSame($pembayaran->id_pembayaran, $arsip->id_pembayaran);
        $this->assertLessThanOrEqual(2 * 1024 * 1024, $arsip->ukuran_file);
        $this->assertSame('aktif', $pembayaran->fresh()->status);
        $this->assertSame(150000, (int) $pembayaran->fresh()->total_bayar);
        $this->assertSame('lunas', $tagihan->fresh()->status);
        $this->assertSame($tanggalLunas, $tagihan->fresh()->tanggal_lunas->toDateTimeString());
        $this->assertSame(1, DetailPembayaran::query()->where('id_pembayaran', $pembayaran->id_pembayaran)->count());

        $this->get(route('arsip-kwitansi.show', $arsip))->assertRedirect(route('login'));
        $this->actingAs($petugas, 'web')->get(route('arsip-kwitansi.show', $arsip))->assertOk();
        $this->get('/storage/'.$arsip->path)->assertForbidden();
    }

    public function test_student_can_store_an_unlinked_receipt_but_cannot_link_another_students_payment(): void
    {
        Storage::fake('local');
        [$siswa] = $this->buatSiswaDanTagihan();
        [$siswaLain, , $petugas] = $this->buatSiswaDanTagihan();
        $pembayaranLain = Pembayaran::create([
            'no_kwitansi' => 'SISWA-LAIN',
            'id_siswa' => $siswaLain->id_siswa,
            'id_user' => $petugas->id_user,
            'tanggal_bayar' => '2044-07-20 09:15:00',
            'total_bayar' => 150000,
            'status' => 'aktif',
        ]);
        $akunSiswa = $this->buatAkunSiswa($siswa);

        $this->actingAs($akunSiswa, 'siswa')
            ->from(route('siswa.status'))
            ->post(route('siswa.kwitansi.store'), [
                'id_pembayaran' => $pembayaranLain->id_pembayaran,
                'foto' => UploadedFile::fake()->image('kwitansi.png', 300, 300),
            ])
            ->assertRedirect(route('siswa.status'))
            ->assertSessionHasErrors('id_pembayaran');

        $this->post(route('siswa.kwitansi.store'), [
            'foto' => UploadedFile::fake()->image('kwitansi.png', 300, 300),
        ])->assertRedirect(route('siswa.status'));

        $this->assertDatabaseHas('arsip_kwitansi_siswa', [
            'id_siswa' => $siswa->id_siswa,
            'id_pembayaran' => null,
        ]);
    }

    public function test_petugas_and_student_can_stay_logged_in_at_the_same_time_and_log_out_independently(): void
    {
        [$siswa, , $petugas] = $this->buatSiswaDanTagihan();
        $akunSiswa = $this->buatAkunSiswa($siswa);

        $this->post(route('login.attempt'), [
            'username' => $petugas->username,
            'password' => 'password',
        ])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($petugas, 'web');
        $csrfTokenPetugas = $this->app['session.store']->token();

        $this->post(route('siswa.login.attempt'), [
            'username' => $akunSiswa->username,
            'password' => 'password',
        ])->assertRedirect(route('siswa.status'));
        $this->assertAuthenticatedAs($petugas, 'web');
        $this->assertAuthenticatedAs($akunSiswa, 'siswa');
        $this->assertSame($csrfTokenPetugas, $this->app['session.store']->token());

        $this->get(route('siswa.login'))->assertRedirect(route('siswa.status'));
        $this->get(route('home'))->assertOk();
        $this->get(route('siswa.status'))->assertOk();

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest('web');
        $this->assertAuthenticatedAs($akunSiswa, 'siswa');
        $this->assertSame($csrfTokenPetugas, $this->app['session.store']->token());
        $this->get(route('siswa.status'))->assertOk();

        $this->post(route('login.attempt'), [
            'username' => $petugas->username,
            'password' => 'password',
        ])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($petugas, 'web');
        $this->assertAuthenticatedAs($akunSiswa, 'siswa');

        $csrfTokenSiswa = $this->app['session.store']->token();
        $this->post(route('siswa.logout'))->assertRedirect(route('siswa.login'));
        $this->assertGuest('siswa');
        $this->assertAuthenticatedAs($petugas, 'web');
        $this->assertSame($csrfTokenSiswa, $this->app['session.store']->token());
        $this->get(route('home'))->assertOk();
    }

    public function test_tu_logout_accepts_the_csrf_token_from_before_student_login(): void
    {
        [$siswa, , $petugas] = $this->buatSiswaDanTagihan();
        $akunSiswa = $this->buatAkunSiswa($siswa);
        $this->withMiddleware();

        $this->get(route('login'));
        $this->post(route('login.attempt'), [
            '_token' => $this->app['session.store']->token(),
            'username' => $petugas->username,
            'password' => 'password',
        ])->assertRedirect(route('home'));
        $tokenDashboardTu = $this->app['session.store']->token();

        $this->get(route('siswa.login'));
        $this->post(route('siswa.login.attempt'), [
            '_token' => $tokenDashboardTu,
            'username' => $akunSiswa->username,
            'password' => 'password',
        ])->assertRedirect(route('siswa.status'));

        $this->assertSame($tokenDashboardTu, $this->app['session.store']->token());
        $this->post(route('logout'), ['_token' => $tokenDashboardTu])
            ->assertRedirect(route('login'));
        $this->assertAuthenticatedAs($akunSiswa, 'siswa');
    }

    /**
     * @return array{0: Siswa, 1: TagihanSpp|null, 2: User}
     */
    private function buatSiswaDanTagihan(bool $buatTagihan = true): array
    {
        $kode = 'P'.fake()->unique()->numerify('###');
        $jurusan = Jurusan::create(['kode_jurusan' => $kode, 'nama_jurusan' => 'Portal']);
        $kelas = Kelas::create([
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 1,
            'rombel' => 1,
            'nama_kelas' => 'X PRT 1',
        ]);
        $tahunAjaran = TahunAjaran::create([
            'tahun_ajaran' => fake()->unique()->numerify('204#/204#'),
            'tanggal_mulai' => '2044-07-01',
            'tanggal_selesai' => '2045-06-30',
            'aktif' => true,
        ]);
        $tarif = TarifSpp::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tingkat' => 1,
            'nominal' => 150000,
        ]);
        $siswa = Siswa::create([
            'nipd' => fake()->unique()->numerify('PRT-####'),
            'nama_siswa' => 'Siswa Portal',
            'jenis_kelamin' => 'L',
            'angkatan' => 2044,
            'status_siswa' => 'aktif',
        ]);
        $siswaKelas = SiswaKelas::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
        ]);
        $tagihan = $buatTagihan ? TagihanSpp::create([
            'id_siswa' => $siswa->id_siswa,
            'id_siswa_kelas' => $siswaKelas->id_siswa_kelas,
            'id_tarif' => $tarif->id_tarif,
            'bulan' => 7,
            'tahun' => 2044,
            'nominal' => 150000,
            'status' => 'belum_bayar',
        ]) : null;

        return [$siswa, $tagihan, User::factory()->create()];
    }

    private function buatAkunSiswa(Siswa $siswa): User
    {
        return User::create([
            'nama' => $siswa->nama_siswa,
            'username' => fake()->unique()->userName(),
            'password' => 'password',
            'role' => 'siswa',
            'id_siswa' => $siswa->id_siswa,
        ]);
    }
}
