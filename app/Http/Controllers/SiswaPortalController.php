<?php

namespace App\Http\Controllers;

use App\Http\Requests\FotoKwitansiRequest;
use App\Models\ArsipKwitansiSiswa;
use App\Models\Siswa;
use App\Services\KompresiFotoKwitansiService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $tagihanSpp = $siswa->tagihanSpp()
            ->with([
                'detailPembayaran' => fn ($query) => $query
                    ->whereHas('pembayaran', fn (Builder $query) => $query->where('status', 'aktif'))
                    ->with([
                        'pembayaran.arsipKwitansi',
                        'pembayaran.detailPembayaran.tagihanSpp',
                    ]),
            ])
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get();

        return view('portal-siswa.status', [
            'siswa' => $siswa,
            'kelasAktif' => $kelasAktif,
            'tagihanSpp' => $tagihanSpp,
            'adaTransaksiDapatDiunggah' => $tagihanSpp->contains(
                fn ($tagihan) => ($pembayaran = $tagihan->detailPembayaran->first()?->pembayaran)
                    && ! $pembayaran->arsipKwitansi,
            ),
        ]);
    }

    public function simpanFoto(
        FotoKwitansiRequest $request,
        KompresiFotoKwitansiService $kompresiFotoKwitansiService,
    ): RedirectResponse {
        /** @var Siswa $siswa */
        $siswa = $request->user('siswa')->siswa;
        $idPembayaran = $request->validated('id_pembayaran');
        $dataFoto = null;

        try {
            DB::transaction(function () use (
                $siswa,
                $idPembayaran,
                $request,
                $kompresiFotoKwitansiService,
                &$dataFoto,
            ): void {
                $pembayaran = $siswa->pembayaran()
                    ->whereKey($idPembayaran)
                    ->where('status', 'aktif')
                    ->lockForUpdate()
                    ->first();

                if (! $pembayaran) {
                    throw ValidationException::withMessages([
                        'id_pembayaran' => 'Transaksi pembayaran aktif tidak ditemukan untuk siswa ini.',
                    ]);
                }

                if ($pembayaran->arsipKwitansi()->exists()) {
                    throw ValidationException::withMessages([
                        'id_pembayaran' => 'Foto kwitansi untuk transaksi ini sudah diunggah.',
                    ]);
                }

                $dataFoto = $kompresiFotoKwitansiService->simpan($request->file('foto'), $siswa);

                ArsipKwitansiSiswa::create([
                    'id_siswa' => $siswa->id_siswa,
                    'id_pembayaran' => $pembayaran->id_pembayaran,
                    ...$dataFoto,
                ]);
            });
        } catch (\Throwable $exception) {
            if ($dataFoto) {
                Storage::disk('local')->delete($dataFoto['path']);
            }

            throw $exception;
        }

        return to_route('siswa.status')->with('status', 'Foto kwitansi berhasil disimpan sebagai arsip.');
    }

    public function gantiFoto(
        FotoKwitansiRequest $request,
        KompresiFotoKwitansiService $kompresiFotoKwitansiService,
    ): RedirectResponse {
        /** @var Siswa $siswa */
        $siswa = $request->user('siswa')->siswa;
        $idPembayaran = $request->validated('id_pembayaran');
        $dataFoto = null;
        $pathLama = null;

        try {
            DB::transaction(function () use (
                $siswa,
                $idPembayaran,
                $request,
                $kompresiFotoKwitansiService,
                &$dataFoto,
                &$pathLama,
            ): void {
                $pembayaran = $siswa->pembayaran()
                    ->whereKey($idPembayaran)
                    ->where('status', 'aktif')
                    ->lockForUpdate()
                    ->first();

                if (! $pembayaran) {
                    throw ValidationException::withMessages([
                        'id_pembayaran' => 'Transaksi pembayaran aktif tidak ditemukan untuk siswa ini.',
                    ]);
                }

                $arsip = $pembayaran->arsipKwitansi()->lockForUpdate()->first();

                if (! $arsip) {
                    throw ValidationException::withMessages([
                        'id_pembayaran' => 'Foto kwitansi untuk transaksi ini belum pernah diunggah.',
                    ]);
                }

                $dataFoto = $kompresiFotoKwitansiService->simpan($request->file('foto'), $siswa);
                $pathLama = $arsip->path;
                $arsip->update($dataFoto);
            });
        } catch (\Throwable $exception) {
            if ($dataFoto) {
                Storage::disk('local')->delete($dataFoto['path']);
            }

            throw $exception;
        }

        Storage::disk('local')->delete($pathLama);

        return to_route('siswa.status')->with('status', 'Foto kwitansi berhasil diganti.');
    }
}
