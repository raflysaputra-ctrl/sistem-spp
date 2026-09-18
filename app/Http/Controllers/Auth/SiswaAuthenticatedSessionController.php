<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SiswaLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SiswaAuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.siswa-login');
    }

    public function store(SiswaLoginRequest $request): RedirectResponse
    {
        $petugasAktif = Auth::guard('web')->check();
        $csrfTokenPetugas = $petugasAktif ? $request->session()->token() : null;
        $request->authenticate();

        if ($csrfTokenPetugas) {
            // SessionGuard rotates the session and token during login. Keep the TU's open form valid.
            $request->session()->put('_token', $csrfTokenPetugas);
        }

        return to_route('siswa.status');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $petugasAktif = Auth::guard('web')->check();
        Auth::guard('siswa')->logout();

        if ($petugasAktif) {
            // Keep the Petugas TU guard and CSRF token valid in the other open tab.
            $request->session()->migrate(true);
        } else {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return to_route('siswa.login');
    }
}
