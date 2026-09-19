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

    public function test_student_cannot_upload_without_a_payment_or_to_another_students_payment(): void
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

        $this->from(route('siswa.status'))
            ->post(route('siswa.kwitansi.store'), [
                'foto' => UploadedFile::fake()->image('kwitansi.png', 300, 300),
            ])
            ->assertRedirect(route('siswa.status'))
            ->assertSessionHasErrors('id_pembayaran');

        $this->patch(route('siswa.kwitansi.update'), [
            'id_pembayaran' => $pembayaranLain->id_pembayaran,
            'foto' => UploadedFile::fake()->image('pengganti.png', 300, 300),
        ])->assertSessionHasErrors('id_pembayaran');

        $this->assertDatabaseCount('arsip_kwitansi_siswa', 0);
    }

    public function test_student_cannot_upload_or_replace_a_photo_for_a_cancelled_payment(): void
    {
        Storage::fake('local');
        [$siswa, , $petugas] = $this->buatSiswaDanTagihan();
        $pembayaran = Pembayaran::create([
            'no_kwitansi' => 'BATAL-UNGGAH',
            'id_siswa' => $siswa->id_siswa,
            'id_user' => $petugas->id_user,
            'tanggal_bayar' => '2044-07-20 09:15:00',
            'total_bayar' => 150000,
            'status' => 'dibatalkan',
        ]);
        $arsip = ArsipKwitansiSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_pembayaran' => $pembayaran->id_pembayaran,
            'path' => 'kwitansi-siswa/riwayat/batal.jpg',
            'mime_type' => 'image/jpeg',
            'ukuran_file' => 1024,
        ]);
        Storage::disk('local')->put($arsip->path, 'foto-lama');

        $this->actingAs($this->buatAkunSiswa($siswa), 'siswa')
            ->from(route('siswa.status'))
            ->post(route('siswa.kwitansi.store'), [
                'id_pembayaran' => $pembayaran->id_pembayaran,
                'foto' => UploadedFile::fake()->image('kwitansi.png', 300, 300),
            ])
            ->assertRedirect(route('siswa.status'))
            ->assertSessionHasErrors('id_pembayaran');

        $this->patch(route('siswa.kwitansi.update'), [
            'id_pembayaran' => $pembayaran->id_pembayaran,
            'foto' => UploadedFile::fake()->image('pengganti.png', 300, 300),
        ])->assertSessionHasErrors('id_pembayaran');

        $this->assertSame($arsip->path, $arsip->fresh()->path);
        Storage::disk('local')->assertExists($arsip->path);
    }

    public function test_one_payment_cannot_have_two_receipt_photos(): void
    {
        Storage::fake('local');
        [$siswa, $tagihan, $petugas] = $this->buatSiswaDanTagihan();
        $pembayaran = $this->buatPembayaran($siswa, $petugas, [$tagihan], 'SATU-FOTO');
        $akunSiswa = $this->buatAkunSiswa($siswa);

        $this->actingAs($akunSiswa, 'siswa')
            ->post(route('siswa.kwitansi.store'), [
                'id_pembayaran' => $pembayaran->id_pembayaran,
                'foto' => UploadedFile::fake()->image('pertama.jpg', 300, 300),
            ])
            ->assertRedirect(route('siswa.status'));

        $this->from(route('siswa.status'))
            ->post(route('siswa.kwitansi.store'), [
                'id_pembayaran' => $pembayaran->id_pembayaran,
                'foto' => UploadedFile::fake()->image('kedua.jpg', 300, 300),
            ])
            ->assertRedirect(route('siswa.status'))
            ->assertSessionHasErrors('id_pembayaran');

        $this->assertSame(1, ArsipKwitansiSiswa::query()->where('id_pembayaran', $pembayaran->id_pembayaran)->count());
    }

    public function test_student_can_replace_a_receipt_photo_without_creating_another_archive(): void
    {
        Storage::fake('local');
        [$siswa, $tagihan, $petugas] = $this->buatSiswaDanTagihan();
        $tagihan->update(['status' => 'lunas', 'tanggal_lunas' => '2044-07-20 09:15:00']);
        $pembayaran = $this->buatPembayaran($siswa, $petugas, [$tagihan], 'GANTI-FOTO');
        $akunSiswa = $this->buatAkunSiswa($siswa);

        $this->actingAs($akunSiswa, 'siswa')
            ->post(route('siswa.kwitansi.store'), [
                'id_pembayaran' => $pembayaran->id_pembayaran,
                'foto' => UploadedFile::fake()->image('lama.jpg', 300, 300),
            ]);

        $arsip = ArsipKwitansiSiswa::query()->firstOrFail();
        $pathLama = $arsip->path;
        $tanggalLunas = $tagihan->fresh()->tanggal_lunas->toDateTimeString();

        $this->get(route('siswa.status'))
            ->assertOk()
            ->assertSeeText('Ganti foto kwitansi');

        $this->patch(route('siswa.kwitansi.update'), [
            'id_pembayaran' => $pembayaran->id_pembayaran,
            'foto' => UploadedFile::fake()->image('baru.png', 400, 400),
        ])->assertRedirect(route('siswa.status'))
            ->assertSessionHas('status', 'Foto kwitansi berhasil diganti.');

        $arsip->refresh();
        $this->assertNotSame($pathLama, $arsip->path);
        $this->assertSame('image/png', $arsip->mime_type);
        $this->assertSame(1, ArsipKwitansiSiswa::query()->where('id_pembayaran', $pembayaran->id_pembayaran)->count());
        Storage::disk('local')->assertMissing($pathLama);
        Storage::disk('local')->assertExists($arsip->path);
        $this->assertSame('aktif', $pembayaran->fresh()->status);
        $this->assertSame('lunas', $tagihan->fresh()->status);
        $this->assertSame($tanggalLunas, $tagihan->fresh()->tanggal_lunas->toDateTimeString());
    }

    public function test_multi_month_payment_marks_every_period_card_as_having_a_receipt_photo(): void
    {
        [$siswa, $tagihanJuli, $petugas] = $this->buatSiswaDanTagihan();
        $tagihanAgustus = TagihanSpp::create([
            'id_siswa' => $siswa->id_siswa,
            'id_siswa_kelas' => $tagihanJuli->id_siswa_kelas,
            'id_tarif' => $tagihanJuli->id_tarif,
            'bulan' => 8,
            'tahun' => 2044,
            'nominal' => 150000,
            'status' => 'lunas',
            'tanggal_lunas' => '2044-07-20 09:15:00',
        ]);
        $pembayaran = $this->buatPembayaran($siswa, $petugas, [$tagihanJuli, $tagihanAgustus], 'MULTI-BULAN');

        $this->actingAs($this->buatAkunSiswa($siswa), 'siswa')
            ->get(route('siswa.status'))
            ->assertOk()
            ->assertSeeText(['MULTI-BULAN', '20/07/2044', 'Juli 2044, Agustus 2044']);

        ArsipKwitansiSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_pembayaran' => $pembayaran->id_pembayaran,
            'path' => 'kwitansi-siswa/riwayat/multi.jpg',
            'mime_type' => 'image/jpeg',
            'ukuran_file' => 1024,
        ]);

        $response = $this
            ->get(route('siswa.status'))
            ->assertOk()
            ->assertSeeText(['Juli 2044', 'Agustus 2044', 'Ganti foto kwitansi'])
            ->assertDontSeeText('Unggah foto kwitansi');

        $this->assertSame(2, substr_count($response->getContent(), 'Foto kwitansi sudah diunggah'));
    }

    public function test_legacy_unlinked_archive_remains_available_and_does_not_enable_unlinked_uploads(): void
    {
        [$siswa, , $petugas] = $this->buatSiswaDanTagihan();
        $arsip = ArsipKwitansiSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_pembayaran' => null,
            'path' => 'kwitansi-siswa/riwayat/legacy.jpg',
            'mime_type' => 'image/jpeg',
            'ukuran_file' => 1024,
        ]);

        $this->actingAs($this->buatAkunSiswa($siswa), 'siswa')
            ->get(route('siswa.status'))
            ->assertOk()
            ->assertSeeText('Belum ada transaksi pembayaran yang dapat dihubungkan dengan foto kwitansi.');

        $this->actingAs($petugas, 'web')
            ->get(route('arsip-kwitansi.index'))
            ->assertOk()
            ->assertSeeText('Tidak ditautkan');

        $this->assertDatabaseHas('arsip_kwitansi_siswa', [
            'id_arsip_kwitansi' => $arsip->id_arsip_kwitansi,
            'id_pembayaran' => null,
        ]);
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

    /**
     * @param  array<int, TagihanSpp>  $tagihanSpp
     */
    private function buatPembayaran(Siswa $siswa, User $petugas, array $tagihanSpp, string $nomorKwitansi): Pembayaran
    {
        $pembayaran = Pembayaran::create([
            'no_kwitansi' => $nomorKwitansi,
            'id_siswa' => $siswa->id_siswa,
            'id_user' => $petugas->id_user,
            'tanggal_bayar' => '2044-07-20 09:15:00',
            'total_bayar' => count($tagihanSpp) * 150000,
            'status' => 'aktif',
        ]);

        foreach ($tagihanSpp as $tagihan) {
            DetailPembayaran::create([
                'id_pembayaran' => $pembayaran->id_pembayaran,
                'id_tagihan' => $tagihan->id_tagihan,
                'nominal_bayar' => 150000,
            ]);
        }

        return $pembayaran;
    }
}
