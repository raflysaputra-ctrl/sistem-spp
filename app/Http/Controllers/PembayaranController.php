<?php

namespace App\Http\Controllers;

use App\Http\Requests\PembayaranRequest;
use App\Models\Jurusan;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\User;
use App\Services\PembayaranService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PembayaranController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'id_jurusan' => ['nullable', 'integer', 'exists:jurusan,id_jurusan'],
            'tingkat' => ['nullable', 'integer', 'in:1,2,3'],
            'rombel' => ['nullable', 'integer', 'min:1', 'max:255'],
            'status_siswa' => ['nullable', 'in:aktif,lulus,pindah,semua'],
        ]);

        foreach (['id_jurusan', 'tingkat', 'rombel'] as $filter) {
            if (isset($filters[$filter])) {
                $filters[$filter] = (int) $filters[$filter];
            }
        }

        $tahunAjaranAktif = fn (Builder $query) => $query->where('aktif', true);
        $adaFilterKelas = ($filters['id_jurusan'] ?? null)
            || ($filters['tingkat'] ?? null)
            || ($filters['rombel'] ?? null);
        $statusSiswa = $filters['status_siswa'] ?? 'aktif';

        $siswa = Siswa::query()
            ->with(['siswaKelas' => function ($query) use ($tahunAjaranAktif) {
                $query->whereHas('tahunAjaran', $tahunAjaranAktif)->with('kelas.jurusan');
            }])
            ->when($filters['cari'] ?? null, function (Builder $query, string $cari) {
                $query->where(function (Builder $query) use ($cari) {
                    $query->where('nipd', 'like', "%{$cari}%")
                        ->orWhere('nama_siswa', 'like', "%{$cari}%");
                });
            })
            ->when($statusSiswa !== 'semua', fn (Builder $query) => $query->where('status_siswa', $statusSiswa))
            ->when($adaFilterKelas, function (Builder $query) use ($filters, $tahunAjaranAktif) {
                $query->whereHas('siswaKelas', function (Builder $query) use ($filters, $tahunAjaranAktif) {
                    $query
                        ->whereHas('tahunAjaran', $tahunAjaranAktif)
                        ->whereHas('kelas', function (Builder $query) use ($filters) {
                            $query
                                ->when($filters['id_jurusan'] ?? null, fn (Builder $query, int $idJurusan) => $query->where('id_jurusan', $idJurusan))
                                ->when($filters['tingkat'] ?? null, fn (Builder $query, int $tingkat) => $query->where('tingkat', $tingkat))
                                ->when($filters['rombel'] ?? null, fn (Builder $query, int $rombel) => $query->where('rombel', $rombel));
                        });
                });
            })
            ->orderBy('nama_siswa')
            ->paginate(10)
            ->withQueryString();

        return view('pembayaran.index', [
            'filters' => $filters,
            'siswa' => $siswa,
            'jurusan' => Jurusan::query()->orderBy('nama_jurusan')->get(),
            'statusSiswa' => $statusSiswa,
        ]);
    }

    public function show(Siswa $siswa): View
    {
        return view('pembayaran.show', [
            'siswa' => $siswa,
            'kelasAktif' => $siswa->siswaKelas()
                ->with(['kelas.jurusan', 'tahunAjaran'])
                ->whereHas('tahunAjaran', fn ($query) => $query->where('aktif', true))
                ->first(),
            'tagihanSpp' => $siswa->tagihanSpp()
                ->with(['siswaKelas.kelas.jurusan', 'siswaKelas.tahunAjaran'])
                ->orderBy('tahun')
                ->orderBy('bulan')
                ->get(),
        ]);
    }

    public function store(PembayaranRequest $request, Siswa $siswa, PembayaranService $pembayaranService): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $pembayaran = $pembayaranService->bayar($user, $siswa, $request->validated('id_tagihan'));
        } catch (QueryException) {
            return back()->withInput()->withErrors([
                'id_tagihan' => 'Pembayaran tidak dapat diproses karena data tagihan baru saja berubah. Muat ulang halaman dan coba lagi.',
            ]);
        }

        return to_route('pembayaran.kwitansi.show', $pembayaran)->with(
            'status',
            'Pembayaran berhasil dicatat.',
        );
    }

    public function showKwitansi(Pembayaran $pembayaran): View
    {
        $pembayaran->load([
            'siswa',
            'user',
            'detailPembayaran.tagihanSpp.siswaKelas.kelas.jurusan',
        ]);

        return view('pembayaran.kwitansi', [
            'pembayaran' => $pembayaran,
        ]);
    }
}
