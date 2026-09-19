<?php

use App\Http\Controllers\AkunSiswaController;
use App\Http\Controllers\ArsipKwitansiSiswaController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\SiswaAuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KenaikanKelasController;
use App\Http\Controllers\LaporanTunggakanController;
use App\Http\Controllers\MasterData\JurusanController;
use App\Http\Controllers\MasterData\KelasController;
use App\Http\Controllers\MasterData\SiswaController;
use App\Http\Controllers\MasterData\TahunAjaranController;
use App\Http\Controllers\MasterData\TarifSppController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\RekapPembayaranController;
use App\Http\Controllers\RiwayatPembayaranController;
use App\Http\Controllers\SiswaPortalController;
use App\Http\Controllers\StatusSppController;
use App\Http\Controllers\WaliPortalController;
use Illuminate\Support\Facades\Route;

Route::get('/wali', [WaliPortalController::class, 'index'])->name('wali.portal');

Route::middleware('guest:web')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.attempt');
});

Route::middleware('guest:siswa')->group(function () {
    Route::get('/siswa/login', [SiswaAuthenticatedSessionController::class, 'create'])->name('siswa.login');
    Route::post('/siswa/login', [SiswaAuthenticatedSessionController::class, 'store'])->name('siswa.login.attempt');
});

Route::middleware(['auth:web', 'role:petugas,web'])->group(function () {
    Route::get('/', DashboardController::class)->name('home');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/pembayaran', [PembayaranController::class, 'index'])->name('pembayaran.index');
    Route::get('/pembayaran/kwitansi/{pembayaran}', [PembayaranController::class, 'showKwitansi'])->name('pembayaran.kwitansi.show');
    Route::get('/pembayaran/{siswa}', [PembayaranController::class, 'show'])->name('pembayaran.show');
    Route::post('/pembayaran/{siswa}', [PembayaranController::class, 'store'])->name('pembayaran.store');
    Route::get('/riwayat-pembayaran', [RiwayatPembayaranController::class, 'index'])->name('riwayat-pembayaran.index');
    Route::get('/riwayat-pembayaran/{pembayaran}', [RiwayatPembayaranController::class, 'show'])->name('riwayat-pembayaran.show');
    Route::patch('/riwayat-pembayaran/{pembayaran}/batalkan', [RiwayatPembayaranController::class, 'batalkan'])->name('riwayat-pembayaran.batalkan');
    Route::get('/rekap-pembayaran', [RekapPembayaranController::class, 'index'])->name('rekap-pembayaran.index');
    Route::get('/rekap-pembayaran/export/excel', [RekapPembayaranController::class, 'exportExcel'])->name('rekap-pembayaran.export.excel');
    Route::get('/rekap-pembayaran/export/pdf', [RekapPembayaranController::class, 'exportPdf'])->name('rekap-pembayaran.export.pdf');
    Route::get('/laporan-tunggakan', [LaporanTunggakanController::class, 'index'])->name('laporan-tunggakan.index');
    Route::get('/laporan-tunggakan/export/excel', [LaporanTunggakanController::class, 'exportExcel'])->name('laporan-tunggakan.export.excel');
    Route::get('/laporan-tunggakan/export/pdf', [LaporanTunggakanController::class, 'exportPdf'])->name('laporan-tunggakan.export.pdf');
    Route::get('/arsip-kwitansi', [ArsipKwitansiSiswaController::class, 'index'])->name('arsip-kwitansi.index');
    Route::get('/arsip-kwitansi/{arsipKwitansi}', [ArsipKwitansiSiswaController::class, 'show'])->name('arsip-kwitansi.show');
    Route::get('/status-spp', [StatusSppController::class, 'index'])->name('status-spp.index');
    Route::get('/status-spp/{siswa}', [StatusSppController::class, 'show'])->withTrashed()->name('status-spp.show');
    Route::get('/kenaikan-kelas/preview', [KenaikanKelasController::class, 'preview'])->name('kenaikan-kelas.preview');
    Route::post('/kenaikan-kelas/proses', [KenaikanKelasController::class, 'proses'])->name('kenaikan-kelas.proses');

    Route::prefix('master-data')->name('master.')->group(function () {
        Route::get('/siswa', [SiswaController::class, 'index'])->name('siswa.index');
        Route::get('/siswa/create', [SiswaController::class, 'create'])->name('siswa.create');
        Route::post('/siswa', [SiswaController::class, 'store'])->name('siswa.store');
        Route::get('/siswa/import', [SiswaController::class, 'importForm'])->name('siswa.import.form');
        Route::post('/siswa/import', [SiswaController::class, 'import'])->name('siswa.import');
        Route::get('/siswa/nonaktif', [SiswaController::class, 'nonaktif'])->name('siswa.nonaktif');
        Route::patch('/siswa/{siswa}/nonaktifkan', [SiswaController::class, 'nonaktifkan'])->name('siswa.nonaktifkan');
        Route::get('/siswa/{siswa}/edit', [SiswaController::class, 'edit'])->name('siswa.edit');
        Route::put('/siswa/{siswa}', [SiswaController::class, 'update'])->name('siswa.update');
        Route::delete('/siswa/{siswa}', [SiswaController::class, 'destroy'])->name('siswa.destroy');
        Route::get('/siswa/{siswa}/akun', [AkunSiswaController::class, 'show'])->name('siswa.akun.show');
        Route::post('/siswa/{siswa}/akun', [AkunSiswaController::class, 'store'])->name('siswa.akun.store');
        Route::patch('/siswa/{siswa}/akun/password', [AkunSiswaController::class, 'resetPassword'])->name('siswa.akun.password');

        Route::get('/jurusan', [JurusanController::class, 'index'])->name('jurusan.index');
        Route::get('/jurusan/create', [JurusanController::class, 'create'])->name('jurusan.create');
        Route::post('/jurusan', [JurusanController::class, 'store'])->name('jurusan.store');
        Route::get('/jurusan/{jurusan}/edit', [JurusanController::class, 'edit'])->name('jurusan.edit');
        Route::put('/jurusan/{jurusan}', [JurusanController::class, 'update'])->name('jurusan.update');
        Route::delete('/jurusan/{jurusan}', [JurusanController::class, 'destroy'])->name('jurusan.destroy');

        Route::get('/kelas', [KelasController::class, 'index'])->name('kelas.index');
        Route::get('/kelas/create', [KelasController::class, 'create'])->name('kelas.create');
        Route::post('/kelas', [KelasController::class, 'store'])->name('kelas.store');
        Route::delete('/kelas/{kelas}', [KelasController::class, 'destroy'])->name('kelas.destroy');

        Route::get('/tahun-ajaran', [TahunAjaranController::class, 'index'])->name('tahun-ajaran.index');
        Route::post('/tahun-ajaran', [TahunAjaranController::class, 'store'])->name('tahun-ajaran.store');
        Route::get('/tahun-ajaran/{tahunAjaran}/edit', [TahunAjaranController::class, 'edit'])->name('tahun-ajaran.edit');
        Route::put('/tahun-ajaran/{tahunAjaran}', [TahunAjaranController::class, 'update'])->name('tahun-ajaran.update');
        Route::patch('/tahun-ajaran/{tahunAjaran}/activate', [TahunAjaranController::class, 'activate'])->name('tahun-ajaran.activate');
        Route::patch('/tahun-ajaran/{tahunAjaran}/deactivate', [TahunAjaranController::class, 'deactivate'])->name('tahun-ajaran.deactivate');
        Route::delete('/tahun-ajaran/{tahunAjaran}', [TahunAjaranController::class, 'destroy'])->name('tahun-ajaran.destroy');

        Route::get('/tarif-spp', [TarifSppController::class, 'index'])->name('tarif-spp.index');
        Route::get('/tarif-spp/create', [TarifSppController::class, 'create'])->name('tarif-spp.create');
        Route::post('/tarif-spp', [TarifSppController::class, 'store'])->name('tarif-spp.store');
        Route::get('/tarif-spp/{tarifSpp}/edit', [TarifSppController::class, 'edit'])->name('tarif-spp.edit');
        Route::put('/tarif-spp/{tarifSpp}', [TarifSppController::class, 'update'])->name('tarif-spp.update');
    });
});

Route::middleware(['auth:siswa', 'role:siswa,siswa'])->prefix('siswa')->name('siswa.')->group(function () {
    Route::get('/status-spp', [SiswaPortalController::class, 'status'])->name('status');
    Route::post('/kwitansi', [SiswaPortalController::class, 'simpanFoto'])->name('kwitansi.store');
    Route::patch('/kwitansi', [SiswaPortalController::class, 'gantiFoto'])->name('kwitansi.update');
    Route::post('/logout', [SiswaAuthenticatedSessionController::class, 'destroy'])->name('logout');
});
