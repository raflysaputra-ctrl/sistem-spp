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
                        'pembayaran.penerimaan.arsipKwitansi',
                        'pembayaran.detailPembayaran.tagihanSpp',
                    ]),
            ])
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get();

        $penerimaanNonSppSaja = $siswa->penerimaan()
            ->with(['arsipKwitansi', 'pembayaranNonSpp.detailPembayaranNonSpp.tagihanPembayaran.jenisPembayaran'])
            ->where('status', 'aktif')
            ->whereDoesntHave('pembayaranSpp')
            ->whereHas('pembayaranNonSpp')
            ->orderByDesc('tanggal_bayar')
            ->get();

        return view('portal-siswa.status', [
            'siswa' => $siswa,
            'kelasAktif' => $kelasAktif,
            'tagihanSpp' => $tagihanSpp,
            'penerimaanNonSppSaja' => $penerimaanNonSppSaja,
            'adaTransaksiDapatDiunggah' => $tagihanSpp->contains(function ($tagihan): bool {
                $pembayaran = $tagihan->detailPembayaran->first()?->pembayaran;
                $arsip = $pembayaran?->penerimaan?->arsipKwitansi ?? $pembayaran?->arsipKwitansi;

                return $pembayaran && ! $arsip;
            }) || $penerimaanNonSppSaja->contains(fn ($penerimaan) => ! $penerimaan->arsipKwitansi),
        ]);
    }

    public function simpanFoto(
        FotoKwitansiRequest $request,
        KompresiFotoKwitansiService $kompresiFotoKwitansiService,
    ): RedirectResponse {
        /** @var Siswa $siswa */
        $siswa = $request->user('siswa')->siswa;
        $idPembayaran = $request->validated('id_pembayaran');
        $idPenerimaan = $request->validated('id_penerimaan');
        $dataFoto = null;

        try {
            DB::transaction(function () use (
                $siswa,
                $idPembayaran,
                $idPenerimaan,
                $request,
                $kompresiFotoKwitansiService,
                &$dataFoto,
            ): void {
                $pembayaran = null;
                $penerimaan = null;

                if ($idPenerimaan) {
                    $penerimaan = $siswa->penerimaan()
                        ->whereKey($idPenerimaan)
                        ->where('status', 'aktif')
                        ->lockForUpdate()
                        ->first();
                } else {
                    $pembayaran = $siswa->pembayaran()
                        ->whereKey($idPembayaran)
                        ->where('status', 'aktif')
                        ->lockForUpdate()
                        ->first();
                }

                if (! $pembayaran && ! $penerimaan) {
                    throw ValidationException::withMessages([
                        'id_pembayaran' => 'Kwitansi aktif tidak ditemukan untuk siswa ini.',
                    ]);
                }

                $relasiArsip = $penerimaan?->arsipKwitansi() ?? $pembayaran->arsipKwitansi();
                if ($relasiArsip->exists()) {
                    throw ValidationException::withMessages([
                        'id_pembayaran' => 'Foto kwitansi untuk transaksi ini sudah diunggah.',
                    ]);
                }

                $dataFoto = $kompresiFotoKwitansiService->simpan($request->file('foto'), $siswa);

                ArsipKwitansiSiswa::create([
                    'id_siswa' => $siswa->id_siswa,
                    'id_pembayaran' => $pembayaran?->id_pembayaran,
                    'id_penerimaan' => $penerimaan?->id_penerimaan,
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
        $idPenerimaan = $request->validated('id_penerimaan');
        $dataFoto = null;
        $pathLama = null;

        try {
            DB::transaction(function () use (
                $siswa,
                $idPembayaran,
                $idPenerimaan,
                $request,
                $kompresiFotoKwitansiService,
                &$dataFoto,
                &$pathLama,
            ): void {
                $pembayaran = null;
                $penerimaan = null;

                if ($idPenerimaan) {
                    $penerimaan = $siswa->penerimaan()
                        ->whereKey($idPenerimaan)
                        ->where('status', 'aktif')
                        ->lockForUpdate()
                        ->first();
                } else {
                    $pembayaran = $siswa->pembayaran()
                        ->whereKey($idPembayaran)
                        ->where('status', 'aktif')
                        ->lockForUpdate()
                        ->first();
                }

                if (! $pembayaran && ! $penerimaan) {
                    throw ValidationException::withMessages([
                        'id_pembayaran' => 'Transaksi pembayaran aktif tidak ditemukan untuk siswa ini.',
                    ]);
                }

                $arsip = ($penerimaan?->arsipKwitansi() ?? $pembayaran->arsipKwitansi())
                    ->lockForUpdate()
                    ->first();

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
