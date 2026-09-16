<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LayoutNavigationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_authenticated_petugas_can_see_the_initial_application_navigation(): void
    {
        $user = User::factory()->create(['nama' => 'Petugas TU']);

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSeeText([
                'Dashboard',
                'Master Data',
                'Data Siswa',
                'Data Jurusan',
                'Data Kelas',
                'Tahun Ajaran',
                'Tarif SPP',
                'Kenaikan Kelas',
                'Pembayaran',
                'Transaksi Pembayaran',
                'Riwayat Pembayaran',
                'Laporan',
                'Rekap Pembayaran',
                'Status Pembayaran SPP',
                'Logout',
            ])
            ->assertSee(route('status-spp.index'), false)
            ->assertSee(route('kenaikan-kelas.preview'), false)
            ->assertSee(asset('images/cbi.png'), false)
            ->assertSee('alt="Logo SMK Informatika CBI"', false)
            ->assertSee('overflow-y: auto;', false)
            ->assertSee('overflow-y: hidden;', false);
    }

    public function test_data_table_headers_include_sorting_controls(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSee('table-sort-button', false)
            ->assertSee("indicator.textContent = '↕'", false)
            ->assertSee('sortableValue', false);
    }
}
