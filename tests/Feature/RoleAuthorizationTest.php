<?php

namespace Tests\Feature;

use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_only_admin_can_access_master_data(): void
    {
        $admin = User::factory()->admin()->create();
        $tu = User::factory()->tu()->create();
        $kepalaSekolah = User::factory()->kepalaSekolah()->create();

        $this->actingAs($admin)->get(route('master.siswa.index'))->assertOk();
        $this->actingAs($tu)->get(route('master.siswa.index'))->assertForbidden();
        $this->actingAs($kepalaSekolah)->get(route('master.siswa.index'))->assertForbidden();

        foreach ([$tu, $kepalaSekolah] as $user) {
            $this->actingAs($user)
                ->post(route('master.jurusan.store'), [
                    'kode_jurusan' => 'RPL',
                    'nama_jurusan' => 'Rekayasa Perangkat Lunak',
                ])
                ->assertForbidden();
        }

        $this->assertDatabaseMissing('jurusan', ['kode_jurusan' => 'RPL']);
    }

    public function test_only_tu_can_access_the_spp_transaction_flow(): void
    {
        $admin = User::factory()->admin()->create();
        $tu = User::factory()->tu()->create();
        $kepalaSekolah = User::factory()->kepalaSekolah()->create();

        $this->actingAs($tu)->get(route('pembayaran.index'))->assertOk();
        $this->actingAs($admin)->get(route('pembayaran.index'))->assertForbidden();
        $this->actingAs($kepalaSekolah)->get(route('pembayaran.index'))->assertForbidden();

        $siswa = Siswa::create([
            'nipd' => 'R1-AUTH',
            'nama_siswa' => 'Siswa Authorization',
            'jenis_kelamin' => 'L',
            'angkatan' => 2026,
            'status_siswa' => 'aktif',
        ]);

        $this->actingAs($kepalaSekolah)
            ->post(route('pembayaran.store', $siswa), ['id_tagihan' => [1]])
            ->assertForbidden();
    }

    public function test_all_internal_roles_can_access_dashboard_and_rekap(): void
    {
        foreach (User::INTERNAL_ROLES as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('home'))->assertRedirect();
            $this->actingAs($user)->get(route('rekap-pembayaran.index'))->assertOk();
        }
    }

    public function test_kepala_sekolah_cannot_access_operational_history(): void
    {
        $kepalaSekolah = User::factory()->kepalaSekolah()->create();

        $this->actingAs($kepalaSekolah)
            ->get(route('riwayat-pembayaran.index'))
            ->assertForbidden();
    }

    public function test_admin_history_page_does_not_offer_tu_transaction_action(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('riwayat-pembayaran.index'))
            ->assertOk()
            ->assertDontSee('href="'.route('pembayaran.index').'"', false)
            ->assertDontSeeText('Input Transaksi Baru');
    }

    public function test_student_role_cannot_access_internal_routes_even_on_web_guard(): void
    {
        $siswa = User::factory()->create(['role' => User::ROLE_SISWA]);

        $this->actingAs($siswa, 'web')->get(route('home'))->assertRedirect();
        $this->actingAs($siswa, 'web')->get(route('master.siswa.index'))->assertForbidden();
        $this->actingAs($siswa, 'web')->get(route('pembayaran.index'))->assertForbidden();
    }
}
