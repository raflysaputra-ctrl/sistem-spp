<?php

namespace App\Http\Middleware;

use App\Session\PortalSessionManager;
use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UsePortalSession
{
    public function __construct(
        private readonly Application $app,
        private readonly PortalSessionManager $sessions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $context = $request->is('siswa', 'siswa/*') ? 'siswa' : 'web';

        $this->selectContext($context);

        return $next($request);
    }

    private function selectContext(string $context): void
    {
        config(['session.cookie' => config("session.cookies.{$context}")]);
        $this->sessions->useContext($context);
        $this->app->forgetInstance('session.store');
    }
}
