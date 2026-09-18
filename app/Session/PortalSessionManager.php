<?php

namespace App\Session;

use Illuminate\Session\SessionManager;

class PortalSessionManager extends SessionManager
{
    private string $context = 'web';

    public function useContext(string $context): void
    {
        $this->context = $context;
    }

    public function driverForContext(string $context)
    {
        $previousContext = $this->context;
        $previousCookie = config('session.cookie');

        $this->context = $context;
        config(['session.cookie' => config("session.cookies.{$context}")]);

        try {
            return $this->driver();
        } finally {
            $this->context = $previousContext;
            config(['session.cookie' => $previousCookie]);
        }
    }

    public function driver($driver = null)
    {
        $driver ??= $this->getDefaultDriver();
        $key = $this->context.':'.$driver;

        return $this->drivers[$key] ??= $this->createDriver($driver);
    }
}
