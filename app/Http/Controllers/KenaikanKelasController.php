<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\TarifSpp;
use App\Services\TagihanSppService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use LogicException;

class KenaikanKelasController extends Controller
{
    public function preview(Request $request): View
    {
        $filters = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'tetap_di_kelas_asal' => ['nullable', 'array'],
            'tetap_di_kelas_asal.*' => ['integer'],
        ]);
        $preview = $this->previewData();
        $idSiswaTetapKelas = collect(old('tetap_di_kelas_asal', $filters['tetap_di_kelas_asal'] ?? []))
            ->map(fn (mixed $idSiswa): int => (int) $idSiswa)
            ->unique()
            ->values()
            ->all();
        $kandidatTetapKelas = collect();

        if (! $preview['error'] && ! $preview['info']) {
            $kandidat = $preview['kandidat'];
            $kandidatTetapKelas = $kandidat
                ->filter(fn (array $item): bool => in_array($item['id_siswa'], $idSiswaTetapKelas, true))
                ->map(fn (array $item): array => [
                    'id_siswa' => $item['id_siswa'],
                    'nipd' => $item['nipd'],
                    'nama_siswa' => $item['nama_siswa'],
                    'kelas_asal' => $item['kelas_asal'],
                ])
                ->values();

            if ($filters['cari'] ?? null) {
                $cari = mb_strtolower($filters['cari']);
                $kandidat = $kandidat->filter(fn (array $item): bool => str_contains(mb_strtolower($item['nama_siswa']), $cari)
                    || str_contains(mb_strtolower($item['nipd']), $cari));
            }

            $preview['kandidat'] = $this->paginateKandidat($kandidat, $request);
        }

        return view('kenaikan-kelas.preview', [
            ...$preview,
            'filters' => $filters,
            'idSiswaTetapKelas' => $idSiswaTetapKelas,
            'kandidatTetapKelas' => $kandidatTetapKelas,
        ]);
    }

    public function proses(Request $request, TagihanSppService $tagihanSppService): RedirectResponse
    {
        $data = $request->validate([
            'tetap_di_kelas_asal' => ['nullable', 'array'],
            'tetap_di_kelas_asal.*' => ['integer', 'distinct'],
        ]);
        $preview = $this->previewData();

        if ($preview['error'] || $preview['info']) {
            return to_route('kenaikan-kelas.preview')->with('error', $preview['error'] ?? $preview['info']);
        }

        $idSiswaKandidat = collect($preview['kandidat'])->pluck('id_siswa');
        $idSiswaTetapKelas = collect($data['tetap_di_kelas_asal'] ?? [])
            ->map(fn (mixed $idSiswa): int => (int) $idSiswa)
            ->unique()
            ->values();

        if ($idSiswaTetapKelas->diff($idSiswaKandidat)->isNotEmpty()) {
            return back()->withInput()->with(
                'error',
                'Data siswa yang tetap di kelas asal tidak valid. Muat ulang halaman sebelum memproses kenaikan kelas.',
            );
        }

        $tingkatTarifDibutuhkan = collect($preview['kandidat'])
            ->map(function (array $kandidat) use ($idSiswaTetapKelas): ?int {
                if ($idSiswaTetapKelas->contains($kandidat['id_siswa'])) {
                    return $kandidat['tingkat_asal'];
                }

                return $kandidat['tingkat_tujuan'];
            })
            ->filter()
            ->unique();
        $tingkatTarifTersedia = TarifSpp::query()
            ->where('id_tahun_ajaran', $preview['tahunAjaranTujuan']->id_tahun_ajaran)
            ->whereIn('tingkat', $tingkatTarifDibutuhkan)
            ->pluck('tingkat')
            ->map(fn (int $tingkat): int => $tingkat);
        $tingkatTarifBelumAda = $tingkatTarifDibutuhkan->diff($tingkatTarifTersedia);

        if ($tingkatTarifBelumAda->isNotEmpty()) {
            return back()->withInput()->with(
                'error',
                'Tarif SPP tingkat '.implode(', ', $tingkatTarifBelumAda->all()).' pada tahun ajaran tujuan belum tersedia.',
            );
        }

        try {
            $hasil = DB::transaction(function () use ($preview, $idSiswaTetapKelas, $tagihanSppService): array {
                $naikKelas = 0;
                $tetapKelas = 0;
                $lulus = 0;

                foreach ($preview['kandidat'] as $kandidat) {
                    $tetapDiKelasAsal = $idSiswaTetapKelas->contains($kandidat['id_siswa']);

                    if ($kandidat['kelompok'] === 'Lulus' && ! $tetapDiKelasAsal) {
                        $kandidat['siswa']->update(['status_siswa' => 'lulus']);
                        $lulus++;

                        continue;
                    }

                    SiswaKelas::create([
                        'id_siswa' => $kandidat['id_siswa'],
                        'id_kelas' => $tetapDiKelasAsal ? $kandidat['id_kelas_asal'] : $kandidat['id_kelas_tujuan'],
                        'id_tahun_ajaran' => $preview['tahunAjaranTujuan']->id_tahun_ajaran,
                    ]);
                    $tagihanSppService->generateUntukTahunAjaran(
                        $kandidat['siswa'],
                        $preview['tahunAjaranTujuan'],
                    );
                    if ($tetapDiKelasAsal) {
                        $tetapKelas++;
                    } else {
                        $naikKelas++;
                    }
                }

                return [
                    'naik_kelas' => $naikKelas,
                    'tetap_kelas' => $tetapKelas,
                    'lulus' => $lulus,
                ];
            });
        } catch (LogicException|QueryException $exception) {
            return back()->withInput()->with(
                'error',
                "Kenaikan kelas tidak dapat diproses: {$exception->getMessage()}",
            );
        }

        return to_route('kenaikan-kelas.preview')->with(
            'status',
            "Proses kenaikan kelas selesai: {$hasil['naik_kelas']} siswa naik kelas, {$hasil['tetap_kelas']} siswa tetap di kelas asal, dan {$hasil['lulus']} siswa lulus.",
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function previewData(): array
    {
        $tahunAjaranTujuan = TahunAjaran::aktif()->first();
        $data = [
            'tahunAjaranTujuan' => $tahunAjaranTujuan,
            'tahunAjaranAsal' => null,
            'kandidat' => collect(),
            'ringkasan' => collect(),
            'error' => null,
            'info' => null,
        ];

        if (! $tahunAjaranTujuan) {
            return [...$data, 'error' => 'Aktifkan tahun ajaran tujuan sebelum memproses kenaikan kelas.'];
        }

        $tahunAjaranAsal = TahunAjaran::query()
            ->whereDate('tanggal_selesai', $tahunAjaranTujuan->tanggal_mulai->copy()->subDay())
            ->first();

        if (! $tahunAjaranAsal) {
            return [...$data, 'error' => 'Tahun ajaran asal sebelum tahun ajaran aktif tidak ditemukan.'];
        }

        $penempatanAsal = SiswaKelas::query()
            ->with(['siswa', 'kelas.jurusan'])
            ->where('id_tahun_ajaran', $tahunAjaranAsal->id_tahun_ajaran)
            ->whereHas('siswa', fn ($query) => $query->where('status_siswa', 'aktif'))
            ->get()
            ->sortBy('siswa.nama_siswa')
            ->values();

        if ($penempatanAsal->isEmpty()) {
            return [...$data, 'tahunAjaranAsal' => $tahunAjaranAsal, 'error' => 'Tidak ada siswa aktif pada tahun ajaran asal yang dapat diproses.'];
        }

        $kelasTujuan = Kelas::query()
            ->whereIn('tingkat', [2, 3])
            ->get()
            ->keyBy(fn (Kelas $kelas): string => "{$kelas->id_jurusan}-{$kelas->tingkat}-{$kelas->rombel}");
        $kandidat = collect();

        foreach ($penempatanAsal as $penempatan) {
            $kelasAsal = $penempatan->kelas;

            if ($kelasAsal->tingkat === 3) {
                $kandidat->push([
                    'id_siswa' => $penempatan->id_siswa,
                    'siswa' => $penempatan->siswa,
                    'nipd' => $penempatan->siswa->nipd,
                    'nama_siswa' => $penempatan->siswa->nama_siswa,
                    'id_kelas_asal' => $kelasAsal->id_kelas,
                    'kelas_asal' => $kelasAsal->nama_kelas,
                    'kelas_tujuan' => 'Lulus',
                    'id_kelas_tujuan' => null,
                    'tingkat_asal' => $kelasAsal->tingkat,
                    'tingkat_tujuan' => null,
                    'kelompok' => 'Lulus',
                ]);

                continue;
            }

            $tingkatTujuan = $kelasAsal->tingkat + 1;
            $kelas = $kelasTujuan->get("{$kelasAsal->id_jurusan}-{$tingkatTujuan}-{$kelasAsal->rombel}");

            if (! $kelas) {
                return [...$data, 'tahunAjaranAsal' => $tahunAjaranAsal, 'error' => "Kelas tujuan untuk {$kelasAsal->nama_kelas} tidak ditemukan."];
            }

            $kandidat->push([
                'id_siswa' => $penempatan->id_siswa,
                'siswa' => $penempatan->siswa,
                'nipd' => $penempatan->siswa->nipd,
                'nama_siswa' => $penempatan->siswa->nama_siswa,
                'id_kelas_asal' => $kelasAsal->id_kelas,
                'kelas_asal' => $kelasAsal->nama_kelas,
                'kelas_tujuan' => $kelas->nama_kelas,
                'id_kelas_tujuan' => $kelas->id_kelas,
                'tingkat_asal' => $kelasAsal->tingkat,
                'tingkat_tujuan' => $tingkatTujuan,
                'kelompok' => $tingkatTujuan === 2 ? 'Naik ke XI' : 'Naik ke XII',
            ]);
        }

        $idSiswaSudahDitempatkan = SiswaKelas::query()
            ->where('id_tahun_ajaran', $tahunAjaranTujuan->id_tahun_ajaran)
            ->whereIn('id_siswa', $kandidat->pluck('id_siswa'))
            ->pluck('id_siswa');
        $kandidat = $kandidat
            ->reject(fn (array $item): bool => $idSiswaSudahDitempatkan->contains($item['id_siswa']))
            ->values();

        if ($kandidat->isEmpty()) {
            return [
                'tahunAjaranTujuan' => $tahunAjaranTujuan,
                'tahunAjaranAsal' => $tahunAjaranAsal,
                'kandidat' => $kandidat,
                'ringkasan' => collect(),
                'error' => null,
                'info' => 'Tidak ada kandidat kenaikan kelas yang tersisa untuk diproses.',
            ];
        }

        $tingkatTujuan = $kandidat->pluck('tingkat_tujuan')->filter()->unique();
        $tingkatTarifTersedia = TarifSpp::query()
            ->where('id_tahun_ajaran', $tahunAjaranTujuan->id_tahun_ajaran)
            ->whereIn('tingkat', $tingkatTujuan)
            ->pluck('tingkat')
            ->map(fn (int $tingkat): int => $tingkat);
        $tingkatTarifBelumAda = $tingkatTujuan->diff($tingkatTarifTersedia);

        if ($tingkatTarifBelumAda->isNotEmpty()) {
            return [...$data, 'tahunAjaranAsal' => $tahunAjaranAsal, 'error' => 'Tarif SPP tingkat '.implode(', ', $tingkatTarifBelumAda->all()).' pada tahun ajaran tujuan belum tersedia.'];
        }

        return [
            'tahunAjaranTujuan' => $tahunAjaranTujuan,
            'tahunAjaranAsal' => $tahunAjaranAsal,
            'kandidat' => $kandidat,
            'ringkasan' => $kandidat->countBy('kelompok')->sortKeys(),
            'error' => null,
            'info' => null,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $kandidat
     */
    private function paginateKandidat(Collection $kandidat, Request $request): LengthAwarePaginator
    {
        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $kandidat->forPage($page, $perPage)->values(),
            $kandidat->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );
    }
}
