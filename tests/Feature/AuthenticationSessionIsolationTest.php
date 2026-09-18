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

    public function test_csrf_validation_is_really_enabled_for_the_http_flow(): void
    {
        [$petugas] = $this->buatAkun();
        $login = $this->request('GET', route('login'));

        $this->request('POST', route('login.attempt'), [
            'username' => $petugas->username,
            'password' => 'password',
        ])->assertStatus(419);

        $this->request('POST', route('login.attempt'), [
            '_token' => $this->csrfToken($login),
            'username' => $petugas->username,
            'password' => 'password',
        ])->assertRedirect(route('home'));
    }

    public function test_preopened_login_forms_work_when_tu_logs_in_first_and_logouts_are_independent(): void
    {
        $this->assertIsolatedFlow('web');
    }

    public function test_preopened_login_forms_work_when_student_logs_in_first_and_logouts_are_independent(): void
    {
        $this->assertIsolatedFlow('siswa');
    }

    private function assertIsolatedFlow(string $first): void
    {
        [$petugas, $akunSiswa] = $this->buatAkun();
        $tuForm = $this->request('GET', route('login'));
        $tuToken = $this->csrfToken($tuForm);
        $siswaForm = $this->request('GET', route('siswa.login'));
        $siswaToken = $this->csrfToken($siswaForm);

        $this->assertNotSame(config('session.cookies.web'), config('session.cookies.siswa'));
        $this->assertNotSame($tuToken, $siswaToken);
        $this->assertArrayHasKey(config('session.cookies.web'), $this->cookies);
        $this->assertArrayHasKey(config('session.cookies.siswa'), $this->cookies);

        $logins = [
            'web' => [route('login.attempt'), route('home'), $tuToken, $petugas],
            'siswa' => [route('siswa.login.attempt'), route('siswa.status'), $siswaToken, $akunSiswa],
        ];

        foreach ([$first, $first === 'web' ? 'siswa' : 'web'] as $guard) {
            [$url, $redirect, $token, $user] = $logins[$guard];
            $this->request('POST', $url, [
                '_token' => $token,
                'username' => $user->username,
                'password' => 'password',
            ])->assertRedirect($redirect);
        }

        $this->request('GET', route('home'))->assertOk();
        $this->request('GET', route('siswa.status'))->assertOk();

        $tuPage = $this->request('GET', route('home'));
        $this->request('POST', route('logout'), [
            '_token' => $this->csrfToken($tuPage),
        ])->assertRedirect(route('login'));
        $this->request('GET', route('siswa.status'))->assertOk();

        $tuLogin = $this->request('GET', route('login'));
        $this->request('POST', route('login.attempt'), [
            '_token' => $this->csrfToken($tuLogin),
            'username' => $petugas->username,
            'password' => 'password',
        ])->assertRedirect(route('home'));

        $siswaPage = $this->request('GET', route('siswa.status'));
        $this->request('POST', route('siswa.logout'), [
            '_token' => $this->csrfToken($siswaPage),
        ])->assertRedirect(route('siswa.login'));
        $this->request('GET', route('home'))->assertOk();
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function buatAkun(): array
    {
        $petugas = User::factory()->create([
            'username' => fake()->unique()->userName(),
            'password' => 'password',
        ]);
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
            'role' => 'siswa',
            'id_siswa' => $siswa->id_siswa,
        ]);

        return [$petugas, $akunSiswa];
    }

    private function request(string $method, string $url, array $data = []): TestResponse
    {
        $response = $this->call($method, $url, $data, $this->cookies);

        foreach ([config('session.cookies.web'), config('session.cookies.siswa')] as $cookieName) {
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
