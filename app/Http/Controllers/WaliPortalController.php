<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WaliPortalController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'nipd' => ['nullable', 'string', 'max:30'],
        ]);
        $nipd = trim($filters['nipd'] ?? '');
        $siswa = $nipd === '' ? null : Siswa::query()->where('nipd', $nipd)->first();
        $tagihanSpp = collect();
        $kelasAktif = null;

        if ($siswa) {
            $kelasAktif = $siswa->siswaKelas()
                ->with('kelas')
                ->whereHas('tahunAjaran', fn (Builder $query) => $query->where('aktif', true))
                ->first();
            $tagihanSpp = $siswa->tagihanSpp()
                ->with([
                    'detailPembayaran' => fn ($query) => $query
                        ->whereHas('pembayaran', fn (Builder $query) => $query->where('status', 'aktif'))
                        ->with('pembayaran'),
                ])
                ->orderBy('tahun')
                ->orderBy('bulan')
                ->get();
        }

        return view('portal-wali.index', [
            'nipd' => $nipd,
            'siswa' => $siswa,
            'kelasAktif' => $kelasAktif,
            'tagihanSpp' => $tagihanSpp,
        ]);
    }
}
