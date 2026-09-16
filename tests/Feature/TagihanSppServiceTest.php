<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TagihanSpp;
use App\Models\TahunAjaran;
use App\Models\TarifSpp;
use App\Services\TagihanSppService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class TagihanSppServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_service_generates_bill_using_class_and_rate_for_requested_period(): void
    {
        $jurusan = Jurusan::create(['kode_jurusan' => 'TGH', 'nama_jurusan' => 'Tagihan']);
        $kelasTingkatSatu = Kelas::create([
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 1,
            'rombel' => 1,
            'nama_kelas' => 'X TGH 1',
        ]);
        $kelasTingkatDua = Kelas::create([
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 2,
            'rombel' => 1,
            'nama_kelas' => 'XI TGH 1',
        ]);
        $tahunAjaranPertama = TahunAjaran::create([
            'tahun_ajaran' => '2040/2041',
            'tanggal_mulai' => '2040-07-01',
            'tanggal_selesai' => '2041-06-30',
        ]);
        $tahunAjaranKedua = TahunAjaran::create([
            'tahun_ajaran' => '2041/2042',
            'tanggal_mulai' => '2041-07-01',
            'tanggal_selesai' => '2042-06-30',
        ]);
        $siswa = Siswa::create([
            'nipd' => '4001001',
            'nama_siswa' => 'Budi Tagihan',
            'jenis_kelamin' => 'L',
            'angkatan' => 2040,
            'status_siswa' => 'aktif',
        ]);
        SiswaKelas::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelasTingkatSatu->id_kelas,
            'id_tahun_ajaran' => $tahunAjaranPertama->id_tahun_ajaran,
        ]);
        $siswaKelasKedua = SiswaKelas::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelasTingkatDua->id_kelas,
            'id_tahun_ajaran' => $tahunAjaranKedua->id_tahun_ajaran,
        ]);
        TarifSpp::create([
            'id_tahun_ajaran' => $tahunAjaranPertama->id_tahun_ajaran,
            'tingkat' => 1,
            'nominal' => 150000,
        ]);
        $tarifKedua = TarifSpp::create([
            'id_tahun_ajaran' => $tahunAjaranKedua->id_tahun_ajaran,
            'tingkat' => 2,
            'nominal' => 120000,
        ]);

        $tagihan = app(TagihanSppService::class)->generate($siswa, 8, 2041);

        $this->assertDatabaseHas('tagihan_spp', [
            'id_tagihan' => $tagihan->id_tagihan,
            'id_siswa' => $siswa->id_siswa,
            'id_siswa_kelas' => $siswaKelasKedua->id_siswa_kelas,
            'id_tarif' => $tarifKedua->id_tarif,
            'bulan' => 8,
            'tahun' => 2041,
            'nominal' => 120000,
            'status' => 'belum_bayar',
            'tanggal_lunas' => null,
        ]);
    }

    public function test_service_returns_existing_bill_without_changing_nominal_snapshot(): void
    {
        [$siswa, $tarif] = $this->siswaDenganDataTagihan();
        $service = app(TagihanSppService::class);

        $tagihan = $service->generate($siswa, 7, 2042);
        $tarif->update(['nominal' => 175000]);

        $tagihanSama = $service->generate($siswa, 7, 2042);

        $this->assertSame($tagihan->id_tagihan, $tagihanSama->id_tagihan);
        $this->assertSame(1, TagihanSpp::query()->where('id_siswa', $siswa->id_siswa)->count());
        $this->assertDatabaseHas('tagihan_spp', [
            'id_tagihan' => $tagihan->id_tagihan,
            'nominal' => 150000,
        ]);
    }

    public function test_database_rejects_duplicate_spp_period_for_the_same_student(): void
    {
        [$siswa, $tarif] = $this->siswaDenganDataTagihan();
        $tagihan = app(TagihanSppService::class)->generate($siswa, 7, 2042);

        $this->expectException(QueryException::class);

        TagihanSpp::create([
            'id_siswa' => $siswa->id_siswa,
            'id_siswa_kelas' => $tagihan->id_siswa_kelas,
            'id_tarif' => $tarif->id_tarif,
            'bulan' => 7,
            'tahun' => 2042,
            'nominal' => 150000,
            'status' => 'belum_bayar',
        ]);
    }

    public function test_service_rejects_invalid_month(): void
    {
        $siswa = Siswa::create([
            'nipd' => '4201001',
            'nama_siswa' => 'Siswa Tanpa Data',
            'jenis_kelamin' => 'P',
            'angkatan' => 2042,
            'status_siswa' => 'aktif',
        ]);
        $service = app(TagihanSppService::class);

        $this->expectException(InvalidArgumentException::class);
        $service->generate($siswa, 13, 2042);
    }

    public function test_service_requires_class_placement_for_the_requested_academic_year(): void
    {
        TahunAjaran::create([
            'tahun_ajaran' => '2043/2044',
            'tanggal_mulai' => '2043-07-01',
            'tanggal_selesai' => '2044-06-30',
        ]);
        $siswa = Siswa::create([
            'nipd' => '4301001',
            'nama_siswa' => 'Siswa Tanpa Penempatan',
            'jenis_kelamin' => 'P',
            'angkatan' => 2043,
            'status_siswa' => 'aktif',
        ]);
        $service = app(TagihanSppService::class);

        $this->expectException(LogicException::class);
        $service->generate($siswa, 7, 2043);
    }

    /**
     * @return array{0: Siswa, 1: TarifSpp}
     */
    private function siswaDenganDataTagihan(): array
    {
        $jurusan = Jurusan::create(['kode_jurusan' => 'SNP', 'nama_jurusan' => 'Snapshot']);
        $kelas = Kelas::create([
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 1,
            'rombel' => 1,
            'nama_kelas' => 'X SNP 1',
        ]);
        $tahunAjaran = TahunAjaran::create([
            'tahun_ajaran' => '2042/2043',
            'tanggal_mulai' => '2042-07-01',
            'tanggal_selesai' => '2043-06-30',
        ]);
        $tarif = TarifSpp::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tingkat' => 1,
            'nominal' => 150000,
        ]);
        $siswa = Siswa::create([
            'nipd' => '4201002',
            'nama_siswa' => 'Siswa Snapshot',
            'jenis_kelamin' => 'L',
            'angkatan' => 2042,
            'status_siswa' => 'aktif',
        ]);
        SiswaKelas::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
        ]);

        return [$siswa, $tarif];
    }
}
