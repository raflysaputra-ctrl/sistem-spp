<?php

namespace Tests\Feature;

use App\Models\Pembayaran;
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

    public function test_petugas_can_view_operational_dashboard_and_quick_actions(): void
    {
        $user = User::factory()->create(['nama' => 'Petugas Dashboard']);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText([
                'Selamat datang, Petugas Dashboard.',
                'Siswa Aktif',
                'Transaksi Hari Ini',
                'Penerimaan Bulan Ini',
                'Transaksi Terbaru',
                'Pencarian Cepat',
                'Status Pembayaran SPP',
                'Riwayat Pembayaran',
                'Rekap Pembayaran',
            ])
            ->assertSee(route('pembayaran.index'), false)
            ->assertSee(route('riwayat-pembayaran.index'), false)
            ->assertSee(route('rekap-pembayaran.index'), false);
    }

    public function test_dashboard_displays_six_month_financial_chart_by_transaction_date(): void
    {
        Carbon::setTestNow('2026-09-04 10:00:00');

        try {
            $user = User::factory()->create();
            $siswa = Siswa::query()->create([
                'nipd' => '20260001',
                'nama_siswa' => 'Siswa Grafik',
                'jenis_kelamin' => 'L',
                'angkatan' => 2026,
                'status_siswa' => 'aktif',
            ]);

            Pembayaran::query()->create([
                'no_kwitansi' => 'KWT-CHART-001',
                'id_siswa' => $siswa->id_siswa,
                'id_user' => $user->id_user,
                'tanggal_bayar' => now()->subMonth(),
                'total_bayar' => 450000,
            ]);

            $this->actingAs($user)
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
}
