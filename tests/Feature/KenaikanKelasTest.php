<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\TarifSpp;
use App\Models\User;
use App\Services\TagihanSppService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use LogicException;
use Tests\TestCase;

class KenaikanKelasTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_access_kenaikan_kelas(): void
    {
        $this->get(route('kenaikan-kelas.preview'))
            ->assertRedirect(route('login'));
    }

    public function test_preview_shows_candidates_and_group_summaries(): void
    {
        $data = $this->dataKenaikanKelas();

        $this->actingAs(User::factory()->create())
            ->get(route('kenaikan-kelas.preview'))
            ->assertOk()
            ->assertSeeText([
                $data['tahunAjaranAsal']->tahun_ajaran,
                $data['tahunAjaranTujuan']->tahun_ajaran,
                $data['siswaX']->nama_siswa,
                $data['siswaXI']->nama_siswa,
                $data['siswaXII']->nama_siswa,
                'Naik ke XI',
                'Naik ke XII',
                'Lulus',
            ])
            ->assertSee('name="cari"', false)
            ->assertSee('data-candidate-checkbox', false)
            ->assertSee('data-confirm-title="Proses kenaikan kelas?"', false);
    }

    public function test_preview_can_search_and_paginate_candidates(): void
    {
        $data = $this->dataKenaikanKelas();

        foreach (range(1, 21) as $nomor) {
            $this->siswaDenganKelas(
                'KKN-PAGE-'.str_pad((string) $nomor, 2, '0', STR_PAD_LEFT),
                'A Kandidat Pagination '.str_pad((string) $nomor, 2, '0', STR_PAD_LEFT),
                $data['kelasX'],
                $data['tahunAjaranAsal'],
            );
        }

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('kenaikan-kelas.preview'))
            ->assertOk()
            ->assertSeeText('Menampilkan 1-20 dari 24 kandidat')
            ->assertSeeText('A Kandidat Pagination 01')
            ->assertDontSeeText('A Kandidat Pagination 21')
            ->assertSee('id="candidate-filter-reset"', false)
            ->assertSee('type="button">Reset</button>', false);

        $this->get(route('kenaikan-kelas.preview', ['page' => 2]))
            ->assertOk()
            ->assertSeeText('Menampilkan 21-24 dari 24 kandidat')
            ->assertSeeText('A Kandidat Pagination 21')
            ->assertDontSeeText('A Kandidat Pagination 01');

        $this->get(route('kenaikan-kelas.preview', [
            'page' => 2,
            'tetap_di_kelas_asal' => [$data['siswaX']->id_siswa, $data['siswaX']->id_siswa],
        ]))
            ->assertOk()
            ->assertSeeText('Menampilkan 21-24 dari 24 kandidat');

        $this->get(route('kenaikan-kelas.preview', [
            'tetap_di_kelas_asal' => [$data['siswaX']->id_siswa],
        ]))
            ->assertOk()
            ->assertSee('id="selected-candidate-names"', false)
            ->assertViewHas('kandidatTetapKelas', fn ($kandidat) => $kandidat->contains(
                fn (array $item): bool => $item['id_siswa'] === $data['siswaX']->id_siswa
                    && $item['nipd'] === $data['siswaX']->nipd
                    && $item['nama_siswa'] === $data['siswaX']->nama_siswa
                    && $item['kelas_asal'] === $data['kelasX']->nama_kelas,
            ));

        $this->get(route('kenaikan-kelas.preview', ['cari' => $data['siswaXI']->nipd]))
            ->assertOk()
            ->assertSeeText($data['siswaXI']->nama_siswa);
    }

    public function test_process_creates_class_history_and_bills_and_graduates_tingkat_tiga(): void
    {
        $data = $this->dataKenaikanKelas();

        $this->actingAs(User::factory()->create())
            ->post(route('kenaikan-kelas.proses'))
            ->assertRedirect(route('kenaikan-kelas.preview'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('siswa_kelas', [
            'id_siswa' => $data['siswaX']->id_siswa,
            'id_kelas' => $data['kelasXI']->id_kelas,
            'id_tahun_ajaran' => $data['tahunAjaranTujuan']->id_tahun_ajaran,
        ]);
        $this->assertDatabaseHas('siswa_kelas', [
            'id_siswa' => $data['siswaXI']->id_siswa,
            'id_kelas' => $data['kelasXII']->id_kelas,
            'id_tahun_ajaran' => $data['tahunAjaranTujuan']->id_tahun_ajaran,
        ]);
        $this->assertSame(12, $data['siswaX']->tagihanSpp()->count());
        $this->assertSame(12, $data['siswaXI']->tagihanSpp()->count());
        $this->assertDatabaseMissing('siswa_kelas', [
            'id_siswa' => $data['siswaXII']->id_siswa,
            'id_tahun_ajaran' => $data['tahunAjaranTujuan']->id_tahun_ajaran,
        ]);
        $this->assertSame('lulus', $data['siswaXII']->fresh()->status_siswa);
    }

    public function test_process_keeps_checked_students_in_their_source_class(): void
    {
        $data = $this->dataKenaikanKelas();

        $this->actingAs(User::factory()->create())
            ->post(route('kenaikan-kelas.proses'), [
                'tetap_di_kelas_asal' => [
                    $data['siswaX']->id_siswa,
                    $data['siswaXII']->id_siswa,
                ],
            ])
            ->assertRedirect(route('kenaikan-kelas.preview'));

        $this->assertDatabaseHas('siswa_kelas', [
            'id_siswa' => $data['siswaX']->id_siswa,
            'id_kelas' => $data['kelasX']->id_kelas,
            'id_tahun_ajaran' => $data['tahunAjaranTujuan']->id_tahun_ajaran,
        ]);
        $this->assertSame(12, $data['siswaX']->tagihanSpp()->count());
        $this->assertDatabaseHas('siswa_kelas', [
            'id_siswa' => $data['siswaXII']->id_siswa,
            'id_kelas' => $data['kelasXII']->id_kelas,
            'id_tahun_ajaran' => $data['tahunAjaranTujuan']->id_tahun_ajaran,
        ]);
        $this->assertSame(12, $data['siswaXII']->tagihanSpp()->count());
        $this->assertSame('aktif', $data['siswaXII']->fresh()->status_siswa);
    }

    public function test_process_rolls_back_when_bill_generation_fails(): void
    {
        $data = $this->dataKenaikanKelas();
        $this->mock(TagihanSppService::class, function ($mock): void {
            $mock->shouldReceive('generateUntukTahunAjaran')
                ->once()
                ->andThrow(new LogicException('Simulasi pembuatan tagihan gagal.'));
        });

        $this->actingAs(User::factory()->create())
            ->from(route('kenaikan-kelas.preview'))
            ->post(route('kenaikan-kelas.proses'))
            ->assertRedirect(route('kenaikan-kelas.preview'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('siswa_kelas', [
            'id_siswa' => $data['siswaX']->id_siswa,
            'id_tahun_ajaran' => $data['tahunAjaranTujuan']->id_tahun_ajaran,
        ]);
        $this->assertSame('aktif', $data['siswaXII']->fresh()->status_siswa);
    }

    /**
     * @return array<string, Kelas|Siswa|TahunAjaran>
     */
    private function dataKenaikanKelas(): array
    {
        TahunAjaran::aktif()->update(['aktif' => false]);

        $jurusan = Jurusan::create([
            'kode_jurusan' => 'KKN',
            'nama_jurusan' => 'Kenaikan Kelas',
        ]);
        $kelasX = Kelas::create([
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 1,
            'rombel' => 1,
            'nama_kelas' => 'X KKN 1',
        ]);
        $kelasXI = Kelas::create([
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 2,
            'rombel' => 1,
            'nama_kelas' => 'XI KKN 1',
        ]);
        $kelasXII = Kelas::create([
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 3,
            'rombel' => 1,
            'nama_kelas' => 'XII KKN 1',
        ]);
        $tahunAjaranAsal = TahunAjaran::create([
            'tahun_ajaran' => '2090/2091',
            'tanggal_mulai' => '2090-07-01',
            'tanggal_selesai' => '2091-06-30',
        ]);
        $tahunAjaranTujuan = TahunAjaran::create([
            'tahun_ajaran' => '2091/2092',
            'tanggal_mulai' => '2091-07-01',
            'tanggal_selesai' => '2092-06-30',
            'aktif' => true,
        ]);
        TarifSpp::create([
            'id_tahun_ajaran' => $tahunAjaranTujuan->id_tahun_ajaran,
            'tingkat' => 1,
            'nominal' => 150000,
        ]);
        TarifSpp::create([
            'id_tahun_ajaran' => $tahunAjaranTujuan->id_tahun_ajaran,
            'tingkat' => 2,
            'nominal' => 120000,
        ]);
        TarifSpp::create([
            'id_tahun_ajaran' => $tahunAjaranTujuan->id_tahun_ajaran,
            'tingkat' => 3,
            'nominal' => 120000,
        ]);

        $siswaX = $this->siswaDenganKelas('KKN-X', 'Siswa Tingkat X', $kelasX, $tahunAjaranAsal);
        $siswaXI = $this->siswaDenganKelas('KKN-XI', 'Siswa Tingkat XI', $kelasXI, $tahunAjaranAsal);
        $siswaXII = $this->siswaDenganKelas('KKN-XII', 'Siswa Tingkat XII', $kelasXII, $tahunAjaranAsal);

        return compact(
            'kelasX',
            'kelasXI',
            'kelasXII',
            'siswaX',
            'siswaXI',
            'siswaXII',
            'tahunAjaranAsal',
            'tahunAjaranTujuan',
        );
    }

    private function siswaDenganKelas(string $nipd, string $nama, Kelas $kelas, TahunAjaran $tahunAjaran): Siswa
    {
        $siswa = Siswa::create([
            'nipd' => $nipd,
            'nama_siswa' => $nama,
            'jenis_kelamin' => 'L',
            'angkatan' => 2090,
            'status_siswa' => 'aktif',
        ]);
        SiswaKelas::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
        ]);

        return $siswa;
    }
}
