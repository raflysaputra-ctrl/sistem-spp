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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StatusSppTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_access_student_spp_status(): void
    {
        $siswa = Siswa::create([
            'nipd' => '4401001',
            'nama_siswa' => 'Siswa Status',
            'jenis_kelamin' => 'L',
            'angkatan' => 2044,
            'status_siswa' => 'aktif',
        ]);

        $this->get(route('status-spp.index'))->assertRedirect(route('login'));
        $this->get(route('status-spp.show', $siswa))->assertRedirect(route('login'));
    }

    public function test_petugas_can_view_student_identity_class_and_spp_bill_statuses(): void
    {
        $user = User::factory()->create();
        [$siswa, $siswaKelas, $tarif] = $this->siswaDenganPenempatanKelas();

        TagihanSpp::create([
            'id_siswa' => $siswa->id_siswa,
            'id_siswa_kelas' => $siswaKelas->id_siswa_kelas,
            'id_tarif' => $tarif->id_tarif,
            'bulan' => 7,
            'tahun' => 2044,
            'nominal' => 150000,
            'status' => 'belum_bayar',
        ]);
        TagihanSpp::create([
            'id_siswa' => $siswa->id_siswa,
            'id_siswa_kelas' => $siswaKelas->id_siswa_kelas,
            'id_tarif' => $tarif->id_tarif,
            'bulan' => 8,
            'tahun' => 2044,
            'nominal' => 150000,
            'status' => 'lunas',
            'tanggal_lunas' => '2044-08-15 10:30:00',
        ]);
        TagihanSpp::create([
            'id_siswa' => $siswa->id_siswa,
            'id_siswa_kelas' => $siswaKelas->id_siswa_kelas,
            'id_tarif' => $tarif->id_tarif,
            'bulan' => 1,
            'tahun' => 2045,
            'nominal' => 150000,
            'status' => 'belum_bayar',
        ]);

        $this->actingAs($user)->get(route('status-spp.index', ['cari' => '4401002']))
            ->assertOk()
            ->assertSeeText([
                'Ringkasan Status SPP',
                'Siswa Status',
                'X STS 1',
                '2',
                '1',
                'Lihat Status',
            ]);

        $this->actingAs($user)->get(route('status-spp.show', $siswa))
            ->assertOk()
            ->assertSeeText([
                'Status SPP Siswa',
                'Siswa Status',
                '4401002',
                'X STS 1',
                '2044/2045',
                'Agustus 2044',
                'Juli 2044',
                'Januari 2045',
                'Rp 150.000',
                'Lunas',
                'Belum Bayar',
                '15/08/2044 10:30',
            ])
            ->assertSeeInOrder(['Juli 2044', 'Agustus 2044', 'Januari 2045']);
    }

    public function test_petugas_sees_empty_state_when_student_has_no_bills(): void
    {
        $user = User::factory()->create();
        [$siswa] = $this->siswaDenganPenempatanKelas();

        $this->actingAs($user)->get(route('status-spp.show', $siswa))
            ->assertOk()
            ->assertSeeText('Belum ada tagihan SPP untuk siswa ini.');
    }

    public function test_unpaid_past_bill_is_marked_as_tunggakan_in_wib(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 15, 10, 0, 0, 'Asia/Jakarta'));

        try {
            $user = User::factory()->create();
            [$siswa, $siswaKelas, $tarif] = $this->siswaDenganPenempatanKelas();

            $tagihanAgustus = TagihanSpp::create([
                'id_siswa' => $siswa->id_siswa,
                'id_siswa_kelas' => $siswaKelas->id_siswa_kelas,
                'id_tarif' => $tarif->id_tarif,
                'bulan' => 8,
                'tahun' => 2026,
                'nominal' => 150000,
                'status' => 'belum_bayar',
            ]);
            $tagihanSeptember = TagihanSpp::create([
                'id_siswa' => $siswa->id_siswa,
                'id_siswa_kelas' => $siswaKelas->id_siswa_kelas,
                'id_tarif' => $tarif->id_tarif,
                'bulan' => 9,
                'tahun' => 2026,
                'nominal' => 150000,
                'status' => 'belum_bayar',
            ]);
            $tagihanOktober = TagihanSpp::create([
                'id_siswa' => $siswa->id_siswa,
                'id_siswa_kelas' => $siswaKelas->id_siswa_kelas,
                'id_tarif' => $tarif->id_tarif,
                'bulan' => 10,
                'tahun' => 2026,
                'nominal' => 150000,
                'status' => 'belum_bayar',
            ]);

            $this->assertTrue($tagihanAgustus->adalahTunggakan());
            $this->assertFalse($tagihanSeptember->adalahTunggakan());
            $this->assertFalse($tagihanOktober->adalahTunggakan());

            $this->actingAs($user)->get(route('status-spp.show', $siswa))
                ->assertOk()
                ->assertSeeText('Tunggakan')
                ->assertSeeText(['Agustus 2026', 'September 2026', 'Oktober 2026']);

            $this->actingAs($user)->get(route('pembayaran.show', $siswa))
                ->assertOk()
                ->assertSeeText('Tunggakan')
                ->assertSee('value="'.$tagihanAgustus->id_tagihan.'"', false)
                ->assertDontSee('value="'.$tagihanAgustus->id_tagihan.'" disabled', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * @return array{0: Siswa, 1: SiswaKelas, 2: TarifSpp}
     */
    private function siswaDenganPenempatanKelas(): array
    {
        $jurusan = Jurusan::create([
            'kode_jurusan' => 'STS',
            'nama_jurusan' => 'Status SPP',
        ]);
        $kelas = Kelas::create([
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 1,
            'rombel' => 1,
            'nama_kelas' => 'X STS 1',
        ]);
        $tahunAjaran = TahunAjaran::create([
            'tahun_ajaran' => '2044/2045',
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
            'nipd' => '4401002',
            'nama_siswa' => 'Siswa Status',
            'jenis_kelamin' => 'L',
            'angkatan' => 2044,
            'status_siswa' => 'aktif',
        ]);
        $siswaKelas = SiswaKelas::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
        ]);

        return [$siswa, $siswaKelas, $tarif];
    }
}
