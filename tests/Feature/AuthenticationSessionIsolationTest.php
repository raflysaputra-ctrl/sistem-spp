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

    public function test_invalid_internal_csrf_token_is_rejected_without_showing_a_419_page(): void
    {
        [$petugas] = $this->buatAkun();
        $login = $this->request('GET', route('portal.tu.login'));

        $this->request('POST', route('login.attempt'), [
            'username' => $petugas->username,
            'password' => 'password',
        ])->assertRedirect(route('login'));

        $this->assertGuest('tu');

        $this->request('POST', route('portal.tu.login.attempt'), [
            '_token' => $this->csrfToken($login),
            'username' => $petugas->username,
            'password' => 'password',
        ])->assertRedirect(route('portal.tu.home'));
    }

    public function test_preopened_internal_login_form_does_not_replace_the_active_admin_session(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => fake()->unique()->userName(),
            'password' => 'password',
        ]);
        $tu = User::factory()->tu()->create([
            'username' => fake()->unique()->userName(),
            'password' => 'password',
        ]);
        $adminForm = $this->request('GET', route('portal.admin.login'));
        $staleToken = $this->csrfToken($adminForm);

        $this->request('POST', route('portal.admin.login.attempt'), [
            '_token' => $staleToken,
            'username' => $admin->username,
            'password' => 'password',
        ])->assertRedirect(route('portal.admin.home'));

        $this->request('POST', route('portal.admin.login.attempt'), [
            '_token' => $staleToken,
            'username' => $tu->username,
            'password' => 'password',
        ])->assertRedirect(route('portal.admin.login'));

        $this->request('GET', route('portal.admin.home'))
            ->assertOk()
            ->assertViewIs('dashboard.admin');
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_internal_user_can_log_in_to_admin_without_ending_the_tu_session(): void
    {
        $tu = User::factory()->tu()->create([
            'nama' => 'Petugas TU',
            'username' => fake()->unique()->userName(),
            'password' => 'password',
        ]);
        $admin = User::factory()->admin()->create([
            'nama' => 'Admin Keuangan',
            'username' => fake()->unique()->userName(),
            'password' => 'password',
        ]);
        $tuLogin = $this->request('GET', route('portal.tu.login'));

        $this->request('POST', route('portal.tu.login.attempt'), [
            '_token' => $this->csrfToken($tuLogin),
            'username' => $tu->username,
            'password' => 'password',
        ])->assertRedirect(route('portal.tu.home'));

        $adminLogin = $this->request('GET', route('portal.admin.login'))
            ->assertOk()
            ->assertDontSeeText('Akun aktif:');

        $this->request('POST', route('portal.admin.login.attempt'), [
            '_token' => $this->csrfToken($adminLogin),
            'username' => $admin->username,
            'password' => 'password',
        ])->assertRedirect(route('portal.admin.home'));

        $this->request('GET', route('portal.admin.home'))
            ->assertOk()
            ->assertViewIs('dashboard.admin')
            ->assertSeeText('Selamat datang, Admin Keuangan.');
        $this->request('GET', route('portal.tu.home'))
            ->assertOk()
            ->assertViewIs('dashboard.tu')
            ->assertSeeText('Selamat datang, Petugas TU.');
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertAuthenticatedAs($tu, 'tu');
    }

    public function test_stale_internal_logout_form_redirects_to_login_instead_of_showing_419(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => fake()->unique()->userName(),
            'password' => 'password',
        ]);
        $login = $this->request('GET', route('portal.admin.login'));

        $this->request('POST', route('portal.admin.login.attempt'), [
            '_token' => $this->csrfToken($login),
            'username' => $admin->username,
            'password' => 'password',
        ])->assertRedirect(route('portal.admin.home'));

        $dashboard = $this->request('GET', route('portal.admin.home'));
        $logoutToken = $this->csrfToken($dashboard);

        $this->request('POST', route('portal.admin.logout'), [
            '_token' => $logoutToken,
        ])->assertRedirect(route('portal.admin.login'));

        $this->request('POST', route('portal.admin.logout'), [
            '_token' => $logoutToken,
        ])->assertRedirect(route('portal.admin.login'));

        $this->assertGuest('admin');
    }

    public function test_preopened_login_forms_work_when_tu_logs_in_first_and_logouts_are_independent(): void
    {
        $this->assertIsolatedFlow('tu');
    }

    public function test_preopened_login_forms_work_when_student_logs_in_first_and_logouts_are_independent(): void
    {
        $this->assertIsolatedFlow('siswa');
    }

    public function test_admin_tu_kepsek_and_student_can_log_in_and_out_independently_in_one_browser(): void
    {
        [$tu, $siswa] = $this->buatAkun();
        $admin = User::factory()->admin()->create(['password' => 'password']);
        $kepsek = User::factory()->kepalaSekolah()->create(['password' => 'password']);
        $users = ['admin' => $admin, 'tu' => $tu, 'kepsek' => $kepsek];
        $tokens = [];

        foreach ($users as $portal => $user) {
            $form = $this->request('GET', route("portal.{$portal}.login"))->assertOk();
            $tokens[$portal] = $this->csrfToken($form);
            $this->assertArrayHasKey(config("session.cookies.{$portal}"), $this->cookies);
        }

        $studentForm = $this->request('GET', route('siswa.login'))->assertOk();
        $tokens['siswa'] = $this->csrfToken($studentForm);
        $this->assertCount(4, array_unique($tokens));

        foreach ($users as $portal => $user) {
            $this->request('POST', route("portal.{$portal}.login.attempt"), [
                '_token' => $tokens[$portal],
                'username' => $user->username,
                'password' => 'password',
            ])->assertRedirect(route("portal.{$portal}.home"));
        }

        $this->request('POST', route('siswa.login.attempt'), [
            '_token' => $tokens['siswa'],
            'username' => $siswa->username,
            'password' => 'password',
        ])->assertRedirect(route('siswa.status'));

        foreach (['admin' => 'dashboard.admin', 'tu' => 'dashboard.tu', 'kepsek' => 'dashboard.kepala-sekolah'] as $portal => $view) {
            $page = $this->request('GET', route("portal.{$portal}.home"))
                ->assertOk()
                ->assertViewIs($view)
                ->assertSeeText($users[$portal]->nama);
            $tokens[$portal] = $this->csrfToken($page);
        }

        $this->request('GET', route('siswa.status'))->assertOk();

        $this->request('POST', route('portal.tu.logout'), [
            '_token' => $tokens['tu'],
        ])->assertRedirect(route('portal.tu.login'));

        $this->request('GET', route('portal.tu.home'))
            ->assertRedirect(route('portal.tu.login'));
        $this->request('GET', route('portal.admin.home'))->assertOk()->assertViewIs('dashboard.admin');
        $this->request('GET', route('portal.kepsek.home'))->assertOk()->assertViewIs('dashboard.kepala-sekolah');
        $this->request('GET', route('siswa.status'))->assertOk();

        $this->request('POST', route('portal.admin.logout'), [
            '_token' => $tokens['admin'],
        ])->assertRedirect(route('portal.admin.login'));

        $this->request('GET', route('portal.admin.home'))
            ->assertRedirect(route('portal.admin.login'));
        $this->request('GET', route('portal.kepsek.home'))->assertOk();
        $this->request('GET', route('siswa.status'))->assertOk();
    }

    public function test_wrong_role_or_wrong_portal_token_cannot_open_another_portal(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'password']);
        $adminForm = $this->request('GET', route('portal.admin.login'));
        $tuForm = $this->request('GET', route('portal.tu.login'));

        $this->request('POST', route('portal.tu.login.attempt'), [
            '_token' => $this->csrfToken($tuForm),
            'username' => $admin->username,
            'password' => 'password',
        ])->assertSessionHasErrors('username');

        $this->request('POST', route('portal.admin.login.attempt'), [
            '_token' => $this->csrfToken($adminForm),
            'username' => $admin->username,
            'password' => 'password',
        ])->assertRedirect(route('portal.admin.home'));

        $this->request('GET', route('portal.tu.home'))
            ->assertRedirect(route('portal.tu.login'));

        $this->request('POST', route('portal.admin.logout'), [
            '_token' => $this->csrfToken($tuForm),
        ])->assertRedirect(route('portal.admin.login'));

        $this->request('GET', route('portal.admin.home'))->assertOk();
    }

    public function test_portal_links_and_detail_routes_stay_inside_their_own_session_context(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'password']);
        $tu = User::factory()->tu()->create(['password' => 'password']);
        $kepsek = User::factory()->kepalaSekolah()->create(['password' => 'password']);

        foreach (['admin' => $admin, 'tu' => $tu, 'kepsek' => $kepsek] as $portal => $user) {
            $form = $this->request('GET', route("portal.{$portal}.login"));
            $this->request('POST', route("portal.{$portal}.login.attempt"), [
                '_token' => $this->csrfToken($form),
                'username' => $user->username,
                'password' => 'password',
            ])->assertRedirect(route("portal.{$portal}.home"));
        }

        $siswa = Siswa::create([
            'nipd' => 'ISO-DETAIL',
            'nama_siswa' => 'Siswa Detail Session',
            'jenis_kelamin' => 'L',
            'angkatan' => 2044,
            'status_siswa' => 'aktif',
        ]);

        $this->request('GET', route('portal.admin.home'))
            ->assertSee('href="'.route('portal.admin.master.siswa.index').'"', false)
            ->assertSee('href="'.route('portal.admin.rekap-pembayaran.index').'"', false)
            ->assertSee('action="'.route('portal.admin.logout').'"', false);

        $this->request('GET', route('portal.admin.master.siswa.edit', $siswa))
            ->assertOk()
            ->assertSeeText('Siswa Detail Session');
        $createJurusan = $this->request('GET', route('portal.admin.master.jurusan.create'))->assertOk();
        $this->request('POST', route('portal.admin.master.jurusan.store'), [
            '_token' => $this->csrfToken($createJurusan),
            'kode_jurusan' => 'ISX',
            'nama_jurusan' => 'Isolasi Session',
        ])->assertRedirect(route('portal.admin.master.jurusan.index'));
        $this->request('GET', route('portal.admin.rekap-pembayaran.index'))->assertOk();
        $this->request('GET', route('portal.tu.pembayaran.index'))->assertOk();
        $this->request('GET', route('portal.kepsek.rekap-pembayaran.index'))->assertOk();

        $this->request('GET', route('portal.tu.master.siswa.index'))->assertForbidden();
        $this->request('GET', route('portal.kepsek.master.siswa.index'))->assertForbidden();
        $this->request('GET', route('portal.admin.pembayaran.index'))->assertForbidden();

        $tuPage = $this->request('GET', route('portal.tu.home'));
        $this->request('POST', route('portal.admin.master.siswa.store'), [
            '_token' => $this->csrfToken($tuPage),
        ])->assertStatus(419);

        $this->request('GET', route('portal.admin.home'))->assertOk()->assertViewIs('dashboard.admin');
    }

    private function assertIsolatedFlow(string $first): void
    {
        [$petugas, $akunSiswa] = $this->buatAkun();
        $tuForm = $this->request('GET', route('portal.tu.login'));
        $tuToken = $this->csrfToken($tuForm);
        $siswaForm = $this->request('GET', route('siswa.login'));
        $siswaToken = $this->csrfToken($siswaForm);

        $this->assertNotSame(config('session.cookies.tu'), config('session.cookies.siswa'));
        $this->assertNotSame($tuToken, $siswaToken);
        $this->assertArrayHasKey(config('session.cookies.tu'), $this->cookies);
        $this->assertArrayHasKey(config('session.cookies.siswa'), $this->cookies);

        $logins = [
            'tu' => [route('portal.tu.login.attempt'), route('portal.tu.home'), $tuToken, $petugas],
            'siswa' => [route('siswa.login.attempt'), route('siswa.status'), $siswaToken, $akunSiswa],
        ];

        foreach ([$first, $first === 'tu' ? 'siswa' : 'tu'] as $guard) {
            [$url, $redirect, $token, $user] = $logins[$guard];
            $this->request('POST', $url, [
                '_token' => $token,
                'username' => $user->username,
                'password' => 'password',
            ])->assertRedirect($redirect);
        }

        $this->request('GET', route('portal.tu.home'))->assertOk();
        $this->request('GET', route('siswa.status'))->assertOk();

        $tuPage = $this->request('GET', route('portal.tu.home'));
        $this->request('POST', route('portal.tu.logout'), [
            '_token' => $this->csrfToken($tuPage),
        ])->assertRedirect(route('portal.tu.login'));
        $this->request('GET', route('siswa.status'))->assertOk();

        $tuLogin = $this->request('GET', route('portal.tu.login'));
        $this->request('POST', route('portal.tu.login.attempt'), [
            '_token' => $this->csrfToken($tuLogin),
            'username' => $petugas->username,
            'password' => 'password',
        ])->assertRedirect(route('portal.tu.home'));

        $siswaPage = $this->request('GET', route('siswa.status'));
        $this->request('POST', route('siswa.logout'), [
            '_token' => $this->csrfToken($siswaPage),
        ])->assertRedirect(route('siswa.login'));
        $this->request('GET', route('portal.tu.home'))->assertOk();
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
