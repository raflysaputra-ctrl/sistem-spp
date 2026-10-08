<?php

namespace Tests\Feature;

use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AuthenticationSessionIsolationTest extends TestCase
{
    use DatabaseTransactions;

    /** @var array<string, string> */
    private array $cookies = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(ValidateCsrfToken::class, EnforceCsrfToken::class);
    }

    public function test_invalid_internal_csrf_token_redirects_back_to_login_without_a_419_page(): void
    {
        $tu = User::factory()->tu()->create(['password' => 'password']);
        $this->request('GET', route('login'))->assertOk();

        $this->request('POST', route('login.attempt'), [
            '_token' => 'token-tidak-valid',
            'username' => $tu->username,
            'password' => 'password',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('form');

        $this->assertGuest('web');
    }

    public function test_a_stale_internal_login_form_cannot_replace_the_active_internal_user(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'password']);
        $tu = User::factory()->tu()->create(['password' => 'password']);
        $login = $this->request('GET', route('login'));
        $token = $this->csrfToken($login);

        $this->request('POST', route('login.attempt'), [
            '_token' => $token,
            'username' => $admin->username,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->request('POST', route('login.attempt'), [
            '_token' => $token,
            'username' => $tu->username,
            'password' => 'password',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('form');

        $dashboard = $this->request('GET', route('admin.dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard.admin');

        $this->request('POST', route('login.attempt'), [
            '_token' => $this->csrfToken($dashboard),
            'username' => $tu->username,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'web');
        $this->assertGuest('siswa');
    }

    public function test_internal_and_student_sessions_are_independent(): void
    {
        [$tu, $akunSiswa] = $this->buatAkun();
        $loginInternal = $this->request('GET', route('login'));
        $loginSiswa = $this->request('GET', route('siswa.login'));

        $this->assertNotSame(config('session.cookies.web'), config('session.cookies.siswa'));
        $this->assertArrayHasKey(config('session.cookies.web'), $this->cookies);
        $this->assertArrayHasKey(config('session.cookies.siswa'), $this->cookies);

        $this->request('POST', route('login.attempt'), [
            '_token' => $this->csrfToken($loginInternal),
            'username' => $tu->username,
            'password' => 'password',
        ])->assertRedirect(route('tu.dashboard'));

        $this->request('POST', route('siswa.login.attempt'), [
            '_token' => $this->csrfToken($loginSiswa),
            'username' => $akunSiswa->username,
            'password' => 'password',
        ])->assertRedirect(route('siswa.status'));

        $internalDashboard = $this->request('GET', route('tu.dashboard'))->assertOk();
        $this->request('GET', route('siswa.status'))->assertOk();

        $this->request('POST', route('logout'), [
            '_token' => $this->csrfToken($internalDashboard),
        ])->assertRedirect(route('login'));
        $this->request('GET', route('siswa.status'))->assertOk();

        $loginInternal = $this->request('GET', route('login'));
        $this->request('POST', route('login.attempt'), [
            '_token' => $this->csrfToken($loginInternal),
            'username' => $tu->username,
            'password' => 'password',
        ])->assertRedirect(route('tu.dashboard'));

        $halamanSiswa = $this->request('GET', route('siswa.status'));
        $this->request('POST', route('siswa.logout'), [
            '_token' => $this->csrfToken($halamanSiswa),
        ])->assertRedirect(route('siswa.login'));
        $this->request('GET', route('tu.dashboard'))->assertOk();
    }

    public function test_student_session_cannot_access_internal_routes(): void
    {
        [, $akunSiswa] = $this->buatAkun();
        $loginSiswa = $this->request('GET', route('siswa.login'));

        $this->request('POST', route('siswa.login.attempt'), [
            '_token' => $this->csrfToken($loginSiswa),
            'username' => $akunSiswa->username,
            'password' => 'password',
        ])->assertRedirect(route('siswa.status'));

        $this->request('GET', route('tu.dashboard'))->assertRedirect(route('login'));
        $this->request('GET', route('siswa.status'))->assertOk();
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function buatAkun(): array
    {
        $tu = User::factory()->tu()->create(['password' => 'password']);
        $siswa = Siswa::create([
            'nipd' => fake()->unique()->numerify('ISO-####'),
            'nama_siswa' => 'Siswa Session',
            'jenis_kelamin' => 'L',
            'angkatan' => 2044,
            'status_siswa' => 'aktif',
        ]);
        $akunSiswa = User::create([
            'nama' => $siswa->nama_siswa,
            'username' => fake()->unique()->userName(),
            'password' => 'password',
            'role' => User::ROLE_SISWA,
            'id_siswa' => $siswa->id_siswa,
        ]);

        return [$tu, $akunSiswa];
    }

    private function request(string $method, string $url, array $data = []): TestResponse
    {
        $response = $this->call($method, $url, $data, $this->cookies);

        foreach (config('session.cookies') as $cookieName) {
            if ($cookie = $response->getCookie($cookieName, false)) {
                $this->cookies[$cookieName] = $cookie->getValue();
            }
        }

        return $response;
    }

    private function csrfToken(TestResponse $response): string
    {
        preg_match('/name="_token" value="([^"]+)"/', $response->getContent(), $matches);

        $this->assertArrayHasKey(1, $matches, 'Form tidak memiliki token CSRF.');

        return $matches[1];
    }
}

class EnforceCsrfToken extends ValidateCsrfToken
{
    protected function runningUnitTests(): bool
    {
        return false;
    }
}
