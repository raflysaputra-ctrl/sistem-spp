<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string $roles, ?string $guard = null): Response
    {
        $user = $request->user($guard);
        $allowedRoles = explode('|', $roles);

        if (! $user || ! in_array($user->role, $allowedRoles, true)) {
            abort(403);
        }

        if ($user->role === 'siswa' && (! $user->siswa || $user->siswa->status_siswa !== 'aktif')) {
            abort(403);
        }

        return $next($request);
    }
}
