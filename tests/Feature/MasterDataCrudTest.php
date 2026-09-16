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
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MasterDataCrudTest extends TestCase
{
    use DatabaseTransactions;

    public function test_petugas_can_create_jurusan_with_default_classes_and_update_it(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('master.jurusan.store'), [
            'kode_jurusan' => 'CRJ',
            'nama_jurusan' => 'CRUD Jurusan',
        ])->assertRedirect(route('master.jurusan.index'));

        $jurusan = Jurusan::query()->where('kode_jurusan', 'CRJ')->firstOrFail();

        $this->assertSame(12, $jurusan->kelas()->count());

        foreach ([1 => 'X', 2 => 'XI', 3 => 'XII'] as $tingkat => $namaTingkat) {
            foreach (range(1, 4) as $rombel) {
                $this->assertDatabaseHas('kelas', [
                    'id_jurusan' => $jurusan->id_jurusan,
                    'tingkat' => $tingkat,
                    'rombel' => $rombel,
                    'nama_kelas' => "$namaTingkat CRJ $rombel",
                ]);
            }
        }

        $this->put(route('master.jurusan.update', $jurusan), [
            'kode_jurusan' => 'CRU',
            'nama_jurusan' => 'Jurusan Diperbarui',
        ])->assertRedirect(route('master.jurusan.index'));

        $this->assertDatabaseHas('jurusan', [
            'id_jurusan' => $jurusan->id_jurusan,
            'kode_jurusan' => 'CRU',
            'nama_jurusan' => 'Jurusan Diperbarui',
        ]);
    }

    public function test_jurusan_deletion_is_rejected_when_referenced_by_kelas(): void
    {
        $user = User::factory()->create();
        $jurusan = Jurusan::create(['kode_jurusan' => 'JRF', 'nama_jurusan' => 'Jurusan Referensi']);
        Kelas::create([
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 1,
            'rombel' => 1,
            'nama_kelas' => 'X JRF 1',
        ]);

        $this->actingAs($user)->delete(route('master.jurusan.destroy', $jurusan))
            ->assertRedirect(route('master.jurusan.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('jurusan', ['id_jurusan' => $jurusan->id_jurusan]);
    }

    public function test_petugas_can_create_kelas_with_flexible_rombel_and_duplicate_combination_is_rejected(): void
    {
        $user = User::factory()->create();
        $jurusan = Jurusan::create(['kode_jurusan' => 'KLS', 'nama_jurusan' => 'Kelas Test']);

        $this->actingAs($user)->post(route('master.kelas.store'), [
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 1,
            'rombel' => 5,
            'nama_kelas' => 'Nama manual tidak dipakai',
        ])->assertRedirect(route('master.kelas.index'));

        $kelas = Kelas::query()->where('nama_kelas', 'X KLS 5')->firstOrFail();
        $this->assertDatabaseHas('kelas', ['id_kelas' => $kelas->id_kelas, 'nama_kelas' => 'X KLS 5']);
        $this->get(route('master.kelas.index', ['rombel' => 5]))
            ->assertOk()
            ->assertSeeText('X KLS 5');

        $this->assertFalse(Route::has('master.kelas.edit'));
        $this->assertFalse(Route::has('master.kelas.update'));

        $response = $this->from(route('master.kelas.create'))->post(route('master.kelas.store'), [
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 1,
            'rombel' => 5,
            'nama_kelas' => 'Duplikat Kelas',
        ]);

        $response->assertRedirect(route('master.kelas.create'));
        $response->assertSessionHasErrors('rombel');

        $this->from(route('master.kelas.create'))->post(route('master.kelas.store'), [
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 1,
            'rombel' => 256,
        ])->assertRedirect(route('master.kelas.create'))
            ->assertSessionHasErrors('rombel');

        $this->delete(route('master.kelas.destroy', $kelas))
            ->assertRedirect(route('master.kelas.index'));

        $this->assertDatabaseMissing('kelas', ['id_kelas' => $kelas->id_kelas]);
    }

    public function test_kelas_deletion_is_rejected_when_referenced_by_siswa_kelas(): void
    {
        $user = User::factory()->create();
        $jurusan = Jurusan::create(['kode_jurusan' => 'KRF', 'nama_jurusan' => 'Kelas Referensi']);
        $kelas = Kelas::create([
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 1,
            'rombel' => 1,
            'nama_kelas' => 'X KRF 1',
        ]);
        $tahunAjaran = $this->createTahunAjaran('2091/2092');
        $siswa = Siswa::create([
            'nipd' => '2091001',
            'nama_siswa' => 'Siswa Referensi',
            'jenis_kelamin' => 'L',
            'angkatan' => 2091,
            'status_siswa' => 'aktif',
        ]);
        SiswaKelas::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
        ]);

        $this->actingAs($user)->delete(route('master.kelas.destroy', $kelas))
            ->assertRedirect(route('master.kelas.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('kelas', ['id_kelas' => $kelas->id_kelas]);
    }

    public function test_tahun_ajaran_deletion_is_rejected_when_it_has_tarif(): void
    {
        $user = User::factory()->create();
        $tahunAjaran = $this->createTahunAjaran('2092/2093');
        TarifSpp::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tingkat' => 1,
            'nominal' => 150000,
        ]);

        $this->actingAs($user)->delete(route('master.tahun-ajaran.destroy', $tahunAjaran))
            ->assertRedirect(route('master.tahun-ajaran.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('tahun_ajaran', ['id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran]);
    }

    public function test_petugas_can_create_all_tarif_and_update_but_cannot_update_referenced_tarif(): void
    {
        $user = User::factory()->create();
        $tahunAjaran = $this->createTahunAjaran('2093/2094');

        $this->actingAs($user)->get(route('master.tarif-spp.create'))
            ->assertOk()
            ->assertSee('name="tarif[1]"', false)
            ->assertSee('name="tarif[2]"', false)
            ->assertSee('name="tarif[3]"', false);

        $this->actingAs($user)->post(route('master.tarif-spp.store'), [
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tarif' => [
                1 => 150000,
                2 => 120000,
                3 => 120000,
            ],
        ])->assertRedirect(route('master.tarif-spp.index', ['id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran]));

        $this->assertSame(3, TarifSpp::query()
            ->where('id_tahun_ajaran', $tahunAjaran->id_tahun_ajaran)
            ->count());
        $tarif = TarifSpp::query()
            ->where('id_tahun_ajaran', $tahunAjaran->id_tahun_ajaran)
            ->where('tingkat', 1)
            ->firstOrFail();

        $response = $this->from(route('master.tarif-spp.create'))->post(route('master.tarif-spp.store'), [
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tarif' => [
                1 => 150000,
                2 => 120000,
                3 => 120000,
            ],
        ]);

        $response->assertRedirect(route('master.tarif-spp.create'));
        $response->assertSessionHasErrors('tarif.1');

        $this->put(route('master.tarif-spp.update', $tarif), [
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tingkat' => 1,
            'nominal' => 175000,
        ])->assertRedirect(route('master.tarif-spp.index', ['id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran]));

        $this->assertDatabaseHas('tarif_spp', ['id_tarif' => $tarif->id_tarif, 'nominal' => 175000]);

        $this->createTagihanReferencingTarif($tarif, $tahunAjaran);

        $this->put(route('master.tarif-spp.update', $tarif), [
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tingkat' => 1,
            'nominal' => 200000,
        ])->assertRedirect(route('master.tarif-spp.index', ['id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('tarif_spp', ['id_tarif' => $tarif->id_tarif, 'nominal' => 175000]);
    }

    private function createTahunAjaran(string $tahunAjaran): TahunAjaran
    {
        $tahunMulai = (int) substr($tahunAjaran, 0, 4);

        return TahunAjaran::create([
            'tahun_ajaran' => $tahunAjaran,
            'tanggal_mulai' => "$tahunMulai-07-01",
            'tanggal_selesai' => ($tahunMulai + 1).'-06-30',
        ]);
    }

    private function createTagihanReferencingTarif(TarifSpp $tarif, TahunAjaran $tahunAjaran): void
    {
        $jurusan = Jurusan::create(['kode_jurusan' => 'TRF', 'nama_jurusan' => 'Tarif Referensi']);
        $kelas = Kelas::create([
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 1,
            'rombel' => 1,
            'nama_kelas' => 'X TRF 1',
        ]);
        $siswa = Siswa::create([
            'nipd' => '2093001',
            'nama_siswa' => 'Siswa Tarif',
            'jenis_kelamin' => 'P',
            'angkatan' => 2093,
            'status_siswa' => 'aktif',
        ]);
        $siswaKelas = SiswaKelas::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
        ]);

        TagihanSpp::create([
            'id_siswa' => $siswa->id_siswa,
            'id_siswa_kelas' => $siswaKelas->id_siswa_kelas,
            'id_tarif' => $tarif->id_tarif,
            'bulan' => 7,
            'tahun' => 2093,
            'nominal' => 175000,
            'status' => 'belum_bayar',
        ]);
    }
}
