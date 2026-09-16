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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class MasterDataSiswaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_access_siswa_data(): void
    {
        $this->get(route('master.siswa.index', ['cari' => '2501004']))
            ->assertRedirect(route('login'));

        $this->get(route('master.siswa.nonaktif'))
            ->assertRedirect(route('login'));

        $this->assertFalse(Route::has('master.siswa.restore'));
    }

    public function test_petugas_can_create_siswa_with_active_class_placement(): void
    {
        $user = User::factory()->create();
        [$kelas, $tahunAjaran] = $this->kelasDanTahunAjaranAktif();

        $response = $this->actingAs($user)->post(route('master.siswa.store'), [
            'nipd' => '2601001',
            'nama_siswa' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'angkatan' => 2090,
            'status_siswa' => 'aktif',
            'id_kelas' => $kelas->id_kelas,
        ]);

        $siswa = Siswa::query()->where('nipd', '2601001')->firstOrFail();

        $response->assertRedirect(route('master.siswa.index'));
        $this->assertDatabaseHas('siswa', ['id_siswa' => $siswa->id_siswa, 'nama_siswa' => 'Budi Santoso']);
        $this->assertDatabaseHas('siswa_kelas', [
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
        ]);
        $this->assertSame(12, $siswa->tagihanSpp()->count());
        $this->assertSame(12, $siswa->tagihanSpp()->where('status', 'belum_bayar')->count());
        $this->assertDatabaseHas('tagihan_spp', [
            'id_siswa' => $siswa->id_siswa,
            'bulan' => 7,
            'tahun' => 2090,
            'nominal' => 150000,
            'status' => 'belum_bayar',
        ]);
        $this->assertDatabaseHas('tagihan_spp', [
            'id_siswa' => $siswa->id_siswa,
            'bulan' => 6,
            'tahun' => 2091,
            'nominal' => 150000,
            'status' => 'belum_bayar',
        ]);
    }

    public function test_siswa_creation_is_rolled_back_when_active_year_has_no_tarif(): void
    {
        $user = User::factory()->create();
        [$kelas, $tahunAjaran, $tarif] = $this->kelasDanTahunAjaranAktif();
        $tarif->delete();

        $this->actingAs($user)
            ->from(route('master.siswa.create'))
            ->post(route('master.siswa.store'), [
                'nipd' => '2601002',
                'nama_siswa' => 'Siswa Tanpa Tarif',
                'jenis_kelamin' => 'P',
                'angkatan' => 2090,
                'status_siswa' => 'aktif',
                'id_kelas' => $kelas->id_kelas,
            ])
            ->assertRedirect(route('master.siswa.create'))
            ->assertSessionHasErrors('id_kelas');

        $this->assertDatabaseMissing('siswa', ['nipd' => '2601002']);
        $this->assertSame(0, $tahunAjaran->siswaKelas()->whereHas('siswa', fn ($query) => $query->where('nipd', '2601002'))->count());
    }

    public function test_petugas_can_edit_siswa_and_duplicate_nis_is_rejected(): void
    {
        $user = User::factory()->create();
        $siswa = Siswa::create([
            'nipd' => '2501001',
            'nama_siswa' => 'Siti Aminah',
            'jenis_kelamin' => 'P',
            'angkatan' => 2025,
            'status_siswa' => 'aktif',
        ]);
        Siswa::create([
            'nipd' => '2501002',
            'nama_siswa' => 'Nisa Putri',
            'jenis_kelamin' => 'P',
            'angkatan' => 2025,
            'status_siswa' => 'aktif',
        ]);

        $this->actingAs($user)->put(route('master.siswa.update', $siswa), [
            'nipd' => '2501001',
            'nama_siswa' => 'Siti Aminah Baru',
            'jenis_kelamin' => 'P',
            'angkatan' => 2025,
            'status_siswa' => 'pindah',
        ])->assertRedirect(route('master.siswa.index'));

        $this->assertDatabaseHas('siswa', [
            'id_siswa' => $siswa->id_siswa,
            'nama_siswa' => 'Siti Aminah Baru',
            'status_siswa' => 'pindah',
        ]);

        $this->from(route('master.siswa.edit', $siswa))
            ->put(route('master.siswa.update', $siswa), [
                'nipd' => '2501002',
                'nama_siswa' => 'Siti Aminah Baru',
                'jenis_kelamin' => 'P',
                'angkatan' => 2025,
                'status_siswa' => 'pindah',
            ])
            ->assertRedirect(route('master.siswa.edit', $siswa))
            ->assertSessionHasErrors('nipd');
    }

    public function test_petugas_can_edit_siswa_active_class_without_removing_previous_class_history(): void
    {
        $user = User::factory()->create();
        [$kelasAsal, $tahunAjaranAktif] = $this->kelasDanTahunAjaranAktif();
        $kelasTujuan = Kelas::create([
            'id_jurusan' => $kelasAsal->id_jurusan,
            'tingkat' => 1,
            'rombel' => 2,
            'nama_kelas' => 'X SIS 2',
        ]);
        $kelasBedaTingkat = Kelas::create([
            'id_jurusan' => $kelasAsal->id_jurusan,
            'tingkat' => 2,
            'rombel' => 1,
            'nama_kelas' => 'XI SIS 1',
        ]);
        $tahunAjaranSebelumnya = TahunAjaran::create([
            'tahun_ajaran' => '2089/2090',
            'tanggal_mulai' => '2089-07-01',
            'tanggal_selesai' => '2090-06-30',
        ]);
        $siswa = Siswa::create([
            'nipd' => '2501003',
            'nama_siswa' => 'Siswa Pindah Rombel',
            'jenis_kelamin' => 'L',
            'angkatan' => 2090,
            'status_siswa' => 'aktif',
        ]);
        SiswaKelas::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelasAsal->id_kelas,
            'id_tahun_ajaran' => $tahunAjaranSebelumnya->id_tahun_ajaran,
        ]);
        SiswaKelas::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelasAsal->id_kelas,
            'id_tahun_ajaran' => $tahunAjaranAktif->id_tahun_ajaran,
        ]);

        $this->actingAs($user)
            ->get(route('master.siswa.edit', $siswa))
            ->assertOk()
            ->assertSee('name="id_kelas"', false)
            ->assertSee('value="'.$kelasAsal->id_kelas.'"', false)
            ->assertDontSeeText($kelasBedaTingkat->nama_kelas);

        $this->put(route('master.siswa.update', $siswa), [
            'nipd' => $siswa->nipd,
            'nama_siswa' => $siswa->nama_siswa,
            'jenis_kelamin' => $siswa->jenis_kelamin,
            'angkatan' => $siswa->angkatan,
            'status_siswa' => $siswa->status_siswa,
            'id_kelas' => $kelasTujuan->id_kelas,
        ])->assertRedirect(route('master.siswa.index'));

        $this->assertDatabaseHas('siswa_kelas', [
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelasAsal->id_kelas,
            'id_tahun_ajaran' => $tahunAjaranSebelumnya->id_tahun_ajaran,
        ]);
        $this->assertDatabaseHas('siswa_kelas', [
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelasTujuan->id_kelas,
            'id_tahun_ajaran' => $tahunAjaranAktif->id_tahun_ajaran,
        ]);

        $this->from(route('master.siswa.edit', $siswa))
            ->put(route('master.siswa.update', $siswa), [
                'nipd' => $siswa->nipd,
                'nama_siswa' => $siswa->nama_siswa,
                'jenis_kelamin' => $siswa->jenis_kelamin,
                'angkatan' => $siswa->angkatan,
                'status_siswa' => $siswa->status_siswa,
                'id_kelas' => $kelasBedaTingkat->id_kelas,
            ])
            ->assertRedirect(route('master.siswa.edit', $siswa))
            ->assertSessionHasErrors('id_kelas');

        $this->assertDatabaseHas('siswa_kelas', [
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelasTujuan->id_kelas,
            'id_tahun_ajaran' => $tahunAjaranAktif->id_tahun_ajaran,
        ]);
    }

    public function test_siswa_can_be_deleted_permanently_when_it_has_no_payment_or_paid_bill(): void
    {
        $user = User::factory()->create();
        [$kelas, $tahunAjaran, $tarif] = $this->kelasDanTahunAjaranAktif();
        $siswa = Siswa::create([
            'nipd' => '2501003',
            'nama_siswa' => 'Siswa Salah Input',
            'jenis_kelamin' => 'L',
            'angkatan' => 2025,
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
            'tahun' => 2090,
            'nominal' => 150000,
            'status' => 'belum_bayar',
        ]);

        $this->actingAs($user)
            ->from(route('master.siswa.index'))
            ->delete(route('master.siswa.destroy', $siswa), ['konfirmasi_nipd' => 'salah'])
            ->assertRedirect(route('master.siswa.index'))
            ->assertSessionHas('error');

        $this->assertNull($siswa->fresh()->deleted_at);

        $this->delete(route('master.siswa.destroy', $siswa), ['konfirmasi_nipd' => $siswa->nipd])
            ->assertRedirect(route('master.siswa.index'));

        $this->assertSame(0, Siswa::withTrashed()->where('id_siswa', $siswa->id_siswa)->count());
        $this->assertDatabaseMissing('tagihan_spp', ['id_tagihan' => $tagihan->id_tagihan]);
        $this->assertDatabaseMissing('siswa_kelas', ['id_siswa_kelas' => $siswaKelas->id_siswa_kelas]);
    }

    public function test_siswa_with_payment_can_only_be_nonaktifkan_and_its_histori_remains_available(): void
    {
        $user = User::factory()->create();
        [$kelas, $tahunAjaran, $tarif] = $this->kelasDanTahunAjaranAktif();
        $siswa = Siswa::create([
            'nipd' => '2501004',
            'nama_siswa' => 'Siswa Berhistori',
            'jenis_kelamin' => 'L',
            'angkatan' => 2025,
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
            'tahun' => 2090,
            'nominal' => 150000,
            'status' => 'belum_bayar',
        ]);
        $pembayaran = app(PembayaranService::class)->bayar($user, $siswa, [$tagihan->id_tagihan]);

        $this->actingAs($user)
            ->from(route('master.siswa.index'))
            ->delete(route('master.siswa.destroy', $siswa), ['konfirmasi_nipd' => $siswa->nipd])
            ->assertRedirect(route('master.siswa.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('siswa', ['id_siswa' => $siswa->id_siswa, 'deleted_at' => null]);
        $this->assertDatabaseHas('tagihan_spp', ['id_tagihan' => $tagihan->id_tagihan, 'status' => 'lunas']);

        $this->from(route('master.siswa.index'))
            ->patch(route('master.siswa.nonaktifkan', $siswa), ['konfirmasi_nipd' => 'salah'])
            ->assertRedirect(route('master.siswa.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('siswa', ['id_siswa' => $siswa->id_siswa, 'deleted_at' => null]);

        $this->patch(route('master.siswa.nonaktifkan', $siswa), ['konfirmasi_nipd' => $siswa->nipd])
            ->assertRedirect(route('master.siswa.index'));

        $this->assertSoftDeleted('siswa', ['id_siswa' => $siswa->id_siswa]);
        $this->assertTrue($siswaKelas->fresh()->siswa->is($siswa));
        $this->get(route('master.siswa.nonaktif'))
            ->assertOk()
            ->assertSeeText('Siswa Berhistori')
            ->assertSee('Status SPP', false)
            ->assertSee('Riwayat Pembayaran', false)
            ->assertDontSeeText('Pulihkan');
        $this->get(route('status-spp.show', $siswa))
            ->assertOk()
            ->assertSeeText(['Siswa Berhistori', 'Juli 2090', 'Lunas']);
        $this->get(route('riwayat-pembayaran.index', ['cari' => $siswa->nipd]))
            ->assertOk()
            ->assertSeeText($pembayaran->no_kwitansi);
    }

    public function test_siswa_forms_render_confirmation_controls(): void
    {
        $user = User::factory()->create();
        $siswa = Siswa::create([
            'nipd' => '2501004',
            'nama_siswa' => 'Siswa Konfirmasi',
            'jenis_kelamin' => 'P',
            'angkatan' => 2025,
            'status_siswa' => 'aktif',
        ]);

        $this->actingAs($user)
            ->get(route('master.siswa.create'))
            ->assertOk()
            ->assertSee('data-confirm-title="Tambahkan siswa?"', false);

        $this->get(route('master.siswa.edit', $siswa))
            ->assertOk()
            ->assertSee('data-confirm-title="Simpan perubahan siswa?"', false);

        $this->get(route('master.siswa.index', ['cari' => '2501004']))
            ->assertOk()
            ->assertSee('data-confirm-input-name="konfirmasi_nipd"', false)
            ->assertSee('data-confirm-input-value="2501004"', false)
            ->assertSee('id="confirmation-form"', false)
            ->assertSee('id="confirmation-cancel"', false)
            ->assertSee('id="confirmation-input-error"', false);
    }

    public function test_petugas_can_search_siswa_by_nis_or_name(): void
    {
        $user = User::factory()->create();
        Siswa::create([
            'nipd' => '2301001',
            'nama_siswa' => 'Dewi Lestari',
            'jenis_kelamin' => 'P',
            'angkatan' => 2023,
            'status_siswa' => 'aktif',
        ]);
        Siswa::create([
            'nipd' => '2301002',
            'nama_siswa' => 'Rudi Hartono',
            'jenis_kelamin' => 'L',
            'angkatan' => 2023,
            'status_siswa' => 'aktif',
        ]);

        $this->actingAs($user)->get(route('master.siswa.index', ['cari' => 'Dewi']))
            ->assertOk()
            ->assertSeeText('Dewi Lestari')
            ->assertDontSeeText('Rudi Hartono');

        $this->get(route('master.siswa.index', ['cari' => '2301002']))
            ->assertOk()
            ->assertSeeText('Rudi Hartono')
            ->assertDontSeeText('Dewi Lestari');
    }

    public function test_graduated_students_are_hidden_by_default_and_shown_by_lulus_filter(): void
    {
        $user = User::factory()->create();
        Siswa::create([
            'nipd' => 'STATUS-001',
            'nama_siswa' => 'Siswa Aktif',
            'jenis_kelamin' => 'L',
            'angkatan' => 2026,
            'status_siswa' => 'aktif',
        ]);
        Siswa::create([
            'nipd' => 'STATUS-002',
            'nama_siswa' => 'Siswa Lulus',
            'jenis_kelamin' => 'P',
            'angkatan' => 2024,
            'status_siswa' => 'lulus',
        ]);

        $this->actingAs($user)
            ->get(route('master.siswa.index'))
            ->assertOk()
            ->assertSeeText('Siswa Aktif')
            ->assertDontSeeText('Siswa Lulus');

        $this->get(route('master.siswa.index', ['status_siswa' => 'lulus']))
            ->assertOk()
            ->assertSeeText('Siswa Lulus')
            ->assertDontSeeText('Siswa Aktif');
    }

    public function test_petugas_can_filter_siswa_by_status_and_active_class(): void
    {
        $user = User::factory()->create();
        [$kelas, $tahunAjaran] = $this->kelasDanTahunAjaranAktif();
        $jurusanLain = Jurusan::create([
            'kode_jurusan' => 'FLT',
            'nama_jurusan' => 'Filter Lain',
        ]);
        $kelasLain = Kelas::create([
            'id_jurusan' => $jurusanLain->id_jurusan,
            'tingkat' => 2,
            'rombel' => 5,
            'nama_kelas' => 'XI FLT 5',
        ]);
        $siswaSesuai = Siswa::create([
            'nipd' => 'FILTER-001',
            'nama_siswa' => 'Siswa Sesuai Filter',
            'jenis_kelamin' => 'L',
            'angkatan' => 2090,
            'status_siswa' => 'aktif',
        ]);
        $siswaLain = Siswa::create([
            'nipd' => 'FILTER-002',
            'nama_siswa' => 'Siswa Tidak Sesuai',
            'jenis_kelamin' => 'P',
            'angkatan' => 2089,
            'status_siswa' => 'pindah',
        ]);
        SiswaKelas::create([
            'id_siswa' => $siswaSesuai->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
        ]);
        SiswaKelas::create([
            'id_siswa' => $siswaLain->id_siswa,
            'id_kelas' => $kelasLain->id_kelas,
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
        ]);

        $this->actingAs($user)->get(route('master.siswa.index', [
            'status_siswa' => 'aktif',
            'id_jurusan' => $kelas->id_jurusan,
            'tingkat' => 1,
            'rombel' => 1,
        ]))
            ->assertOk()
            ->assertSeeText('Siswa Sesuai Filter')
            ->assertDontSeeText('Siswa Tidak Sesuai')
            ->assertSeeHtml('name="status_siswa"')
            ->assertSeeHtml('name="id_jurusan"');

        $this->get(route('master.siswa.index', [
            'status_siswa' => 'pindah',
            'id_jurusan' => $kelasLain->id_jurusan,
            'tingkat' => 2,
            'rombel' => 5,
        ]))
            ->assertOk()
            ->assertSeeText('Siswa Tidak Sesuai')
            ->assertDontSeeText('Siswa Sesuai Filter');
    }

    public function test_student_list_paginates_and_keeps_search_filter(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 21) as $nomor) {
            Siswa::create([
                'nipd' => 'PAGE-'.str_pad((string) $nomor, 3, '0', STR_PAD_LEFT),
                'nama_siswa' => 'Siswa Pagination '.str_pad((string) $nomor, 2, '0', STR_PAD_LEFT),
                'jenis_kelamin' => 'L',
                'angkatan' => 2026,
                'status_siswa' => 'aktif',
            ]);
        }

        $this->actingAs($user)
            ->get(route('master.siswa.index', ['cari' => 'PAGE-']))
            ->assertOk()
            ->assertSeeText('Menampilkan 1-20 dari 21 siswa')
            ->assertSeeText('Siswa Pagination 01')
            ->assertDontSeeText('Siswa Pagination 21')
            ->assertDontSeeText('Detail');

        $this->get(route('master.siswa.index', ['cari' => 'PAGE-', 'page' => 2]))
            ->assertOk()
            ->assertSeeText('Menampilkan 21-21 dari 21 siswa')
            ->assertSeeText('Siswa Pagination 21')
            ->assertDontSeeText('Siswa Pagination 01');
    }

    public function test_angkatan_choices_follow_active_year_and_preserve_older_student_value(): void
    {
        $user = User::factory()->create();
        [$kelas] = $this->kelasDanTahunAjaranAktif();

        $this->actingAs($user)->get(route('master.siswa.create'))
            ->assertOk()
            ->assertSee('data-tingkat="1"', false)
            ->assertSee('const tahunMulai = 2090', false)
            ->assertSee('id="angkatan" name="angkatan" type="hidden" value=""', false);

        $siswa = Siswa::create([
            'nipd' => '2001002',
            'nama_siswa' => 'Siswa Data Lama',
            'jenis_kelamin' => 'P',
            'angkatan' => 2020,
            'status_siswa' => 'aktif',
        ]);

        $this->get(route('master.siswa.edit', $siswa))
            ->assertOk()
            ->assertSee('id="angkatan" name="angkatan" type="hidden" value="2020"', false);

        $this->put(route('master.siswa.update', $siswa), [
            'nipd' => '2001002',
            'nama_siswa' => 'Siswa Data Lama',
            'jenis_kelamin' => 'P',
            'angkatan' => 2020,
            'status_siswa' => 'aktif',
        ])->assertRedirect(route('master.siswa.index'));

        $this->assertFalse(Route::has('master.siswa.show'));

        $this->from(route('master.siswa.create'))
            ->post(route('master.siswa.store'), [
                'nipd' => '2001001',
                'nama_siswa' => 'Angkatan Di Luar Rentang',
                'jenis_kelamin' => 'L',
                'angkatan' => 2025,
                'status_siswa' => 'aktif',
                'id_kelas' => $kelas->id_kelas,
            ])
            ->assertRedirect(route('master.siswa.create'))
            ->assertSessionHasErrors('angkatan');
    }

    public function test_import_skips_duplicate_nis_in_the_same_file_and_on_reupload(): void
    {
        $user = User::factory()->create();
        [$kelas, $tahunAjaran] = $this->kelasDanTahunAjaranAktif();

        foreach ([2, 3] as $tingkat) {
            TarifSpp::create([
                'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
                'tingkat' => $tingkat,
                'nominal' => 120000,
            ]);
        }

        $rows = [
            [1, $kelas->nama_kelas, 'IMPORT-2601001', '0000000001', 'Siswa Import Pertama', 'L'],
            [2, $kelas->nama_kelas, 'IMPORT-2601001', '0000000002', 'Nama Duplikat Tidak Dipakai', 'P'],
        ];

        $this->actingAs($user)
            ->get(route('master.siswa.import.form'))
            ->assertOk()
            ->assertSeeText(['Baris header dideteksi otomatis.', 'Pilih File', 'Belum ada file dipilih'])
            ->assertSee('class="file-picker"', false);

        $this->post(route('master.siswa.import'), [
            'file' => $this->fileImportSiswa($rows),
        ])->assertRedirect(route('master.siswa.import.form'))
            ->assertSessionHas('import_result', function (array $result): bool {
                return $result['berhasil'] === 1
                    && $result['duplikat'] === 1
                    && $result['errors'] === [];
            });

        $siswa = Siswa::query()->where('nipd', 'IMPORT-2601001')->firstOrFail();

        $this->assertDatabaseHas('siswa', [
            'id_siswa' => $siswa->id_siswa,
            'nama_siswa' => 'Siswa Import Pertama',
            'jenis_kelamin' => 'L',
            'angkatan' => 2090,
        ]);
        $this->assertDatabaseMissing('siswa', ['nama_siswa' => 'Nama Duplikat Tidak Dipakai']);
        $this->assertSame(1, Siswa::withTrashed()->where('nipd', 'IMPORT-2601001')->count());
        $this->assertSame(1, SiswaKelas::query()->where('id_siswa', $siswa->id_siswa)->count());
        $this->assertSame(12, $siswa->tagihanSpp()->count());

        $this->post(route('master.siswa.import'), [
            'file' => $this->fileImportSiswa($rows),
        ])->assertRedirect(route('master.siswa.import.form'))
            ->assertSessionHas('import_result', function (array $result): bool {
                return $result['berhasil'] === 0
                    && $result['duplikat'] === 2
                    && $result['errors'] === [];
            });

        $this->assertSame(1, Siswa::withTrashed()->where('nipd', 'IMPORT-2601001')->count());
        $this->assertSame(1, SiswaKelas::query()->where('id_siswa', $siswa->id_siswa)->count());
        $this->assertSame(12, $siswa->tagihanSpp()->count());
    }

    public function test_import_detects_headers_in_any_row_and_column_order(): void
    {
        $user = User::factory()->create();
        [$kelas, $tahunAjaran] = $this->kelasDanTahunAjaranAktif();

        foreach ([2, 3] as $tingkat) {
            TarifSpp::create([
                'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
                'tingkat' => $tingkat,
                'nominal' => 120000,
            ]);
        }

        $this->actingAs($user)
            ->post(route('master.siswa.import'), [
                'file' => $this->fileImportSiswa(
                    [['Siswa Header Fleksibel', 'P', 'IMPORT-2601002', $kelas->nama_kelas]],
                    'B8',
                    ['Nama Siswa', 'Jenis Kelamin', 'NIPD', 'Kelas'],
                ),
            ])->assertRedirect(route('master.siswa.import.form'))
            ->assertSessionHas('import_result', function (array $result): bool {
                return $result['berhasil'] === 1
                    && $result['duplikat'] === 0
                    && $result['errors'] === [];
            });

        $this->assertDatabaseHas('siswa', [
            'nipd' => 'IMPORT-2601002',
            'nama_siswa' => 'Siswa Header Fleksibel',
            'jenis_kelamin' => 'P',
        ]);
    }

    /**
     * @return array{0: Kelas, 1: TahunAjaran, 2: TarifSpp}
     */
    private function kelasDanTahunAjaranAktif(): array
    {
        TahunAjaran::query()->where('aktif', true)->update(['aktif' => false]);

        $jurusan = Jurusan::create([
            'kode_jurusan' => 'SIS',
            'nama_jurusan' => 'Sistem Informasi',
        ]);
        $kelas = Kelas::create([
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 1,
            'rombel' => 1,
            'nama_kelas' => 'X SIS 1',
        ]);
        $tahunAjaran = TahunAjaran::create([
            'tahun_ajaran' => '2090/2091',
            'tanggal_mulai' => '2090-07-01',
            'tanggal_selesai' => '2091-06-30',
            'aktif' => true,
        ]);
        $tarif = TarifSpp::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tingkat' => $kelas->tingkat,
            'nominal' => 150000,
        ]);

        return [$kelas, $tahunAjaran, $tarif];
    }

    /**
     * @param  list<list<int|string>>  $rows
     */
    private function fileImportSiswa(array $rows, string $headerCell = 'C4', ?array $headers = null): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([$headers ?? ['No', 'Rombel', 'NIPD', 'NISN', 'Nama', 'JK']], null, $headerCell);
        [$kolom, $baris] = Coordinate::coordinateFromString($headerCell);
        $sheet->fromArray($rows, null, $kolom.((int) $baris + 1));

        $path = tempnam(sys_get_temp_dir(), 'siswa-import-');
        (new Xlsx($spreadsheet))->save($path);
        $contents = file_get_contents($path);
        unlink($path);

        return UploadedFile::fake()->createWithContent('siswa.xlsx', $contents);
    }
}
