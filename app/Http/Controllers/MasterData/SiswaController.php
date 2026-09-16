<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\SiswaRequest;
use App\Imports\SiswaImport;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Services\TagihanSppService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use LogicException;
use Maatwebsite\Excel\Facades\Excel;

class SiswaController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'status_siswa' => ['nullable', 'in:aktif,lulus,pindah'],
            'id_jurusan' => ['nullable', 'integer', 'exists:jurusan,id_jurusan'],
            'tingkat' => ['nullable', 'integer', 'in:1,2,3'],
            'rombel' => ['nullable', 'integer', 'min:1', 'max:255'],
        ]);
        $tahunAjaranAktif = TahunAjaran::query()->where('aktif', true)->first();

        return view('master.siswa.index', [
            'siswa' => Siswa::query()
                ->withExists([
                    'pembayaran as memiliki_pembayaran',
                    'tagihanSpp as memiliki_tagihan_lunas' => fn (Builder $query) => $query->where('status', 'lunas'),
                ])
                ->with(['siswaKelas' => function ($query) use ($tahunAjaranAktif) {
                    $query
                        ->where('id_tahun_ajaran', $tahunAjaranAktif?->id_tahun_ajaran)
                        ->with('kelas.jurusan');
                }])
                ->when($filters['cari'] ?? null, function ($query, $cari) {
                    $query->where(function ($query) use ($cari) {
                        $query->where('nipd', 'like', "%{$cari}%")
                            ->orWhere('nama_siswa', 'like', "%{$cari}%");
                    });
                })
                ->when($filters['status_siswa'] ?? null, fn (Builder $query, string $status) => $query->where('status_siswa', $status))
                ->when(! ($filters['status_siswa'] ?? null), fn (Builder $query) => $query->where('status_siswa', '!=', 'lulus'))
                ->when(
                    ($filters['id_jurusan'] ?? null) || ($filters['tingkat'] ?? null) || ($filters['rombel'] ?? null),
                    function (Builder $query) use ($filters, $tahunAjaranAktif) {
                        $query->whereHas('siswaKelas', function (Builder $query) use ($filters, $tahunAjaranAktif) {
                            $query
                                ->where('id_tahun_ajaran', $tahunAjaranAktif?->id_tahun_ajaran)
                                ->whereHas('kelas', function (Builder $query) use ($filters) {
                                    $query
                                        ->when($filters['id_jurusan'] ?? null, fn (Builder $query, int $idJurusan) => $query->where('id_jurusan', $idJurusan))
                                        ->when($filters['tingkat'] ?? null, fn (Builder $query, int $tingkat) => $query->where('tingkat', $tingkat))
                                        ->when($filters['rombel'] ?? null, fn (Builder $query, int $rombel) => $query->where('rombel', $rombel));
                                });
                        });
                    },
                )
                ->orderBy('nama_siswa')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $filters,
            'tahunAjaranAktif' => $tahunAjaranAktif,
            'jurusan' => Jurusan::query()->orderBy('nama_jurusan')->get(),
        ]);
    }

    public function create(): View
    {
        return view('master.siswa.create', $this->formData());
    }

    public function nonaktif(Request $request): View
    {
        $filters = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
        ]);

        return view('master.siswa.nonaktif', [
            'siswa' => Siswa::onlyTrashed()
                ->when($filters['cari'] ?? null, function (Builder $query, string $cari) {
                    $query->where(function (Builder $query) use ($cari) {
                        $query->where('nipd', 'like', "%{$cari}%")
                            ->orWhere('nama_siswa', 'like', "%{$cari}%");
                    });
                })
                ->orderByDesc('deleted_at')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function importForm(): View
    {
        return view('master.siswa.import', [
            'tahunAjaranAktif' => TahunAjaran::aktif()->first(),
        ]);
    }

    public function import(Request $request, TagihanSppService $tagihanSppService): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);
        $tahunAjaranAktif = TahunAjaran::aktif()->first();

        if (! $tahunAjaranAktif) {
            return back()->with('error', 'Aktifkan tahun ajaran sebelum mengimpor siswa.');
        }

        $tingkatWajib = [1, 2, 3];
        $tingkatTersedia = $tahunAjaranAktif->tarifSpp()
            ->whereIn('tingkat', $tingkatWajib)
            ->pluck('tingkat')
            ->map(fn (int $tingkat): int => $tingkat)
            ->all();
        $tingkatBelumAda = array_values(array_diff($tingkatWajib, $tingkatTersedia));

        if ($tingkatBelumAda) {
            $tingkat = implode(', ', $tingkatBelumAda);

            return back()->with('error', "Import tidak dapat diproses karena tarif SPP tingkat {$tingkat} belum tersedia.");
        }

        $import = new SiswaImport($tahunAjaranAktif, $tagihanSppService);
        Excel::import($import, $data['file']);

        return to_route('master.siswa.import.form')->with('import_result', $import->result());
    }

    public function store(SiswaRequest $request, TagihanSppService $tagihanSppService): RedirectResponse
    {
        $tahunAjaranAktif = TahunAjaran::query()->where('aktif', true)->first();

        if (! $tahunAjaranAktif) {
            return back()->withInput()->withErrors([
                'id_kelas' => 'Tambahkan dan aktifkan tahun ajaran sebelum menambahkan siswa.',
            ]);
        }

        $data = $request->validated();
        $idKelas = $data['id_kelas'];
        unset($data['id_kelas']);

        try {
            $siswa = DB::transaction(function () use ($data, $idKelas, $tahunAjaranAktif, $tagihanSppService) {
                $siswa = Siswa::create($data);

                SiswaKelas::create([
                    'id_siswa' => $siswa->id_siswa,
                    'id_kelas' => $idKelas,
                    'id_tahun_ajaran' => $tahunAjaranAktif->id_tahun_ajaran,
                ]);

                $tagihanSppService->generateUntukTahunAjaran($siswa, $tahunAjaranAktif);

                return $siswa;
            });
        } catch (LogicException $exception) {
            return back()->withInput()->withErrors([
                'id_kelas' => $exception->getMessage(),
            ]);
        }

        return to_route('master.siswa.index')->with('status', 'Siswa berhasil ditambahkan beserta 12 tagihan SPP.');
    }

    public function edit(Siswa $siswa): View
    {
        return view('master.siswa.edit', [
            'siswa' => $siswa,
            ...$this->formData($siswa),
        ]);
    }

    public function update(SiswaRequest $request, Siswa $siswa): RedirectResponse
    {
        $data = $request->validated();
        $idKelas = $data['id_kelas'] ?? null;
        unset($data['id_kelas']);

        try {
            DB::transaction(function () use ($siswa, $data, $idKelas): void {
                $siswa->update($data);

                if (! $idKelas) {
                    return;
                }

                $tahunAjaranAktif = TahunAjaran::aktif()->first();
                $penempatanAktif = $siswa->siswaKelas()
                    ->with('kelas')
                    ->where('id_tahun_ajaran', $tahunAjaranAktif?->id_tahun_ajaran)
                    ->lockForUpdate()
                    ->first();
                $kelasTujuan = Kelas::find($idKelas);

                if (! $tahunAjaranAktif || ! $penempatanAktif || ! $kelasTujuan) {
                    throw new LogicException('Penempatan kelas pada tahun ajaran aktif tidak ditemukan.');
                }

                if ($penempatanAktif->kelas->tingkat !== $kelasTujuan->tingkat) {
                    throw new LogicException('Kelas hanya dapat diubah ke kelas lain pada tingkat yang sama.');
                }

                $penempatanAktif->update(['id_kelas' => $kelasTujuan->id_kelas]);
            });
        } catch (LogicException $exception) {
            return back()->withInput()->withErrors(['id_kelas' => $exception->getMessage()]);
        }

        return to_route('master.siswa.index')->with('status', 'Data siswa dan kelas aktif berhasil diperbarui.');
    }

    public function nonaktifkan(Request $request, Siswa $siswa): RedirectResponse
    {
        $konfirmasiNis = $request->validate([
            'konfirmasi_nipd' => ['required', 'string'],
        ])['konfirmasi_nipd'];

        if (! hash_equals($siswa->nipd, $konfirmasiNis)) {
            return back()->with('error', 'NIPD konfirmasi tidak sesuai. Siswa tidak dinonaktifkan.');
        }

        $siswa->delete();

        return to_route('master.siswa.index')->with('status', 'Siswa dinonaktifkan dari daftar aktif. Riwayat kelas dan pembayaran tetap tersimpan.');
    }

    public function destroy(Request $request, Siswa $siswa): RedirectResponse
    {
        $konfirmasiNis = $request->validate([
            'konfirmasi_nipd' => ['required', 'string'],
        ])['konfirmasi_nipd'];

        if (! hash_equals($siswa->nipd, $konfirmasiNis)) {
            return back()->with('error', 'NIPD konfirmasi tidak sesuai. Siswa tidak dihapus permanen.');
        }

        try {
            DB::transaction(function () use ($siswa): void {
                $tagihanSpp = $siswa->tagihanSpp()->lockForUpdate()->get();
                $memilikiDetailPembayaran = $siswa->tagihanSpp()->whereHas('detailPembayaran')->exists();

                if ($siswa->pembayaran()->lockForUpdate()->exists() || $memilikiDetailPembayaran || $tagihanSpp->contains('status', 'lunas')) {
                    throw new LogicException('Siswa dengan riwayat pembayaran atau tagihan lunas tidak dapat dihapus permanen. Nonaktifkan siswa untuk mempertahankan histori.');
                }

                $siswa->tagihanSpp()->delete();
                $siswa->siswaKelas()->delete();
                $siswa->forceDelete();
            });
        } catch (LogicException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('master.siswa.index')->with('status', 'Siswa, tagihan belum bayar, dan riwayat kelas berhasil dihapus permanen.');
    }

    /**
     * @return array{angkatanPilihan: list<int>, idKelasAktif: int|null, kelas: Collection<int, Kelas>, tahunAjaranAktif: TahunAjaran|null, tingkatKelasAktif: int|null}
     */
    private function formData(?Siswa $siswa = null): array
    {
        $tahunAjaranAktif = TahunAjaran::query()->where('aktif', true)->first();
        $angkatanPilihan = $tahunAjaranAktif
            ? range($tahunAjaranAktif->tanggal_mulai->year, $tahunAjaranAktif->tanggal_mulai->year - 4)
            : [];

        if ($siswa && ! in_array($siswa->angkatan, $angkatanPilihan, true)) {
            $angkatanPilihan[] = $siswa->angkatan;
            rsort($angkatanPilihan);
        }

        $penempatanAktif = $siswa
            ? $siswa->siswaKelas()
                ->with('kelas')
                ->where('id_tahun_ajaran', $tahunAjaranAktif?->id_tahun_ajaran)
                ->first()
            : null;

        return [
            'kelas' => Kelas::query()
                ->with('jurusan')
                ->orderBy('tingkat')
                ->orderBy('nama_kelas')
                ->get(),
            'tahunAjaranAktif' => $tahunAjaranAktif,
            'angkatanPilihan' => $angkatanPilihan,
            'idKelasAktif' => $penempatanAktif?->id_kelas,
            'tingkatKelasAktif' => $penempatanAktif?->kelas?->tingkat,
        ];
    }
}
