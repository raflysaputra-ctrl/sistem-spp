<?php

namespace App\Http\Middleware;

use App\Session\PortalSessionManager;
use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UsePortalSession
{
    public function __construct(
        private readonly Application $app,
        private readonly PortalSessionManager $sessions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $portal = $request->segment(1);
        $context = $portal === 'siswa' ? 'siswa' : 'web';

        $this->selectContext($context);
        Auth::shouldUse($context);

        return $next($request);
    }

    private function selectContext(string $context): void
    {
        config(['session.cookie' => config("session.cookies.{$context}")]);
        $this->sessions->useContext($context);
        $this->app->forgetInstance('session.store');
    }
}
