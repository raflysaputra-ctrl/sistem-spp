<?php

namespace App\Http\Controllers;

use App\Http\Requests\PembatalanPembayaranRequest;
use App\Http\Requests\PenerimaanRequest;
use App\Models\JenisPembayaran;
use App\Models\Jurusan;
use App\Models\Penerimaan;
use App\Models\Siswa;
use App\Models\TagihanPembayaran;
use App\Models\User;
use App\Services\PembatalanPenerimaanService;
use App\Services\PenerimaanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PenerimaanController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'id_jurusan' => ['nullable', 'integer', 'exists:jurusan,id_jurusan'],
            'tingkat' => ['nullable', 'integer', 'in:1,2,3'],
        ]);

        $tahunAjaranAktif = fn (Builder $query) => $query->where('aktif', true);

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
            ->when($filters['id_jurusan'] ?? null, fn (Builder $query, int $id) => $query
                ->whereHas('siswaKelas', fn (Builder $kelas) => $kelas
                    ->whereHas('tahunAjaran', $tahunAjaranAktif)
                    ->whereHas('kelas', fn (Builder $q) => $q->where('id_jurusan', $id))))
            ->when($filters['tingkat'] ?? null, fn (Builder $query, int $tingkat) => $query
                ->whereHas('siswaKelas', fn (Builder $kelas) => $kelas
                    ->whereHas('tahunAjaran', $tahunAjaranAktif)
                    ->whereHas('kelas', fn (Builder $q) => $q->where('tingkat', $tingkat))))
            ->where('status_siswa', 'aktif')
            ->where(function (Builder $query) {
                $query->whereHas('tagihanSpp', fn (Builder $q) => $q->where('status', 'belum_bayar'))
                    ->orWhereHas('tagihanNonSpp', fn (Builder $q) => $q->whereNotIn('status', ['lunas', 'tidak_berlaku']));
            })
            ->orderBy('nama_siswa')
            ->paginate(15)
            ->withQueryString();

        return view('penerimaan.index', [
            'siswa' => $siswa,
            'filters' => $filters,
            'jurusan' => Jurusan::query()->orderBy('nama_jurusan')->get(),
        ]);
    }

    public function show(Siswa $siswa): View
    {
        $tagihanNonSpp = TagihanPembayaran::query()
            ->with(['jenisPembayaran', 'tahunAjaran'])
            ->withSum([
                'detailPembayaranNonSpp as total_dibayar_aktif' => fn (Builder $query) => $query
                    ->whereHas('pembayaranNonSpp', fn (Builder $pembayaran) => $pembayaran->where('status', 'aktif')),
            ], 'nominal_bayar')
            ->where('id_siswa', $siswa->id_siswa)
            ->whereNotIn('status', ['lunas', 'tidak_berlaku'])
            ->orderBy('id_jenis_pembayaran')
            ->orderBy('kode_periode')
            ->get()
            ->map(function (TagihanPembayaran $tagihan): array {
                $totalDibayar = (int) ($tagihan->total_dibayar_aktif ?? 0);

                return [
                    'tagihan' => $tagihan,
                    'total_dibayar' => $totalDibayar,
                    'sisa_tagihan' => max(0, (int) $tagihan->total_tagihan - $totalDibayar),
                ];
            });

        return view('penerimaan.show', [
            'siswa' => $siswa,
            'kelasAktif' => $siswa->siswaKelas()
                ->with(['kelas.jurusan', 'tahunAjaran'])
                ->whereHas('tahunAjaran', fn ($query) => $query->where('aktif', true))
                ->first(),
            'tagihanSpp' => $siswa->tagihanSpp()
                ->with(['siswaKelas.kelas', 'siswaKelas.tahunAjaran'])
                ->orderBy('tahun')
                ->orderBy('bulan')
                ->get(),
            'tagihanNonSpp' => $tagihanNonSpp,
            'jenisPembayaran' => JenisPembayaran::query()
                ->where('aktif', true)
                ->whereHas('tagihanPembayaran', fn (Builder $query) => $query
                    ->where('id_siswa', $siswa->id_siswa)
                    ->whereNotIn('status', ['lunas', 'tidak_berlaku']))
                ->orderBy('nama_jenis')
                ->get(),
        ]);
    }

    public function store(
        PenerimaanRequest $request,
        Siswa $siswa,
        PenerimaanService $penerimaanService,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        try {
            $penerimaan = $penerimaanService->bayar(
                $user,
                $siswa,
                $request->validated('id_tagihan_spp', []),
                $request->selectedNonSppItems(),
                $request->validated('kode_periode_bam'),
            );
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        } catch (QueryException) {
            return back()->withInput()->withErrors([
                'penerimaan' => 'Penerimaan tidak dapat diproses karena data tagihan baru saja berubah. Muat ulang halaman dan coba lagi.',
            ]);
        }

        return to_route('penerimaan.kwitansi', $penerimaan)
            ->with('status', 'Pembayaran berhasil dicatat dalam satu kwitansi.');
    }

    public function kwitansi(Penerimaan $penerimaan): View
    {
        $this->loadPenerimaan($penerimaan);

        return view('penerimaan.kwitansi', ['penerimaan' => $penerimaan]);
    }

    public function riwayat(Request $request): View
    {
        $filters = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:aktif,dibatalkan,semua'],
        ]);
        $filters['status'] = $filters['status'] ?? 'aktif';

        $penerimaan = Penerimaan::query()
            ->with(['siswa', 'user', 'pembayaranSpp.detailPembayaran', 'pembayaranNonSpp.detailPembayaranNonSpp'])
            ->when($filters['cari'] ?? null, function (Builder $query, string $cari) {
                $query->where(function (Builder $query) use ($cari) {
                    $query->where('no_kwitansi', 'like', "%{$cari}%")
                        ->orWhereHas('siswa', fn (Builder $siswa) => $siswa->withTrashed()
                            ->where('nipd', 'like', "%{$cari}%")
                            ->orWhere('nama_siswa', 'like', "%{$cari}%"));
                });
            })
            ->when($filters['status'] !== 'semua', fn (Builder $query) => $query->where('status', $filters['status']))
            ->orderByDesc('tanggal_bayar')
            ->paginate(15)
            ->withQueryString();

        return view('penerimaan.riwayat', compact('penerimaan', 'filters'));
    }

    public function detail(Penerimaan $penerimaan): View
    {
        $this->loadPenerimaan($penerimaan);

        return view('penerimaan.detail', ['penerimaan' => $penerimaan]);
    }

    public function batalkan(
        PembatalanPembayaranRequest $request,
        Penerimaan $penerimaan,
        PembatalanPenerimaanService $pembatalanPenerimaanService,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        try {
            $pembatalanPenerimaanService->batalkan(
                $user,
                $penerimaan,
                $request->validated('alasan_pembatalan'),
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        } catch (QueryException) {
            return back()->withErrors([
                'penerimaan' => 'Pembatalan tidak dapat diproses karena data transaksi baru saja berubah.',
            ]);
        }

        return to_route('penerimaan.detail', $penerimaan)
            ->with('status', 'Seluruh isi kwitansi pembayaran berhasil dibatalkan.');
    }

    private function loadPenerimaan(Penerimaan $penerimaan): void
    {
        $penerimaan->load([
            'siswa',
            'user',
            'dibatalkanOleh',
            'arsipKwitansi',
            'pembayaranSpp.detailPembayaran.tagihanSpp.siswaKelas.kelas',
            'pembayaranNonSpp.detailPembayaranNonSpp.tagihanPembayaran.jenisPembayaran',
            'pembayaranNonSpp.detailPembayaranNonSpp.tagihanPembayaran.tahunAjaran',
        ]);
    }
}
