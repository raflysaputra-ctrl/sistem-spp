<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ArsipKwitansiSiswaController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\SiswaAuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KenaikanKelasController;
use App\Http\Controllers\LaporanTunggakanController;
use App\Http\Controllers\MasterData\JenisPembayaranController;
use App\Http\Controllers\MasterData\JurusanController;
use App\Http\Controllers\MasterData\KategoriPengeluaranController;
use App\Http\Controllers\MasterData\KelasController;
use App\Http\Controllers\MasterData\SiswaController;
use App\Http\Controllers\MasterData\TagihanNonSppBatchController;
use App\Http\Controllers\MasterData\TahunAjaranController;
use App\Http\Controllers\MasterData\TarifSppController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\PembayaranNonSppController;
use App\Http\Controllers\PenerimaanController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\RekapPembayaranController;
use App\Http\Controllers\RiwayatPembayaranController;
use App\Http\Controllers\RiwayatPembayaranNonSppController;
use App\Http\Controllers\SiswaAccountController;
use App\Http\Controllers\SiswaPortalController;
use App\Http\Controllers\StatusSppController;
use App\Http\Controllers\WaliPortalController;
use Illuminate\Support\Facades\Route;

Route::get('/wali', [WaliPortalController::class, 'index'])->name('wali.portal');

Route::get('/', function () {
    if (auth()->check()) {
        return match (auth()->user()->role) {
            'admin' => redirect('/admin/dashboard'),
            'tu' => redirect('/tu/dashboard'),
            'kepala_sekolah' => redirect('/kepsek/dashboard'),
            'siswa' => redirect('/siswa/status-spp'),
            default => redirect('/login'),
        };
    }

    return redirect('/login');
})->name('home');

Route::middleware('cache.headers:no_store;no_cache;must_revalidate;max_age=0')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.attempt');
});

Route::middleware('guest:siswa')->group(function () {
    Route::get('/siswa/login', [SiswaAuthenticatedSessionController::class, 'create'])->name('siswa.login');
    Route::post('/siswa/login', [SiswaAuthenticatedSessionController::class, 'store'])->name('siswa.login.attempt');
});

Route::get('/admin/login', fn () => redirect('/login'))->name('portal.admin.login');
Route::get('/tu/login', fn () => redirect('/login'))->name('portal.tu.login');
Route::get('/kepsek/login', fn () => redirect('/login'))->name('portal.kepsek.login');

Route::get('/admin', fn () => redirect('/admin/dashboard'));
Route::get('/tu', fn () => redirect('/tu/dashboard'));
Route::get('/kepsek', fn () => redirect('/kepsek/dashboard'));

Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
    ->middleware(['auth', 'role:admin', 'cache.headers:no_store;no_cache;must_revalidate;max_age=0'])
    ->name('admin.dashboard');

Route::get('/tu/dashboard', [DashboardController::class, 'tu'])
    ->middleware(['auth', 'role:tu', 'cache.headers:no_store;no_cache;must_revalidate;max_age=0'])
    ->name('tu.dashboard');

Route::get('/kepsek/dashboard', [DashboardController::class, 'kepsek'])
    ->middleware(['auth', 'role:kepala_sekolah', 'cache.headers:no_store;no_cache;must_revalidate;max_age=0'])
    ->name('kepsek.dashboard');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role:admin|tu|kepala_sekolah', 'cache.headers:no_store;no_cache;must_revalidate;max_age=0'])->group(function () {
    Route::get('/rekap-pembayaran', [RekapPembayaranController::class, 'index'])->name('rekap-pembayaran.index');
    Route::get('/rekap-pembayaran/export/excel', [RekapPembayaranController::class, 'exportExcel'])->name('rekap-pembayaran.export.excel');
    Route::get('/rekap-pembayaran/export/pdf', [RekapPembayaranController::class, 'exportPdf'])->name('rekap-pembayaran.export.pdf');
});

