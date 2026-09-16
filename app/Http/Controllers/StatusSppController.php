<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatusSppController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
        ]);
        $tahunAjaranAktif = fn ($query) => $query->where('aktif', true);

        return view('status-spp.index', [
            'filters' => $filters,
            'siswa' => Siswa::query()
                ->with(['siswaKelas' => function ($query) use ($tahunAjaranAktif) {
                    $query->whereHas('tahunAjaran', $tahunAjaranAktif)->with('kelas.jurusan');
                }])
                ->withCount([
                    'tagihanSpp as tagihan_belum_bayar_count' => fn ($query) => $query->where('status', 'belum_bayar'),
                    'tagihanSpp as tagihan_lunas_count' => fn ($query) => $query->where('status', 'lunas'),
                ])
                ->when($filters['cari'] ?? null, function ($query, $cari) {
                    $query->where(function ($query) use ($cari) {
                        $query->where('nipd', 'like', "%{$cari}%")
                            ->orWhere('nama_siswa', 'like', "%{$cari}%");
                    });
                })
                ->orderBy('nama_siswa')
                ->get(),
        ]);
    }

    public function show(Siswa $siswa): View
    {
        $kelasAktif = $siswa->siswaKelas()
            ->with(['kelas.jurusan', 'tahunAjaran'])
            ->whereHas('tahunAjaran', fn ($query) => $query->where('aktif', true))
            ->first();

        return view('status-spp.show', [
            'siswa' => $siswa,
            'kelasAktif' => $kelasAktif,
            'tagihanSpp' => $siswa->tagihanSpp()
                ->with(['siswaKelas.kelas.jurusan', 'siswaKelas.tahunAjaran'])
                ->orderBy('tahun')
                ->orderBy('bulan')
                ->get(),
        ]);
    }
}
