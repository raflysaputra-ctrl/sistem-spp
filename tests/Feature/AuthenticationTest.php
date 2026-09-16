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

    public function test_petugas_can_log_in_with_valid_username_and_password(): void
    {
        $user = User::factory()->create([
            'username' => 'petugas.tu',
            'password' => Hash::make('rahasia'),
        ]);

        $response = $this->post(route('login.attempt'), [
            'username' => 'petugas.tu',
            'password' => 'rahasia',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
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

    public function test_authenticated_petugas_can_log_out(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
