<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LayoutNavigationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_sees_admin_navigation_only(): void
    {
        $admin = User::factory()->admin()->create(['nama' => 'Admin Sistem']);

        $this->actingAs($admin)->followingRedirects()->get(route('home'))
            ->assertOk()
            ->assertSeeText([
                'Sistem Informasi Keuangan',
                'Admin Sistem',
                'Admin',
                'Master Data',
                'Data Siswa',
                'Data Jurusan',
                'Data Kelas',
                'Tahun Ajaran',
                'Tarif SPP',
                'Kenaikan Kelas',
                'Pembatalan Transaksi',
                'Rekap Pembayaran',
                'Backup Data',
                'Pending',
            ])
            ->assertSee('href="'.route('master.siswa.index').'"', false)
            ->assertSee('href="'.route('riwayat-pembayaran.index').'"', false)
            ->assertDontSee('href="'.route('pembayaran.index').'"', false)
            ->assertDontSee('href="'.route('arsip-kwitansi.index').'"', false)
            ->assertDontSee('href="'.route('laporan-tunggakan.index').'"', false)
            ->assertDontSee('href="'.route('status-spp.index').'"', false)
            ->assertSee('aria-disabled="true"', false);
    }

    public function test_tu_sees_operational_navigation_only(): void
    {
        $tu = User::factory()->tu()->create(['nama' => 'Petugas TU']);

        $this->actingAs($tu)->followingRedirects()->get(route('home'))
            ->assertOk()
            ->assertSeeText([
                'Tata Usaha',
                'Penerimaan',
                'SPP',
                'UJIKOM',
                'Pembayaran Lainnya',
                'Pengeluaran',
                'Input Pengeluaran',
                'Riwayat Pengeluaran',
                'Kelola Pembayaran',
                'Riwayat Pembayaran',
                'Arsip Kwitansi Siswa',
                'Status Pembayaran SPP',
                'Laporan / Rekap',
                'Laporan Tunggakan',
            ])
            ->assertSee('href="'.route('pembayaran.index').'"', false)
            ->assertSee('href="'.route('arsip-kwitansi.index').'"', false)
            ->assertSee('href="'.route('status-spp.index').'"', false)
            ->assertDontSee('href="'.route('master.siswa.index').'"', false)
            ->assertDontSeeText('Pembatalan Transaksi')
            ->assertSee('aria-disabled="true"', false);
    }

    public function test_kepala_sekolah_sees_read_only_navigation_only(): void
    {
        $kepalaSekolah = User::factory()->kepalaSekolah()->create(['nama' => 'Kepala Sekolah']);

        $this->actingAs($kepalaSekolah)->followingRedirects()->get(route('home'))
            ->assertOk()
            ->assertSeeText([
                'Kepala Sekolah',
                'Dashboard Keuangan',
                'Rekap',
                'Penerimaan',
            ])
            ->assertSee('href="'.route('rekap-pembayaran.index').'"', false)
            ->assertDontSee('href="'.route('master.siswa.index').'"', false)
            ->assertDontSee('href="'.route('pembayaran.index').'"', false)
            ->assertDontSee('href="'.route('riwayat-pembayaran.index').'"', false)
            ->assertDontSee('href="'.route('status-spp.index').'"', false);
    }

    public function test_data_table_headers_include_sorting_controls(): void
    {
        $tu = User::factory()->tu()->create();

        $this->actingAs($tu)->followingRedirects()->get(route('home'))
            ->assertOk()
            ->assertSee('table-sort-button', false)
            ->assertSee("indicator.textContent = '↕'", false)
            ->assertSee('sortableValue', false);
    }
}
