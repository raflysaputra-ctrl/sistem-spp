<?php

namespace App\Http\Controllers;

use App\Http\Requests\PembatalanPembayaranRequest;
use App\Models\Pembayaran;
use App\Models\User;
use App\Services\PembatalanPembayaranService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RiwayatPembayaranController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        return view('riwayat-pembayaran.index', [
            'riwayatPembayaran' => Pembayaran::query()
                ->with(['siswa', 'user', 'detailPembayaran.tagihanSpp'])
                ->when($filters['cari'] ?? null, function (Builder $query, string $cari) {
                    $query->where(function (Builder $query) use ($cari) {
                        $query->where('no_kwitansi', 'like', "%{$cari}%")
                            ->orWhereHas('siswa', function (Builder $query) use ($cari) {
                                $query->withTrashed()
                                    ->where('nipd', 'like', "%{$cari}%")
                                    ->orWhere('nama_siswa', 'like', "%{$cari}%");
                            });
                    });
                })
                ->when($filters['tanggal_mulai'] ?? null, fn (Builder $query, string $tanggal) => $query->whereDate('tanggal_bayar', '>=', $tanggal))
                ->when($filters['tanggal_selesai'] ?? null, fn (Builder $query, string $tanggal) => $query->whereDate('tanggal_bayar', '<=', $tanggal))
                ->orderByDesc('tanggal_bayar')
                ->paginate(15)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function show(Pembayaran $pembayaran): View
    {
        $pembayaran->load([
            'siswa',
            'user',
            'dibatalkanOleh',
            'detailPembayaran.tagihanSpp.siswaKelas.kelas',
            'arsipKwitansi',
        ]);

        return view('riwayat-pembayaran.show', [
            'pembayaran' => $pembayaran,
        ]);
    }

    public function batalkan(
        PembatalanPembayaranRequest $request,
        Pembayaran $pembayaran,
        PembatalanPembayaranService $pembatalanPembayaranService,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        try {
            $pembatalanPembayaranService->batalkan(
                $user,
                $pembayaran,
                $request->validated('alasan_pembatalan'),
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        } catch (QueryException) {
            return back()->withErrors([
                'pembayaran' => 'Pembatalan tidak dapat diproses karena data transaksi baru saja berubah. Muat ulang halaman dan coba lagi.',
            ]);
        }

        return to_route('riwayat-pembayaran.show', $pembayaran)->with(
            'status',
            'Transaksi pembayaran berhasil dibatalkan.',
        );
    }
}
