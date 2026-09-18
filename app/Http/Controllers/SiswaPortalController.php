<?php

namespace App\Http\Controllers;

use App\Http\Requests\FotoKwitansiRequest;
use App\Models\ArsipKwitansiSiswa;
use App\Models\Siswa;
use App\Services\KompresiFotoKwitansiService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SiswaPortalController extends Controller
{
    public function status(Request $request): View
    {
        /** @var Siswa $siswa */
        $siswa = $request->user('siswa')->siswa;
        $kelasAktif = $siswa->siswaKelas()
            ->with('kelas')
            ->whereHas('tahunAjaran', fn (Builder $query) => $query->where('aktif', true))
            ->first();

        return view('portal-siswa.status', [
            'siswa' => $siswa,
            'kelasAktif' => $kelasAktif,
            'tagihanSpp' => $siswa->tagihanSpp()
                ->with([
                    'detailPembayaran' => fn ($query) => $query
                        ->whereHas('pembayaran', fn (Builder $query) => $query->where('status', 'aktif'))
                        ->with('pembayaran'),
                ])
                ->orderBy('tahun')
                ->orderBy('bulan')
                ->get(),
            'pembayaranAktif' => $siswa->pembayaran()
                ->where('status', 'aktif')
                ->with('detailPembayaran.tagihanSpp')
                ->orderByDesc('tanggal_bayar')
                ->get(),
        ]);
    }

    public function simpanFoto(
        FotoKwitansiRequest $request,
        KompresiFotoKwitansiService $kompresiFotoKwitansiService,
    ): RedirectResponse {
        /** @var Siswa $siswa */
        $siswa = $request->user('siswa')->siswa;
        $idPembayaran = $request->validated('id_pembayaran');
        $pembayaran = null;

        if ($idPembayaran) {
            $pembayaran = $siswa->pembayaran()
                ->whereKey($idPembayaran)
                ->where('status', 'aktif')
                ->first();

            if (! $pembayaran) {
                throw ValidationException::withMessages([
                    'id_pembayaran' => 'Transaksi pembayaran yang dipilih tidak ditemukan.',
                ]);
            }
        }

        $dataFoto = $kompresiFotoKwitansiService->simpan($request->file('foto'), $siswa);

        try {
            ArsipKwitansiSiswa::create([
                'id_siswa' => $siswa->id_siswa,
                'id_pembayaran' => $pembayaran?->id_pembayaran,
                ...$dataFoto,
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($dataFoto['path']);

            throw $exception;
        }

        return to_route('siswa.status')->with('status', 'Foto kwitansi berhasil disimpan sebagai arsip.');
    }
}
