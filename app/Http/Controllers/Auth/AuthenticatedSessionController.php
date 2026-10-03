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
    public function create(Request $request): View|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return $this->redirectToDashboard(Auth::guard('web')->user());
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return $this->redirectToDashboard(Auth::guard('web')->user());
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->regenerateToken();

        return to_route('login');
    }

    private function redirectToDashboard($user): RedirectResponse
    {
        return match ($user->role) {
            'admin' => redirect('/admin/dashboard'),
            'tu' => redirect('/tu/dashboard'),
            'kepala_sekolah' => redirect('/kepsek/dashboard'),
            default => redirect('/login'),
        };
    }
}
