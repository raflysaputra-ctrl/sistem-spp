<?php

namespace App\Http\Controllers;

use App\Models\ArsipKwitansiSiswa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ArsipKwitansiSiswaController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
        ]);

        return view('arsip-kwitansi.index', [
            'filters' => $filters,
            'arsipKwitansi' => ArsipKwitansiSiswa::query()
                ->with(['siswa', 'pembayaran.detailPembayaran.tagihanSpp'])
                ->when($filters['cari'] ?? null, function (Builder $query, string $cari) {
                    $query->whereHas('siswa', function (Builder $query) use ($cari) {
                        $query->withTrashed()
                            ->where('nipd', 'like', "%{$cari}%")
                            ->orWhere('nama_siswa', 'like', "%{$cari}%");
                    });
                })
                ->orderByDesc('created_at')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function show(ArsipKwitansiSiswa $arsipKwitansi)
    {
        abort_unless(Storage::disk('local')->exists($arsipKwitansi->path), 404);

        return Storage::disk('local')->response($arsipKwitansi->path);
    }
}
