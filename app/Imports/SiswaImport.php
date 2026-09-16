<?php

namespace App\Imports;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\TarifSpp;
use App\Services\TagihanSppService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Concerns\ToModel;
use Throwable;

class SiswaImport implements ToModel
{
    use RemembersRowNumber;

    private int $berhasil = 0;

    private int $duplikat = 0;

    /**
     * @var list<array{baris: int, alasan: string}>
     */
    private array $errors = [];

    /**
     * @var array<string, int|string>|null
     */
    private ?array $kolomHeader = null;

    private bool $headerTidakDitemukan = false;

    private Collection $kelasCache;

    public function __construct(
        private readonly TahunAjaran $tahunAjaran,
        private readonly TagihanSppService $tagihanSppService,
    ) {
        $this->kelasCache = Kelas::query()->with('jurusan')->get()->keyBy('nama_kelas');
    }

    /**
     * @param  array<int|string, mixed>  $row
     */
    public function model(array $row): ?Siswa
    {
        $baris = $this->getRowNumber();

        if ($this->kolomHeader === null) {
            $this->kolomHeader = $this->deteksiKolomHeader($row);

            return null;
        }

        if (! array_filter($row, fn (mixed $value): bool => filled($value))) {
            return null;
        }

        if ($this->deteksiKolomHeader($row) !== null) {
            return null;
        }

        $nipd = $this->nilaiKolom($row, 'nipd');
        $namaSiswa = $this->normalisasiNama($this->nilaiKolom($row, 'nama'));
        $jenisKelamin = strtoupper($this->nilaiKolom($row, 'jk'));
        $namaKelas = preg_replace('/\s+/', ' ', $this->nilaiKolom($row, 'rombel'));

        if ($nipd === '') {
            $this->catatError($baris, 'NIPD wajib diisi.');

            return null;
        }

        if (strlen($nipd) > 30) {
            $this->catatError($baris, 'NIPD maksimal 30 karakter.');

            return null;
        }

        if ($namaSiswa === '' || mb_strlen($namaSiswa, 'UTF-8') < 2) {
            $this->catatError($baris, 'Nama siswa tidak valid.');

            return null;
        }

        if (strlen($namaSiswa) > 100) {
            $this->catatError($baris, 'Nama siswa wajib diisi dan maksimal 100 karakter.');

            return null;
        }

        if (! in_array($jenisKelamin, ['L', 'P'], true)) {
            $this->catatError($baris, 'JK harus bernilai L atau P.');

            return null;
        }

        if (Siswa::withTrashed()->where('nipd', $nipd)->exists()) {
            $this->duplikat++;

            return null;
        }

        $kelas = $this->kelasCache->get($namaKelas);

        if (! $kelas) {
            $this->catatError($baris, "Kelas '{$namaKelas}' tidak ditemukan.");

            return null;
        }

        try {
            DB::transaction(function () use ($nipd, $namaSiswa, $jenisKelamin, $kelas): void {
                $siswa = Siswa::create([
                    'nipd' => $nipd,
                    'nama_siswa' => $namaSiswa,
                    'jenis_kelamin' => $jenisKelamin,
                    'angkatan' => $this->tahunAjaran->tanggal_mulai->year - ($kelas->tingkat - 1),
                    'status_siswa' => 'aktif',
                ]);

                $siswaKelasId = SiswaKelas::create([
                    'id_siswa' => $siswa->id_siswa,
                    'id_kelas' => $kelas->id_kelas,
                    'id_tahun_ajaran' => $this->tahunAjaran->id_tahun_ajaran,
                ])->id_siswa_kelas;

                $siswaKelas = SiswaKelas::with('kelas')->findOrFail($siswaKelasId);
                $tarif = TarifSpp::query()
                    ->where('id_tahun_ajaran', $this->tahunAjaran->id_tahun_ajaran)
                    ->where('tingkat', $kelas->tingkat)
                    ->first();

                if (! $tarif) {
                    throw new LogicException('Tarif SPP untuk tingkat dan tahun ajaran tidak ditemukan.');
                }

                $this->tagihanSppService->generateDenganKonteks(
                    $siswa,
                    $this->tahunAjaran,
                    $siswaKelas,
                    $tarif,
                );
            });
        } catch (LogicException $exception) {
            $this->catatError($baris, $exception->getMessage());

            return null;
        } catch (QueryException) {
            if (Siswa::withTrashed()->where('nipd', $nipd)->exists()) {
                $this->duplikat++;
            } else {
                $this->catatError($baris, 'Data siswa tidak dapat disimpan.');
            }

            return null;
        } catch (Throwable) {
            $this->catatError($baris, 'Data siswa tidak dapat diproses.');

            return null;
        }

        $this->berhasil++;

        return null;
    }

    /**
     * @return array{berhasil: int, duplikat: int, errors: list<array{baris: int, alasan: string}>}
     */
    public function result(): array
    {
        if ($this->kolomHeader === null && ! $this->headerTidakDitemukan) {
            $this->catatError(0, 'Header wajib Rombel, NIPD, Nama, dan JK tidak ditemukan.');
            $this->headerTidakDitemukan = true;
        }

        return [
            'berhasil' => $this->berhasil,
            'duplikat' => $this->duplikat,
            'errors' => $this->errors,
        ];
    }

    private function catatError(int $baris, string $alasan): void
    {
        $this->errors[] = [
            'baris' => $baris,
            'alasan' => $alasan,
        ];
    }

    /**
     * @param  array<int|string, mixed>  $row
     * @return array<string, int|string>|null
     */
    private function deteksiKolomHeader(array $row): ?array
    {
        $kolomHeader = [];

        foreach ($row as $kolom => $value) {
            $namaKolom = match ($this->normalisasiHeader($value)) {
                'rombel', 'kelas' => 'rombel',
                'nipd' => 'nipd',
                'nama', 'namasiswa' => 'nama',
                'jk', 'jeniskelamin' => 'jk',
                default => null,
            };

            if ($namaKolom !== null) {
                $kolomHeader[$namaKolom] = $kolom;
            }
        }

        return count($kolomHeader) === 4 ? $kolomHeader : null;
    }

    /**
     * @param  array<int|string, mixed>  $row
     */
    private function nilaiKolom(array $row, string $namaKolom): string
    {
        return trim((string) ($row[$this->kolomHeader[$namaKolom]] ?? ''));
    }

    private function normalisasiHeader(mixed $value): string
    {
        return strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '', trim((string) $value)));
    }

    private function normalisasiNama(string $nama): string
    {
        $nama = trim($nama);
        $nama = preg_replace("/[^\p{L}\s.',-]/u", '', $nama) ?? '';
        $nama = preg_replace('/\s+/u', ' ', $nama) ?? '';
        $nama = mb_convert_case($nama, MB_CASE_TITLE, 'UTF-8');
        $kata = explode(' ', $nama);

        foreach ($kata as $index => $nilai) {
            if ($index > 0 && in_array(mb_strtolower($nilai, 'UTF-8'), ['bin', 'binti', 'van', 'de', 'el', 'al'], true)) {
                $kata[$index] = mb_strtolower($nilai, 'UTF-8');
            }
        }

        return implode(' ', $kata);
    }
}
