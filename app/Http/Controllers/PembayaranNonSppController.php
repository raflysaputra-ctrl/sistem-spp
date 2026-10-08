<?php

namespace App\Http\Controllers;

use App\Http\Requests\PembayaranNonSppRequest;
use App\Models\JenisPembayaran;
use App\Models\PembayaranNonSpp;
use App\Models\Siswa;
use App\Models\TagihanPembayaran;
use App\Models\User;
use App\Services\PembayaranNonSppService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PembayaranNonSppController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'id_jenis_pembayaran' => ['nullable', 'integer', 'exists:jenis_pembayaran,id_jenis_pembayaran'],
            'cari' => ['nullable', 'string', 'max:100'],
        ]);

        $query = Siswa::query()
            ->with(['siswaKelas.kelas.jurusan', 'tagihanNonSpp' => function ($q) use ($filters) {
                $q->when($filters['id_jenis_pembayaran'] ?? null, fn ($sq) => $sq->where('id_jenis_pembayaran', $filters['id_jenis_pembayaran']))
                    ->whereNotIn('status', ['lunas', 'tidak_berlaku'])
                    ->with('jenisPembayaran');
            }])
            ->when($filters['cari'] ?? null, function (Builder $q, string $cari) {
                $q->where(function (Builder $q) use ($cari) {
                    $q->where('nipd', 'like', "%{$cari}%")
                        ->orWhere('nama_siswa', 'like', "%{$cari}%");
                });
            })
            ->whereHas('tagihanNonSpp', function (Builder $q) use ($filters) {
                $q->when($filters['id_jenis_pembayaran'] ?? null, fn ($sq) => $sq->where('id_jenis_pembayaran', $filters['id_jenis_pembayaran']))
                    ->whereNotIn('status', ['lunas', 'tidak_berlaku']);
            })
            ->where('status_siswa', 'aktif')
            ->orderBy('nama_siswa')
            ->paginate(20)
            ->withQueryString();

        return view('pembayaran-non-spp.index', [
            'siswa' => $query,
            'jenisPembayaran' => JenisPembayaran::where('aktif', true)->orderBy('nama_jenis')->get(),
            'filters' => $filters,
        ]);
    }

    public function show(Siswa $siswa): View
    {
        $tagihanBelumLunas = TagihanPembayaran::query()
            ->with(['jenisPembayaran', 'tahunAjaran'])
            ->withSum([
                'detailPembayaranNonSpp as total_dibayar_aktif' => fn (Builder $query) => $query
                    ->whereHas('pembayaranNonSpp', fn (Builder $pembayaran) => $pembayaran->where('status', 'aktif')),
            ], 'nominal_bayar')
            ->where('id_siswa', $siswa->id_siswa)
            ->whereNotIn('status', ['lunas', 'tidak_berlaku'])
            ->orderBy('id_jenis_pembayaran')
            ->orderBy('kode_periode')
            ->orderByDesc('id_tagihan_pembayaran')
            ->get()
            ->map(function (TagihanPembayaran $tagihan): array {
                $totalDibayar = (int) ($tagihan->total_dibayar_aktif ?? 0);
                $sisaTagihan = max(0, (int) $tagihan->total_tagihan - $totalDibayar);

                return [
                    'tagihan' => $tagihan,
                    'total_dibayar' => $totalDibayar,
                    'sisa_tagihan' => $sisaTagihan,
                    'belum_ada_pembayaran' => $totalDibayar === 0 || $totalDibayar === (int) $totalDibayar && $totalDibayar == 0,
                ];
            });

        return view('pembayaran-non-spp.show', [
            'siswa' => $siswa,
            'tagihanBelumLunas' => $tagihanBelumLunas,
        ]);
    }

    public function store(
        PembayaranNonSppRequest $request,
        Siswa $siswa,
        PembayaranNonSppService $pembayaranNonSppService,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        try {
            $pembayaran = $pembayaranNonSppService->bayar(
                $user,
                $siswa,
                $request->selectedItems(),
                $request->validated('kode_periode_bam'),
            );
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        } catch (QueryException) {
            return back()->withInput()->withErrors([
                'items' => 'Pembayaran tidak dapat diproses karena data tagihan baru saja berubah. Muat ulang halaman dan coba lagi.',
            ]);
        }

        return to_route('pembayaran-non-spp.kwitansi', $pembayaran)->with(
            'status',
            'Pembayaran non-SPP berhasil dicatat dalam satu kwitansi.'
        );
    }

    public function kwitansi(PembayaranNonSpp $pembayaranNonSpp): View
    {
        $pembayaranNonSpp->load([
            'siswa',
            'user',
            'detailPembayaranNonSpp.tagihanPembayaran.jenisPembayaran',
            'detailPembayaranNonSpp.tagihanPembayaran.tahunAjaran',
        ]);

        return view('pembayaran-non-spp.kwitansi', [
            'pembayaran' => $pembayaranNonSpp,
        ]);
    }
}
