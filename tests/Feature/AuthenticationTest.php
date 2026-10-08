<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_each_internal_role_can_log_in_with_the_same_form(): void
    {
        foreach (User::INTERNAL_ROLES as $role) {
            $user = User::factory()->create([
                'username' => 'login.'.$role,
                'password' => Hash::make('rahasia'),
                'role' => $role,
            ]);

            $this->post(route('login.attempt'), [
                'username' => $user->username,
                'password' => 'rahasia',
            ])->assertRedirect(route(match ($role) {
                User::ROLE_ADMIN => 'admin.dashboard',
                User::ROLE_TU => 'tu.dashboard',
                User::ROLE_KEPALA_SEKOLAH => 'kepsek.dashboard',
            }));

            $this->assertAuthenticatedAs($user, 'web');
            $this->post(route('logout'))->assertRedirect(route('login'));
        }
    }

    public function test_student_role_cannot_log_in_through_the_internal_form(): void
    {
        $user = User::factory()->create([
            'username' => 'login.siswa',
            'password' => Hash::make('rahasia'),
            'role' => User::ROLE_SISWA,
        ]);

        $this->from(route('login'))->post(route('login.attempt'), [
            'username' => $user->username,
            'password' => 'rahasia',
        ])->assertRedirect(route('login'))->assertSessionHasErrors('username');

        $this->assertGuest('web');
    }

    public function test_login_rejects_an_invalid_password(): void
    {
        User::factory()->create([
            'username' => 'petugas.tu',
            'password' => Hash::make('rahasia'),
        ]);

        $response = $this->from(route('login'))->post(route('login.attempt'), [
            'username' => 'petugas.tu',
            'password' => 'salah',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_login_rejects_an_unknown_username(): void
    {
        $response = $this->from(route('login'))->post(route('login.attempt'), [
            'username' => 'tidak-ada',
            'password' => 'rahasia',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        User::factory()->create([
            'username' => 'petugas.throttle',
            'password' => Hash::make('rahasia'),
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from(route('login'))->post(route('login.attempt'), [
                'username' => 'petugas.throttle',
                'password' => 'salah',
            ])->assertSessionHasErrors('username');
        }

        $this->from(route('login'))->post(route('login.attempt'), [
            'username' => 'petugas.throttle',
            'password' => 'rahasia',
        ])->assertSessionHasErrors('username');

        RateLimiter::clear('petugas.throttle|127.0.0.1');
        $this->assertGuest();
    }

    public function test_guest_cannot_access_internal_route(): void
    {
        $this->get(route('home'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_internal_user_is_redirected_to_their_dashboard_from_login(): void
    {
        $user = User::factory()->tu()->create(['nama' => 'Petugas Aktif']);

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('tu.dashboard'));

        $this->get(route('portal.tu.login'))
            ->assertRedirect(route('login'));
    }

    public function test_authentication_pages_are_not_cached_by_the_browser(): void
    {
        $loginResponse = $this->get(route('login'));

        $this->assertStringContainsString('no-store', (string) $loginResponse->headers->get('Cache-Control'));

        $user = User::factory()->tu()->create();
        $dashboardResponse = $this->actingAs($user)->get(route('tu.dashboard'));

        $dashboardResponse->assertOk();
        $this->assertStringContainsString('no-store', (string) $dashboardResponse->headers->get('Cache-Control'));
    }

    public function test_authenticated_petugas_can_log_out(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
