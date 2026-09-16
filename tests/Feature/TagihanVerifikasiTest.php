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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Throwable;

class TagihanVerifikasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_untuk_tahun_ajaran_is_idempotent_when_bills_exist(): void
    {
        [$siswa, $tahunAjaran] = $this->buatFixtureTagihan();
        $service = app(TagihanSppService::class);

        $tagihanPertama = $service->generateUntukTahunAjaran($siswa, $tahunAjaran);
        $this->assertCount(12, $tagihanPertama);
        $this->assertSame(12, TagihanSpp::query()->where('id_siswa', $siswa->id_siswa)->count());

        $tagihanKedua = null;
        $exception = null;

        try {
            $tagihanKedua = $service->generateUntukTahunAjaran($siswa, $tahunAjaran);
        } catch (Throwable $caught) {
            $exception = $caught;
        }

        $totalTagihan = TagihanSpp::query()->where('id_siswa', $siswa->id_siswa)->count();

        $this->laporkan('Verifikasi 1 - Pemanggilan generator kedua', [
            'panggilan_pertama' => [
                'jumlah_tagihan_dikembalikan' => $tagihanPertama->count(),
                'id_tagihan' => $tagihanPertama->pluck('id_tagihan')->all(),
            ],
            'panggilan_kedua' => $exception
                ? [
                    'hasil' => 'exception',
                    'exception_class' => $exception::class,
                    'exception_message' => $exception->getMessage(),
                ]
                : [
                    'hasil' => 'tanpa exception',
                    'jumlah_tagihan_dikembalikan' => $tagihanKedua->count(),
                    'id_tagihan' => $tagihanKedua->pluck('id_tagihan')->all(),
                ],
            'total_tagihan_di_database' => $totalTagihan,
            'kesimpulan' => $exception
                ? 'Pemanggilan kedua melempar exception.'
                : ($totalTagihan === 12
                    ? 'Pemanggilan kedua idempoten: tagihan yang ada dikembalikan tanpa insert duplikat.'
                    : 'Pemanggilan kedua menambah tagihan, sehingga perlu investigasi duplikasi.'),
        ]);

        $this->assertNull($exception, 'Pemanggilan generator kedua tidak boleh melempar exception.');
        $this->assertCount(12, $tagihanKedua);
        $this->assertSame(12, $totalTagihan);
        $this->assertSame(
            $tagihanPertama->pluck('id_tagihan')->all(),
            $tagihanKedua->pluck('id_tagihan')->all(),
        );
    }

    public function test_tagihan_spp_id_siswa_index_exists_and_explain_reports_its_usage(): void
    {
        [$siswa, $tahunAjaran] = $this->buatFixtureTagihan(1);
        $this->assertSame(1, $siswa->id_siswa);

        app(TagihanSppService::class)->generateUntukTahunAjaran($siswa, $tahunAjaran);

        $index = DB::select('SHOW INDEX FROM tagihan_spp');
        $rencanaQuery = DB::select('EXPLAIN SELECT * FROM tagihan_spp WHERE id_siswa = 1');
        $indexIdSiswa = collect($index)->first(
            fn (object $baris): bool => $baris->Column_name === 'id_siswa' && (int) $baris->Seq_in_index === 1,
        );
        $plan = $rencanaQuery[0] ?? null;
        $key = $plan?->key;
        $type = $plan?->type;
        $indexDigunakan = $key !== null && $key !== 'NULL';

        $this->laporkan('Verifikasi 2 - Index tagihan_spp.id_siswa', [
            'show_index' => $index,
            'explain_select_id_siswa_1' => $rencanaQuery,
            'index_dengan_kolom_pertama_id_siswa' => $indexIdSiswa,
            'key_dari_explain' => $key,
            'type_dari_explain' => $type,
            'kesimpulan' => $indexDigunakan
                ? 'Index tersedia dan dipilih oleh query engine; index manual tambahan tidak diperlukan.'
                : 'Index tersedia, tetapi query engine memilih full scan untuk fixture ini; evaluasi ulang dengan data produksi sebelum menambah index manual.',
        ]);

        $this->assertNotNull($indexIdSiswa, 'Index dengan id_siswa sebagai kolom pertama harus tersedia.');
    }

    /**
     * @return array{0: Siswa, 1: TahunAjaran}
     */
    private function buatFixtureTagihan(?int $idSiswa = null): array
    {
        $jurusan = Jurusan::create([
            'kode_jurusan' => 'VFY',
            'nama_jurusan' => 'Verifikasi',
        ]);
        $kelas = Kelas::create([
            'id_jurusan' => $jurusan->id_jurusan,
            'tingkat' => 1,
            'rombel' => 1,
            'nama_kelas' => 'X VFY 1',
        ]);
        $tahunAjaran = TahunAjaran::create([
            'tahun_ajaran' => '2042/2043',
            'tanggal_mulai' => '2042-07-01',
            'tanggal_selesai' => '2043-06-30',
        ]);
        TarifSpp::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'tingkat' => 1,
            'nominal' => 150000,
        ]);

        $siswa = new Siswa([
            'nipd' => '4201001',
            'nama_siswa' => 'Siswa Verifikasi',
            'jenis_kelamin' => 'L',
            'angkatan' => 2042,
            'status_siswa' => 'aktif',
        ]);

        if ($idSiswa !== null) {
            $siswa->id_siswa = $idSiswa;
        }

        $siswa->save();

        SiswaKelas::create([
            'id_siswa' => $siswa->id_siswa,
            'id_kelas' => $kelas->id_kelas,
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
        ]);

        return [$siswa, $tahunAjaran];
    }

    /**
     * @param  array<string, mixed>  $hasil
     */
    private function laporkan(string $judul, array $hasil): void
    {
        fwrite(STDOUT, PHP_EOL.$judul.PHP_EOL.json_encode($hasil, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }
}
