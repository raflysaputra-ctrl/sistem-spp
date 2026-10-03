<?php

namespace Tests\Feature;

use App\Models\AccountLog;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class SiswaAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountSeeder::class);
    }

    public function test_admin_can_access_siswa_accounts_index(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('admin.accounts.siswa.index'));

        $response->assertOk();
    }

    public function test_tu_cannot_access_siswa_accounts_index(): void
    {
        $tu = User::where('username', 'tata_usaha')->first();

        $response = $this->actingAs($tu)->get(route('admin.accounts.siswa.index'));

        $response->assertForbidden();
    }

    public function test_kepala_sekolah_cannot_access_siswa_accounts_index(): void
    {
        $kepsek = User::where('username', 'kepala_sekolah')->first();

        $response = $this->actingAs($kepsek)->get(route('admin.accounts.siswa.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_create_siswa_account_with_nipd_as_username(): void
    {
        $admin = User::where('username', 'admin')->first();
        $siswa = Siswa::factory()->create(['nipd' => '12345', 'nama_siswa' => 'Test Siswa']);

        $response = $this->actingAs($admin)->post(route('admin.accounts.siswa.store', $siswa), [
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('admin.accounts.siswa.index'));
        $this->assertDatabaseHas('users', [
            'username' => '12345',
            'role' => User::ROLE_SISWA,
            'id_siswa' => $siswa->id_siswa,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('account_logs', [
            'action' => 'CREATE',
        ]);
    }

    public function test_admin_cannot_create_duplicate_siswa_account(): void
    {
        $admin = User::where('username', 'admin')->first();
        $siswa = Siswa::factory()->create(['nipd' => '12345']);

        User::factory()->create([
            'username' => '12345',
            'role' => User::ROLE_SISWA,
            'id_siswa' => $siswa->id_siswa,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.accounts.siswa.store', $siswa), [
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_admin_can_reset_siswa_password(): void
    {
        $admin = User::where('username', 'admin')->first();
        $siswa = Siswa::factory()->create(['nipd' => '12345']);
        $siswaUser = User::factory()->create([
            'username' => '12345',
            'password' => 'oldpassword',
            'role' => User::ROLE_SISWA,
            'id_siswa' => $siswa->id_siswa,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.accounts.siswa.update', $siswaUser), [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('admin.accounts.siswa.index'));

        $siswaUser->refresh();
        $this->assertTrue(Hash::check('newpassword123', $siswaUser->password));

        $this->assertDatabaseHas('account_logs', [
            'user_id' => $siswaUser->id_user,
            'action' => 'RESET_PASSWORD',
        ]);
    }

    public function test_admin_can_toggle_siswa_account_active_status(): void
    {
        $admin = User::where('username', 'admin')->first();
        $siswa = Siswa::factory()->create(['nipd' => '12345']);
        $siswaUser = User::factory()->create([
            'username' => '12345',
            'role' => User::ROLE_SISWA,
            'id_siswa' => $siswa->id_siswa,
            'is_active' => true,
        ]);

        $originalStatus = $siswaUser->is_active;

        $response = $this->actingAs($admin)->post(route('admin.accounts.siswa.toggle', $siswaUser));

        $response->assertRedirect();
        $siswaUser->refresh();
        $this->assertEquals(!$originalStatus, $siswaUser->is_active);

        $expectedAction = $siswaUser->is_active ? 'ACTIVATE' : 'DEACTIVATE';
        $this->assertDatabaseHas('account_logs', [
            'user_id' => $siswaUser->id_user,
            'action' => $expectedAction,
        ]);
    }

    public function test_admin_can_sync_username_when_nipd_changes(): void
    {
        $admin = User::where('username', 'admin')->first();
        $siswa = Siswa::factory()->create(['nipd' => '99999']);
        $siswaUser = User::factory()->create([
            'username' => '12345',
            'role' => User::ROLE_SISWA,
            'id_siswa' => $siswa->id_siswa,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.accounts.siswa.syncUsername', $siswaUser));

        $response->assertRedirect();
        $siswaUser->refresh();
        $this->assertEquals('99999', $siswaUser->username);

        $this->assertDatabaseHas('account_logs', [
            'user_id' => $siswaUser->id_user,
            'action' => 'UPDATE',
        ]);
    }

    public function test_admin_can_hard_delete_siswa_account_without_transactions(): void
    {
        $admin = User::where('username', 'admin')->first();
        $siswa = Siswa::factory()->create(['nipd' => '12345']);
        $siswaUser = User::factory()->create([
            'username' => '12345',
            'role' => User::ROLE_SISWA,
            'id_siswa' => $siswa->id_siswa,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.accounts.siswa.destroy', $siswaUser));

        $response->assertRedirect();
        $this->assertDatabaseMissing('users', [
            'id_user' => $siswaUser->id_user,
        ]);

        $this->assertDatabaseHas('account_logs', [
            'action' => 'DELETE',
        ]);
    }

    public function test_admin_soft_deletes_siswa_account_with_transactions(): void
    {
        $admin = User::where('username', 'admin')->first();
        $siswa = Siswa::factory()->create(['nipd' => '12345']);
        $siswaUser = User::factory()->create([
            'username' => '12345',
            'role' => User::ROLE_SISWA,
            'id_siswa' => $siswa->id_siswa,
            'is_active' => true,
        ]);

        Pembayaran::create([
            'no_kwitansi' => 'KW-001',
            'id_siswa' => $siswa->id_siswa,
            'id_user' => $siswaUser->id_user,
            'tanggal_bayar' => now(),
            'total_bayar' => 100000,
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.accounts.siswa.destroy', $siswaUser));

        $response->assertRedirect();
        $siswaUser->refresh();
        $this->assertFalse($siswaUser->is_active);
        $this->assertDatabaseHas('users', [
            'id_user' => $siswaUser->id_user,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('account_logs', [
            'user_id' => $siswaUser->id_user,
            'action' => 'DEACTIVATE',
        ]);
    }

    public function test_admin_can_import_siswa_accounts_from_excel(): void
    {
        $admin = User::where('username', 'admin')->first();

        $siswa1 = Siswa::factory()->create(['nipd' => '12345', 'nama_siswa' => 'Siswa Pertama']);
        $siswa2 = Siswa::factory()->create(['nipd' => '67890', 'nama_siswa' => 'Siswa Kedua']);

        Excel::fake();

        $file = UploadedFile::fake()->create('accounts.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([[
                ['Nama', 'NIPD', 'Password'],
                ['Siswa Pertama', '12345', 'password123'],
                ['Siswa Kedua', '67890', 'password456'],
            ]]);

        $response = $this->actingAs($admin)->post(route('admin.accounts.siswa.import.store'), [
            'file' => $file,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'username' => '12345',
            'role' => User::ROLE_SISWA,
            'id_siswa' => $siswa1->id_siswa,
            'nama' => 'Siswa Pertama',
        ]);

        $this->assertDatabaseHas('users', [
            'username' => '67890',
            'role' => User::ROLE_SISWA,
            'id_siswa' => $siswa2->id_siswa,
            'nama' => 'Siswa Kedua',
        ]);

        $this->assertCount(2, AccountLog::where('action', 'CREATE')->where('actor_id', $admin->id_user)->get());
    }

    public function test_import_validates_minimum_password_length(): void
    {
        $admin = User::where('username', 'admin')->first();

        $siswa1 = Siswa::factory()->create(['nipd' => '12345']);

        Excel::fake();

        $file = UploadedFile::fake()->create('accounts.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([[
                ['Nama', 'NIPD', 'Password'],
                ['Siswa Test', '12345', 'short'],
            ]]);

        $response = $this->actingAs($admin)->post(route('admin.accounts.siswa.import.store'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('import_errors');

        $this->assertDatabaseMissing('users', [
            'username' => '12345',
        ]);
    }

    public function test_import_skips_siswa_with_existing_account(): void
    {
        $admin = User::where('username', 'admin')->first();

        $siswa1 = Siswa::factory()->create(['nipd' => '12345']);
        User::factory()->create([
            'role' => User::ROLE_SISWA,
            'id_siswa' => $siswa1->id_siswa,
            'username' => '12345',
        ]);

        Excel::fake();

        $file = UploadedFile::fake()->create('accounts.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([[
                ['Nama', 'NIPD', 'Password'],
                ['Siswa Test', '12345', 'password123'],
            ]]);

        $response = $this->actingAs($admin)->post(route('admin.accounts.siswa.import.store'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('import_errors');

        $this->assertEquals(1, User::where('username', '12345')->count());
    }

    public function test_siswa_account_filter_by_status(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('admin.accounts.siswa.index', ['status_akun' => 'aktif']));

        $response->assertOk();
    }

    public function test_siswa_account_search_by_name_or_nipd(): void
    {
        $admin = User::where('username', 'admin')->first();
        $siswa = Siswa::factory()->create(['nipd' => '99999', 'nama_siswa' => 'Test Cari']);

        $response = $this->actingAs($admin)->get(route('admin.accounts.siswa.index', ['search' => '99999']));

        $response->assertOk();
    }
}
