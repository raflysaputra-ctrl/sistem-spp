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
        $request->authenticate();

        return to_route('siswa.status');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('siswa')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('siswa.login');
    }
}
