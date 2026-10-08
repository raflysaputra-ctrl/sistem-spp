<?php

namespace Tests\Feature;

use App\Models\Pembayaran;
use App\Models\Penerimaan;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get(route('home'))
            ->assertRedirect(route('login'));
    }

    public function test_tu_can_view_operational_dashboard_and_quick_actions(): void
    {
        $user = User::factory()->tu()->create(['nama' => 'Petugas Dashboard']);

        $this->actingAs($user)
            ->followingRedirects()
            ->get(route('home'))
            ->assertOk()
            ->assertViewIs('dashboard.tu')
            ->assertSeeText([
                'Selamat datang, Petugas Dashboard.',
                'Siswa Aktif',
                'Transaksi Hari Ini',
                'Penerimaan Bulan Ini',
                'Transaksi Terbaru',
                'Pencarian Cepat',
                'Status Pembayaran SPP',
                'Riwayat Pembayaran',
                'Rekap Penerimaan',
            ])
            ->assertSee(route('penerimaan.index'), false)
            ->assertSee(route('penerimaan.riwayat'), false)
            ->assertSee(route('rekap-pembayaran.index'), false);
    }

    public function test_admin_sees_control_dashboard_without_tu_transaction_actions(): void
    {
        $admin = User::factory()->admin()->create(['nama' => 'Admin Keuangan']);

        $this->actingAs($admin)
            ->followingRedirects()
            ->get(route('home'))
            ->assertOk()
            ->assertViewIs('dashboard.admin')
            ->assertSeeText([
                'Dashboard Admin',
                'Selamat datang, Admin Keuangan.',
                'Kontrol Sistem',
                'Kelola Data Siswa',
                'Tinjau Pembatalan Transaksi',
                'Lihat Rekap Penerimaan',
            ])
            ->assertDontSee('href="'.route('pembayaran.index').'"', false)
            ->assertDontSee('href="'.route('status-spp.index').'"', false);
    }

    public function test_kepala_sekolah_sees_read_only_available_financial_data(): void
    {
        $kepalaSekolah = User::factory()->kepalaSekolah()->create(['nama' => 'Kepala Sekolah']);

        $this->actingAs($kepalaSekolah)
            ->followingRedirects()
            ->get(route('home'))
            ->assertOk()
            ->assertViewIs('dashboard.kepala-sekolah')
            ->assertSeeText([
                'Dashboard Keuangan',
                'Monitoring penerimaan sekolah',
                'Penerimaan Bulan Ini',
                'Penerimaan 6 Bulan',
                'Data Pengeluaran dan selisih keuangan belum ditampilkan',
            ])
            ->assertSee('href="'.route('rekap-pembayaran.index').'"', false)
            ->assertDontSee('href="'.route('pembayaran.index').'"', false)
            ->assertDontSee('href="'.route('riwayat-pembayaran.index').'"', false);
    }

    public function test_dashboard_displays_six_month_financial_chart_by_transaction_date(): void
    {
        Carbon::setTestNow('2026-09-04 10:00:00');

        try {
            $user = User::factory()->tu()->create();
            $siswa = Siswa::query()->create([
                'nipd' => '20260001',
                'nama_siswa' => 'Siswa Grafik',
                'jenis_kelamin' => 'L',
                'angkatan' => 2026,
                'status_siswa' => 'aktif',
            ]);

            Penerimaan::query()->create([
                'no_kwitansi' => 'KWT-CHART-001',
                'id_siswa' => $siswa->id_siswa,
                'id_user' => $user->id_user,
                'tanggal_bayar' => now()->subMonth(),
                'total_bayar' => 450000,
            ]);

            $this->actingAs($user)
                ->followingRedirects()
                ->get(route('home'))
                ->assertOk()
                ->assertSeeText([
                    'Penerimaan 6 Bulan Terakhir',
                    'Berdasarkan tanggal transaksi pembayaran.',
                    'Apr 2026',
                    'Sep 2026',
                ])
                ->assertSeeText('Agu 2026')
                ->assertSee('data-finance-chart', false)
                ->assertSee('id="finance-chart-data"', false)
                ->assertSee('Grafik garis penerimaan enam bulan terakhir berdasarkan tanggal transaksi', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_excludes_cancelled_transactions_from_active_income(): void
    {
        Carbon::setTestNow('2026-09-04 10:00:00');

        try {
            $user = User::factory()->tu()->create();
            $siswa = Siswa::query()->create([
                'nipd' => '20260002',
                'nama_siswa' => 'Siswa Dashboard Batal',
                'jenis_kelamin' => 'P',
                'angkatan' => 2026,
                'status_siswa' => 'aktif',
            ]);
            Penerimaan::query()->create([
                'no_kwitansi' => 'KWT-AKTIF-001',
                'id_siswa' => $siswa->id_siswa,
                'id_user' => $user->id_user,
                'tanggal_bayar' => now(),
                'total_bayar' => 150000,
                'status' => 'aktif',
            ]);
            Penerimaan::query()->create([
                'no_kwitansi' => 'KWT-BATAL-001',
                'id_siswa' => $siswa->id_siswa,
                'id_user' => $user->id_user,
                'tanggal_bayar' => now(),
                'total_bayar' => 500000,
                'status' => 'dibatalkan',
                'alasan_pembatalan' => 'Salah input.',
                'dibatalkan_oleh' => $user->id_user,
                'dibatalkan_pada' => now(),
            ]);

            $this->actingAs($user)
                ->followingRedirects()
                ->get(route('home'))
                ->assertOk()
                ->assertViewHas('jumlahTransaksiHariIni', 1)
                ->assertViewHas('totalPenerimaanBulanIni', 150000)
                ->assertDontSee('KWT-BATAL-001');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_hides_legacy_payments_without_a_unified_receipt(): void
    {
        Carbon::setTestNow('2026-09-04 10:00:00');

        try {
            $user = User::factory()->tu()->create();
            $siswa = Siswa::query()->create([
                'nipd' => '20260003',
                'nama_siswa' => 'Siswa Transaksi Lama',
                'jenis_kelamin' => 'L',
                'angkatan' => 2026,
                'status_siswa' => 'aktif',
            ]);
            Pembayaran::query()->create([
                'no_kwitansi' => 'KWT-LEGACY-001',
                'id_siswa' => $siswa->id_siswa,
                'id_user' => $user->id_user,
                'tanggal_bayar' => now(),
                'total_bayar' => 150_000,
                'status' => 'aktif',
            ]);

            $this->actingAs($user)->get(route('tu.dashboard'))
                ->assertOk()
                ->assertViewHas('jumlahTransaksiHariIni', 0)
                ->assertViewHas('totalPenerimaanBulanIni', 0)
                ->assertDontSee('KWT-LEGACY-001');
        } finally {
            Carbon::setTestNow();
        }
    }
}