Route::middleware(['auth', 'role:admin|tu', 'cache.headers:no_store;no_cache;must_revalidate;max_age=0'])->group(function () {
    Route::get('/pembayaran/kwitansi/{pembayaran}', [PembayaranController::class, 'showKwitansi'])->name('pembayaran.kwitansi.show');
    Route::get('/riwayat-pembayaran', [RiwayatPembayaranController::class, 'index'])->name('riwayat-pembayaran.index');
    Route::get('/riwayat-pembayaran/{pembayaran}', [RiwayatPembayaranController::class, 'show'])->name('riwayat-pembayaran.show');

    Route::get('/pembayaran-non-spp/kwitansi/{pembayaranNonSpp}', [PembayaranNonSppController::class, 'kwitansi'])->name('pembayaran-non-spp.kwitansi');
    Route::get('/riwayat-pembayaran-non-spp', [RiwayatPembayaranNonSppController::class, 'index'])->name('riwayat-pembayaran-non-spp.index');
    Route::get('/riwayat-pembayaran-non-spp/{pembayaranNonSpp}', [RiwayatPembayaranNonSppController::class, 'show'])->name('riwayat-pembayaran-non-spp.show');
    Route::get('/penerimaan/kwitansi/{penerimaan}', [PenerimaanController::class, 'kwitansi'])->name('penerimaan.kwitansi');
    Route::get('/riwayat-penerimaan', [PenerimaanController::class, 'riwayat'])->name('penerimaan.riwayat');
    Route::get('/riwayat-penerimaan/{penerimaan}', [PenerimaanController::class, 'detail'])->name('penerimaan.detail');
    Route::get('/riwayat-pengeluaran', [PengeluaranController::class, 'riwayat'])->name('pengeluaran.riwayat');
    Route::get('/riwayat-pengeluaran/{pengeluaran}', [PengeluaranController::class, 'detail'])->name('pengeluaran.detail');
});

Route::middleware(['auth', 'role:tu', 'cache.headers:no_store;no_cache;must_revalidate;max_age=0'])->group(function () {
    Route::get('/pembayaran', [PembayaranController::class, 'index'])->name('pembayaran.index');
    Route::get('/pembayaran/{siswa}', [PembayaranController::class, 'show'])->name('pembayaran.show');
    Route::post('/pembayaran/{siswa}', [PembayaranController::class, 'store'])->name('pembayaran.store');

    Route::get('/pembayaran-non-spp', [PembayaranNonSppController::class, 'index'])->name('pembayaran-non-spp.index');
    Route::get('/pembayaran-non-spp/{siswa}', [PembayaranNonSppController::class, 'show'])->name('pembayaran-non-spp.show');
    Route::post('/pembayaran-non-spp/{siswa}', [PembayaranNonSppController::class, 'store'])->name('pembayaran-non-spp.store');

    Route::get('/penerimaan', [PenerimaanController::class, 'index'])->name('penerimaan.index');
    Route::get('/penerimaan/{siswa}', [PenerimaanController::class, 'show'])->name('penerimaan.show');
    Route::post('/penerimaan/{siswa}', [PenerimaanController::class, 'store'])->name('penerimaan.store');

    Route::get('/pengeluaran', [PengeluaranController::class, 'index'])->name('pengeluaran.index');
    Route::post('/pengeluaran', [PengeluaranController::class, 'store'])->name('pengeluaran.store');

    Route::get('/laporan-tunggakan', [LaporanTunggakanController::class, 'index'])->name('laporan-tunggakan.index');
    Route::get('/laporan-tunggakan/export/excel', [LaporanTunggakanController::class, 'exportExcel'])->name('laporan-tunggakan.export.excel');
    Route::get('/laporan-tunggakan/export/pdf', [LaporanTunggakanController::class, 'exportPdf'])->name('laporan-tunggakan.export.pdf');
    Route::get('/arsip-kwitansi', [ArsipKwitansiSiswaController::class, 'index'])->name('arsip-kwitansi.index');
    Route::get('/arsip-kwitansi/{arsipKwitansi}', [ArsipKwitansiSiswaController::class, 'show'])->name('arsip-kwitansi.show');
    Route::get('/status-spp', [StatusSppController::class, 'index'])->name('status-spp.index');
    Route::get('/status-spp/{siswa}', [StatusSppController::class, 'show'])->withTrashed()->name('status-spp.show');
});

