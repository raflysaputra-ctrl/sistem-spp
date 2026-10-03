<?php

namespace Tests\Feature;

use App\Models\AccountLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountSeeder::class);
    }

    public function test_admin_can_access_staff_accounts_index(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('admin.accounts.staff.index'));

        $response->assertOk();
        $response->assertSee('Manajemen Akun');
    }

    public function test_tu_cannot_access_staff_accounts_index(): void
    {
        $tu = User::where('username', 'tata_usaha')->first();

        $response = $this->actingAs($tu)->get(route('admin.accounts.staff.index'));

        $response->assertForbidden();
    }

    public function test_kepala_sekolah_cannot_access_staff_accounts_index(): void
    {
        $kepsek = User::where('username', 'kepala_sekolah')->first();

        $response = $this->actingAs($kepsek)->get(route('admin.accounts.staff.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_create_admin_account(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->post(route('admin.accounts.staff.store'), [
            'nama' => 'New Admin User',
            'username' => 'new_admin',
            'password' => 'password123',
            'role' => User::ROLE_ADMIN,
        ]);

        $response->assertRedirect(route('admin.accounts.staff.index'));
        $this->assertDatabaseHas('users', [
            'username' => 'new_admin',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('account_logs', [
            'action' => 'CREATE',
        ]);
    }

    public function test_admin_can_create_tu_account(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->post(route('admin.accounts.staff.store'), [
            'nama' => 'New TU User',
            'username' => 'new_tu',
            'password' => 'password123',
            'role' => User::ROLE_TU,
        ]);

        $response->assertRedirect(route('admin.accounts.staff.index'));
        $this->assertDatabaseHas('users', [
            'username' => 'new_tu',
            'role' => User::ROLE_TU,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_kepala_sekolah_account(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->post(route('admin.accounts.staff.store'), [
            'nama' => 'New Kepala Sekolah',
            'username' => 'new_kepsek',
            'password' => 'password123',
            'role' => User::ROLE_KEPALA_SEKOLAH,
        ]);

        $response->assertRedirect(route('admin.accounts.staff.index'));
        $this->assertDatabaseHas('users', [
            'username' => 'new_kepsek',
            'role' => User::ROLE_KEPALA_SEKOLAH,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_staff_account(): void
    {
        $admin = User::where('username', 'admin')->first();
        $tu = User::where('username', 'tata_usaha')->first();

        $response = $this->actingAs($admin)->put(route('admin.accounts.staff.update', $tu), [
            'nama' => 'Updated TU Name',
            'username' => 'tata_usaha',
            'role' => User::ROLE_TU,
        ]);

        $response->assertRedirect(route('admin.accounts.staff.index'));
        $this->assertDatabaseHas('users', [
            'id_user' => $tu->id_user,
            'nama' => 'Updated TU Name',
        ]);

        $this->assertDatabaseHas('account_logs', [
            'user_id' => $tu->id_user,
            'action' => 'UPDATE',
        ]);
    }

    public function test_admin_can_reset_staff_password(): void
    {
        $admin = User::where('username', 'admin')->first();
        $tu = User::where('username', 'tata_usaha')->first();

        $response = $this->actingAs($admin)->put(route('admin.accounts.staff.update', $tu), [
            'nama' => $tu->nama,
            'username' => $tu->username,
            'role' => $tu->role,
            'password' => 'newpassword123',
        ]);

        $response->assertRedirect(route('admin.accounts.staff.index'));

        $tu->refresh();
        $this->assertTrue(Hash::check('newpassword123', $tu->password));

        $this->assertDatabaseHas('account_logs', [
            'user_id' => $tu->id_user,
            'action' => 'RESET_PASSWORD',
        ]);
    }

    public function test_admin_can_toggle_staff_account_active_status(): void
    {
        $admin = User::where('username', 'admin')->first();
        $tu = User::where('username', 'tata_usaha')->first();

        $originalStatus = $tu->is_active;

        $response = $this->actingAs($admin)->post(route('admin.accounts.staff.toggle', $tu));

        $response->assertRedirect();
        $tu->refresh();
        $this->assertEquals(!$originalStatus, $tu->is_active);

        $expectedAction = $tu->is_active ? 'ACTIVATE' : 'DEACTIVATE';
        $this->assertDatabaseHas('account_logs', [
            'user_id' => $tu->id_user,
            'action' => $expectedAction,
        ]);
    }

    public function test_admin_cannot_toggle_own_account(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->post(route('admin.accounts.staff.toggle', $admin));

        $response->assertSessionHasErrors();
    }

    public function test_admin_can_delete_staff_account(): void
    {
        $admin = User::where('username', 'admin')->first();
        $tu = User::where('username', 'tata_usaha')->first();

        $response = $this->actingAs($admin)->delete(route('admin.accounts.staff.destroy', $tu));

        $response->assertRedirect(route('admin.accounts.staff.index'));
        $this->assertDatabaseMissing('users', [
            'id_user' => $tu->id_user,
        ]);

        $this->assertDatabaseHas('account_logs', [
            'action' => 'DELETE',
            'actor_id' => $admin->id_user,
        ]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->delete(route('admin.accounts.staff.destroy', $admin));

        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('users', [
            'id_user' => $admin->id_user,
        ]);
    }

    public function test_staff_account_filter_by_role(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('admin.accounts.staff.index', ['role' => User::ROLE_TU]));

        $response->assertOk();
        $response->assertSee('tata_usaha');
    }

    public function test_staff_account_filter_by_status(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('admin.accounts.staff.index', ['status' => 'active']));

        $response->assertOk();
    }

    public function test_staff_account_search_by_name_or_username(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('admin.accounts.staff.index', ['search' => 'admin']));

        $response->assertOk();
        $response->assertSee('admin');
    }
}
