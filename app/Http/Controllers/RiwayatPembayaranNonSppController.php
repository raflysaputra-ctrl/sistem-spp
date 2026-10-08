<?php

namespace App\Http\Controllers;

use App\Http\Requests\PembatalanPembayaranRequest;
use App\Models\PembayaranNonSpp;
use App\Models\User;
use App\Services\PembatalanPembayaranNonSppService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RiwayatPembayaranNonSppController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'status' => ['nullable', 'in:aktif,dibatalkan,semua'],
        ]);
        $filters['status'] = $filters['status'] ?? 'aktif';

        return view('riwayat-pembayaran-non-spp.index', [
            'riwayatPembayaran' => PembayaranNonSpp::query()
                ->with(['siswa', 'user', 'detailPembayaranNonSpp.tagihanPembayaran.jenisPembayaran'])
                ->when($filters['cari'] ?? null, function (Builder $query, string $cari) {
                    $query->where(function (Builder $query) use ($cari) {
                        $query->where('no_kwitansi', 'like', "%{$cari}%")
                            ->orWhereHas('siswa', function (Builder $siswa) use ($cari) {
                                $siswa->withTrashed()
                                    ->where('nipd', 'like', "%{$cari}%")
                                    ->orWhere('nama_siswa', 'like', "%{$cari}%");
                            });
                    });
                })
                ->when($filters['tanggal_mulai'] ?? null, fn (Builder $query, string $tanggal) => $query->whereDate('tanggal_bayar', '>=', $tanggal))
                ->when($filters['tanggal_selesai'] ?? null, fn (Builder $query, string $tanggal) => $query->whereDate('tanggal_bayar', '<=', $tanggal))
                ->when($filters['status'] !== 'semua', fn (Builder $query) => $query->where('status', $filters['status']))
                ->orderByDesc('tanggal_bayar')
                ->paginate(15)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function show(PembayaranNonSpp $pembayaranNonSpp): View
    {
        $pembayaranNonSpp->load([
            'siswa',
            'user',
            'dibatalkanOleh',
            'detailPembayaranNonSpp.tagihanPembayaran.jenisPembayaran',
            'detailPembayaranNonSpp.tagihanPembayaran.tahunAjaran',
        ]);

        return view('riwayat-pembayaran-non-spp.show', [
            'pembayaran' => $pembayaranNonSpp,
        ]);
    }

    public function batalkan(
        PembatalanPembayaranRequest $request,
        PembayaranNonSpp $pembayaranNonSpp,
        PembatalanPembayaranNonSppService $pembatalanPembayaranNonSppService,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        try {
            $pembatalanPembayaranNonSppService->batalkan(
                $user,
                $pembayaranNonSpp,
                $request->validated('alasan_pembatalan'),
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        } catch (QueryException) {
            return back()->withErrors([
                'pembayaran' => 'Pembatalan tidak dapat diproses karena data transaksi baru saja berubah. Muat ulang halaman dan coba lagi.',
            ]);
        }

        return to_route('riwayat-pembayaran-non-spp.show', $pembayaranNonSpp)->with(
            'status',
            'Transaksi penerimaan non-SPP berhasil dibatalkan.',
        );
    }
}
