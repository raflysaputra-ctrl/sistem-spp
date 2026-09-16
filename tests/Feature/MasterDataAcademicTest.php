<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\TarifSpp;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterDataAcademicTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_access_academic_master_data(): void
    {
        $this->get(route('master.tahun-ajaran.index'))
            ->assertRedirect(route('login'));
    }

    public function test_petugas_can_view_academic_master_data_and_filter_kelas(): void
    {
        $user = User::factory()->create();
        $this->buatTahunAjaranAktif('2030/2031');
        TahunAjaran::create([
            'tahun_ajaran' => '2031/2032',
            'tanggal_mulai' => '2031-07-01',
            'tanggal_selesai' => '2032-06-30',
            'status' => 'persiapan',
        ]);
        $jurusan = Jurusan::create(['kode_jurusan' => 'TST', 'nama_jurusan' => 'Test Jurusan']);
        $kelas = Kelas::create([
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 1,
            'rombel' => 2,
            'nama_kelas' => 'X TST 2',
        ]);

        $this->actingAs($user)->get(route('master.jurusan.index'))
            ->assertOk()
            ->assertSeeText('TST');

        $this->get(route('master.kelas.index', [
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => $kelas->tingkat,
            'rombel' => $kelas->rombel,
        ]))->assertOk()
            ->assertSeeText('X TST 2');

        $this->get(route('master.tahun-ajaran.index'))
            ->assertOk()
            ->assertSeeText([
                'Aktif',
                'Tahun ajaran berikutnya sudah disiapkan.',
            ])
            ->assertSeeHtml('data-confirm-input-type="password"');
    }

    public function test_store_automatically_prepares_only_the_next_academic_year(): void
    {
        $user = User::factory()->create();
        $tahunAjaranAktif = $this->buatTahunAjaranAktif('2031/2032');

        $this->actingAs($user)->post(route('master.tahun-ajaran.store'), [
            'tahun_ajaran' => '2099/2100',
        ])->assertRedirect(route('master.tahun-ajaran.index'));

        $this->assertDatabaseHas('tahun_ajaran', [
            'tahun_ajaran' => '2032/2033',
            'tanggal_mulai' => '2032-07-01',
            'tanggal_selesai' => '2033-06-30',
            'aktif' => false,
            'status' => 'persiapan',
        ]);

        $this->post(route('master.tahun-ajaran.store'))
            ->assertRedirect(route('master.tahun-ajaran.index'))
            ->assertSessionHas('error');

        $this->assertSame(1, TahunAjaran::query()
            ->whereDate('tanggal_mulai', '>', $tahunAjaranAktif->tanggal_selesai)
            ->count());
    }

    public function test_create_page_is_not_available_for_manual_year_input(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/master-data/tahun-ajaran/create')
            ->assertStatus(405);
    }

    public function test_activation_requires_the_immediate_prepared_successor(): void
    {
        $user = $this->buatPetugas();
        $this->buatTahunAjaranAktif('2033/2034');
        $tahunLoncat = TahunAjaran::create([
            'tahun_ajaran' => '2035/2036',
            'tanggal_mulai' => '2035-07-01',
            'tanggal_selesai' => '2036-06-30',
            'status' => 'persiapan',
        ]);
        $this->buatTarifLengkap($tahunLoncat);

        $this->actingAs($user)
            ->patch(route('master.tahun-ajaran.activate', $tahunLoncat), ['password' => 'rahasia'])
            ->assertRedirect(route('master.tahun-ajaran.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('tahun_ajaran', [
            'id_tahun_ajaran' => $tahunLoncat->id_tahun_ajaran,
            'aktif' => false,
            'status' => 'persiapan',
        ]);
    }

    public function test_activation_closes_the_current_year_and_rejects_reactivation_of_archives(): void
    {
        $user = $this->buatPetugas();
        $tahunAjaranAktif = $this->buatTahunAjaranAktif('2036/2037');
        $tahunAjaranBerikutnya = TahunAjaran::create([
            'tahun_ajaran' => '2037/2038',
            'tanggal_mulai' => '2037-07-01',
            'tanggal_selesai' => '2038-06-30',
            'status' => 'persiapan',
        ]);
        $this->buatTarifLengkap($tahunAjaranBerikutnya);

        $this->actingAs($user)
            ->patch(route('master.tahun-ajaran.activate', $tahunAjaranBerikutnya), ['password' => 'rahasia'])
            ->assertRedirect(route('master.tahun-ajaran.index'));

        $this->assertDatabaseHas('tahun_ajaran', [
            'id_tahun_ajaran' => $tahunAjaranAktif->id_tahun_ajaran,
            'aktif' => false,
            'status' => 'ditutup',
        ]);
        $this->assertDatabaseHas('tahun_ajaran', [
            'id_tahun_ajaran' => $tahunAjaranBerikutnya->id_tahun_ajaran,
            'aktif' => true,
            'status' => 'aktif',
        ]);

        $this->patch(route('master.tahun-ajaran.activate', $tahunAjaranAktif), ['password' => 'rahasia'])
            ->assertRedirect(route('master.tahun-ajaran.index'))
            ->assertSessionHas('error', 'Tahun ajaran yang sudah ditutup tidak dapat diaktifkan kembali.');
    }

    public function test_activation_requires_the_current_petugas_password(): void
    {
        $user = $this->buatPetugas();
        $tahunAjaranAktif = $this->buatTahunAjaranAktif('2037/2038');
        $tahunAjaranBerikutnya = TahunAjaran::create([
            'tahun_ajaran' => '2038/2039',
            'tanggal_mulai' => '2038-07-01',
            'tanggal_selesai' => '2039-06-30',
            'status' => 'persiapan',
        ]);
        $this->buatTarifLengkap($tahunAjaranBerikutnya);

        $this->actingAs($user)
            ->patch(route('master.tahun-ajaran.activate', $tahunAjaranBerikutnya), ['password' => 'salah'])
            ->assertRedirect(route('master.tahun-ajaran.index'))
            ->assertSessionHas('error', 'Password petugas tidak sesuai. Tahun ajaran tidak diaktifkan.');

        $this->assertDatabaseHas('tahun_ajaran', [
            'id_tahun_ajaran' => $tahunAjaranAktif->id_tahun_ajaran,
            'aktif' => true,
            'status' => 'aktif',
        ]);
        $this->assertDatabaseHas('tahun_ajaran', [
            'id_tahun_ajaran' => $tahunAjaranBerikutnya->id_tahun_ajaran,
            'aktif' => false,
            'status' => 'persiapan',
        ]);

        $this->patch(route('master.tahun-ajaran.activate', $tahunAjaranBerikutnya))
            ->assertSessionHasErrors('password');
    }

    public function test_only_prepared_years_can_be_edited_or_deleted(): void
    {
        $user = User::factory()->create();
        $tahunAjaranAktif = $this->buatTahunAjaranAktif('2038/2039');
        $tahunAjaranPersiapan = TahunAjaran::create([
            'tahun_ajaran' => '2039/2040',
            'tanggal_mulai' => '2039-07-01',
            'tanggal_selesai' => '2040-06-30',
            'status' => 'persiapan',
        ]);

        $this->actingAs($user)
            ->get(route('master.tahun-ajaran.edit', $tahunAjaranPersiapan))
            ->assertOk()
            ->assertSeeText('Simpan')
            ->assertSeeHtml('name="tanggal_selesai"')
            ->assertDontSeeHtml('name="tahun_ajaran"');

        $this->put(route('master.tahun-ajaran.update', $tahunAjaranPersiapan), [
            'tahun_ajaran' => '2041/2042',
            'tanggal_mulai' => '2041-07-01',
            'tanggal_selesai' => '2040-07-15',
        ])->assertRedirect(route('master.tahun-ajaran.index'));

        $this->assertDatabaseHas('tahun_ajaran', [
            'id_tahun_ajaran' => $tahunAjaranPersiapan->id_tahun_ajaran,
            'tahun_ajaran' => '2039/2040',
            'tanggal_mulai' => '2039-07-01',
            'tanggal_selesai' => '2040-07-15',
        ]);

        $this->from(route('master.tahun-ajaran.edit', $tahunAjaranPersiapan))
            ->put(route('master.tahun-ajaran.update', $tahunAjaranPersiapan), [
                'tanggal_selesai' => '2039-07-01',
            ])->assertRedirect(route('master.tahun-ajaran.edit', $tahunAjaranPersiapan))
            ->assertSessionHasErrors('tanggal_selesai');

        $this->delete(route('master.tahun-ajaran.destroy', $tahunAjaranAktif))
            ->assertRedirect(route('master.tahun-ajaran.index'))
            ->assertSessionHas('error', 'Hanya tahun ajaran berstatus persiapan yang dapat dihapus.');

        $this->delete(route('master.tahun-ajaran.destroy', $tahunAjaranPersiapan))
            ->assertRedirect(route('master.tahun-ajaran.index'));
    }

    public function test_activation_can_be_cancelled_and_restored_before_the_year_is_used(): void
    {
        $user = $this->buatPetugas();
        $tahunAjaranAktif = $this->buatTahunAjaranAktif('2040/2041');
        $tahunAjaranPersiapan = TahunAjaran::create([
            'tahun_ajaran' => '2041/2042',
            'tanggal_mulai' => '2041-07-01',
            'tanggal_selesai' => '2042-06-30',
            'status' => 'persiapan',
        ]);
        $this->buatTarifLengkap($tahunAjaranPersiapan);

        $this->actingAs($user)->patch(route('master.tahun-ajaran.activate', $tahunAjaranPersiapan), ['password' => 'rahasia']);

        $this->patch(route('master.tahun-ajaran.deactivate', $tahunAjaranPersiapan))
            ->assertRedirect(route('master.tahun-ajaran.index'));

        $this->assertDatabaseHas('tahun_ajaran', [
            'id_tahun_ajaran' => $tahunAjaranPersiapan->id_tahun_ajaran,
            'aktif' => false,
            'status' => 'persiapan',
        ]);
        $this->assertDatabaseHas('tahun_ajaran', [
            'id_tahun_ajaran' => $tahunAjaranAktif->id_tahun_ajaran,
            'status' => 'ditutup',
        ]);

        TahunAjaran::query()
            ->where('status', 'persiapan')
            ->where('id_tahun_ajaran', '!=', $tahunAjaranPersiapan->id_tahun_ajaran)
            ->update(['status' => 'ditutup']);

        $this->patch(route('master.tahun-ajaran.activate', $tahunAjaranPersiapan), ['password' => 'rahasia'])
            ->assertRedirect(route('master.tahun-ajaran.index'));

        $this->assertDatabaseHas('tahun_ajaran', [
            'id_tahun_ajaran' => $tahunAjaranPersiapan->id_tahun_ajaran,
            'aktif' => true,
            'status' => 'aktif',
        ]);
    }

    public function test_deactivation_is_rejected_when_active_year_has_class_history(): void
    {
        $user = User::factory()->create();
        $tahunAjaranAktif = $this->buatTahunAjaranAktif('2042/2043');
        $jurusan = Jurusan::create(['kode_jurusan' => 'DTA', 'nama_jurusan' => 'Data Test']);
        $kelas = Kelas::create([
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 1,
            'rombel' => 1,
            'nama_kelas' => 'X DTA 1',
        ]);
        $siswa = Siswa::create([
            'nipd' => 'DTA-001',
            'nama_siswa' => 'Siswa Deaktivasi',
            'jenis_kelamin' => 'L',
            'angkatan' => 2042,
            'status_siswa' => 'aktif',
        ]);
        SiswaKelas::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'id_tahun_ajaran' => $tahunAjaranAktif->id_tahun_ajaran,
        ]);

        $this->actingAs($user)
            ->patch(route('master.tahun-ajaran.deactivate', $tahunAjaranAktif))
            ->assertRedirect(route('master.tahun-ajaran.index'))
            ->assertSessionHas('error', 'Aktivasi tidak dapat dibatalkan karena tahun ajaran sudah memiliki riwayat kelas siswa.');
    }

    public function test_reactivation_without_an_active_year_requires_the_earliest_prepared_year(): void
    {
        $user = $this->buatPetugas();
        $tahunAjaranAktif = $this->buatTahunAjaranAktif('2043/2044');
        TahunAjaran::query()
            ->where('status', 'persiapan')
            ->update(['status' => 'ditutup']);
        $tahunAjaranAktif->update([
            'aktif' => false,
            'status' => 'persiapan',
        ]);
        $tahunAjaranBerikutnya = TahunAjaran::create([
            'tahun_ajaran' => '2044/2045',
            'tanggal_mulai' => '2044-07-01',
            'tanggal_selesai' => '2045-06-30',
            'status' => 'persiapan',
        ]);
        $this->buatTarifLengkap($tahunAjaranAktif);

        $this->actingAs($user)
            ->patch(route('master.tahun-ajaran.activate', $tahunAjaranBerikutnya), ['password' => 'rahasia'])
            ->assertRedirect(route('master.tahun-ajaran.index'))
            ->assertSessionHas('error', 'Aktifkan tahun ajaran 2043/2044 terlebih dahulu.');

        $this->patch(route('master.tahun-ajaran.activate', $tahunAjaranAktif), ['password' => 'rahasia'])
            ->assertRedirect(route('master.tahun-ajaran.index'));

        $this->assertDatabaseHas('tahun_ajaran', [
            'id_tahun_ajaran' => $tahunAjaranAktif->id_tahun_ajaran,
            'aktif' => true,
            'status' => 'aktif',
        ]);
    }

    public function test_kelas_filter_accepts_rombel_above_four_and_rejects_invalid_values(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('master.kelas.index'))
            ->get(route('master.kelas.index', ['tingkat' => 4, 'rombel' => 256]));

        $response->assertRedirect(route('master.kelas.index'));
        $response->assertSessionHasErrors(['tingkat', 'rombel']);

        $this->get(route('master.kelas.index', ['rombel' => 5]))
            ->assertOk();
    }

    private function buatTahunAjaranAktif(string $tahunAjaran): TahunAjaran
    {
        TahunAjaran::aktif()->update([
            'aktif' => false,
            'status' => 'ditutup',
        ]);

        [$tahunMulai] = explode('/', $tahunAjaran);

        return TahunAjaran::create([
            'tahun_ajaran' => $tahunAjaran,
            'tanggal_mulai' => "{$tahunMulai}-07-01",
            'tanggal_selesai' => ((int) $tahunMulai + 1).'-06-30',
            'aktif' => true,
            'status' => 'aktif',
        ]);
    }

    private function buatPetugas(): User
    {
        return User::factory()->create([
            'password' => Hash::make('rahasia'),
        ]);
    }

    private function buatTarifLengkap(TahunAjaran $tahunAjaran): void
    {
        foreach ([1 => 150000, 2 => 120000, 3 => 120000] as $tingkat => $nominal) {
            TarifSpp::create([
                'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
                'tingkat' => $tingkat,
                'nominal' => $nominal,
            ]);
        }
    }
}