Route::middleware(['auth', 'role:admin', 'cache.headers:no_store;no_cache;must_revalidate;max_age=0'])->group(function () {
    Route::patch('/riwayat-pembayaran/{pembayaran}/batalkan', [RiwayatPembayaranController::class, 'batalkan'])->name('riwayat-pembayaran.batalkan');
    Route::patch('/riwayat-pembayaran-non-spp/{pembayaranNonSpp}/batalkan', [RiwayatPembayaranNonSppController::class, 'batalkan'])->name('riwayat-pembayaran-non-spp.batalkan');
    Route::patch('/riwayat-penerimaan/{penerimaan}/batalkan', [PenerimaanController::class, 'batalkan'])->name('penerimaan.batalkan');
    Route::patch('/riwayat-pengeluaran/{pengeluaran}/batalkan', [PengeluaranController::class, 'batalkan'])->name('pengeluaran.batalkan');
    Route::get('/kenaikan-kelas/preview', [KenaikanKelasController::class, 'preview'])->name('kenaikan-kelas.preview');
    Route::post('/kenaikan-kelas/proses', [KenaikanKelasController::class, 'proses'])->name('kenaikan-kelas.proses');

    Route::prefix('accounts/staff')->name('admin.accounts.staff.')->group(function () {
        Route::get('/', [AccountController::class, 'index'])->name('index');
        Route::get('/create', [AccountController::class, 'create'])->name('create');
        Route::post('/', [AccountController::class, 'store'])->name('store');
        Route::get('/{account}/edit', [AccountController::class, 'edit'])->name('edit');
        Route::put('/{account}', [AccountController::class, 'update'])->name('update');
        Route::post('/{account}/toggle', [AccountController::class, 'toggleActive'])->name('toggle');
        Route::delete('/{account}', [AccountController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('accounts/siswa')->name('admin.accounts.siswa.')->group(function () {
        Route::get('/import', [SiswaAccountController::class, 'importForm'])->name('import');
        Route::post('/import', [SiswaAccountController::class, 'importSiswa'])->name('import.store');
        Route::get('/', [SiswaAccountController::class, 'index'])->name('index');
        Route::get('/create/{siswa}', [SiswaAccountController::class, 'create'])->name('create');
        Route::post('/{siswa}', [SiswaAccountController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [SiswaAccountController::class, 'edit'])->name('edit');
        Route::put('/{user}', [SiswaAccountController::class, 'update'])->name('update');
        Route::post('/{user}/sync-username', [SiswaAccountController::class, 'syncUsername'])->name('syncUsername');
        Route::post('/{user}/toggle', [SiswaAccountController::class, 'toggleActive'])->name('toggle');
        Route::delete('/{user}', [SiswaAccountController::class, 'destroy'])->name('destroy');
    });

    Route::get('/master-data/siswa/{siswa}/akun', function ($siswa) {
        return redirect()->route('admin.accounts.siswa.index');
    });

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

        Route::get('/jenis-pembayaran', [JenisPembayaranController::class, 'index'])->name('jenis-pembayaran.index');
        Route::get('/jenis-pembayaran/create', [JenisPembayaranController::class, 'create'])->name('jenis-pembayaran.create');
        Route::post('/jenis-pembayaran', [JenisPembayaranController::class, 'store'])->name('jenis-pembayaran.store');
        Route::get('/jenis-pembayaran/{jenisPembayaran}/edit', [JenisPembayaranController::class, 'edit'])->name('jenis-pembayaran.edit');
        Route::put('/jenis-pembayaran/{jenisPembayaran}', [JenisPembayaranController::class, 'update'])->name('jenis-pembayaran.update');
        Route::delete('/jenis-pembayaran/{jenisPembayaran}', [JenisPembayaranController::class, 'destroy'])->name('jenis-pembayaran.destroy');

        Route::get('/kategori-pengeluaran', [KategoriPengeluaranController::class, 'index'])->name('kategori-pengeluaran.index');
        Route::post('/kategori-pengeluaran', [KategoriPengeluaranController::class, 'store'])->name('kategori-pengeluaran.store');
        Route::get('/kategori-pengeluaran/{kategoriPengeluaran}/edit', [KategoriPengeluaranController::class, 'edit'])->name('kategori-pengeluaran.edit');
        Route::put('/kategori-pengeluaran/{kategoriPengeluaran}', [KategoriPengeluaranController::class, 'update'])->name('kategori-pengeluaran.update');
        Route::patch('/kategori-pengeluaran/{kategoriPengeluaran}/toggle', [KategoriPengeluaranController::class, 'toggle'])->name('kategori-pengeluaran.toggle');

        Route::get('/tagihan-non-spp', [TagihanNonSppBatchController::class, 'index'])->name('tagihan-non-spp.index');
        Route::get('/tagihan-non-spp/create', [TagihanNonSppBatchController::class, 'create'])->name('tagihan-non-spp.create');
        Route::post('/tagihan-non-spp', [TagihanNonSppBatchController::class, 'store'])->name('tagihan-non-spp.store');
        Route::get('/tagihan-non-spp/detail', [TagihanNonSppBatchController::class, 'detail'])->name('tagihan-non-spp.detail');
        Route::patch('/tagihan-non-spp/{siswa}/gelombang-bam', [TagihanNonSppBatchController::class, 'koreksiGelombangBam'])->name('tagihan-non-spp.gelombang-bam.koreksi');
    });
});

Route::middleware(['auth:siswa', 'role:siswa,siswa'])->prefix('siswa')->name('siswa.')->group(function () {
    Route::get('/status-spp', [SiswaPortalController::class, 'status'])->name('status');
    Route::post('/kwitansi', [SiswaPortalController::class, 'simpanFoto'])->name('kwitansi.store');
    Route::patch('/kwitansi', [SiswaPortalController::class, 'gantiFoto'])->name('kwitansi.update');
    Route::post('/logout', [SiswaAuthenticatedSessionController::class, 'destroy'])->name('logout');
});
