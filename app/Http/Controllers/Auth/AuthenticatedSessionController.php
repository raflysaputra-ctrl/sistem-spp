<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $siswaAktif = Auth::guard('siswa')->check();
        $csrfTokenSiswa = $siswaAktif ? $request->session()->token() : null;
        $request->authenticate();

        if ($csrfTokenSiswa) {
            // SessionGuard rotates the session and token during login. Keep the student's open form valid.
            $request->session()->put('_token', $csrfTokenSiswa);
        }

        return to_route('home');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $siswaAktif = Auth::guard('siswa')->check();
        Auth::guard('web')->logout();

        if ($siswaAktif) {
            // Keep the student's guard and CSRF token valid in the other open tab.
            $request->session()->migrate(true);
        } else {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return to_route('login');
    }
}
