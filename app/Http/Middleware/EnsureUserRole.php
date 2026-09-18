<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string $role, string $guard = 'web'): Response
    {
        $user = $request->user($guard);

        if (! $user || $user->role !== $role) {
            abort(403);
        }

        if ($role === 'siswa' && (! $user->siswa || $user->siswa->status_siswa !== 'aktif')) {
            abort(403);
        }

        return $next($request);
    }
}
