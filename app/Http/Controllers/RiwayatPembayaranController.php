<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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
            'detailPembayaran.tagihanSpp.siswaKelas.kelas',
        ]);

        return view('riwayat-pembayaran.show', [
            'pembayaran' => $pembayaran,
        ]);
    }
}
