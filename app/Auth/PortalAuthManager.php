<?php

namespace App\Auth;

use App\Session\PortalSessionManager;
use Illuminate\Auth\AuthManager;

class PortalAuthManager extends AuthManager
{
    public function createSessionDriver($name, $config)
    {
        $sessions = $this->app->make(PortalSessionManager::class);
        $context = $name === 'siswa' ? 'siswa' : 'web';

        $this->app->instance('session.store', $sessions->driverForContext($context));

        try {
            return parent::createSessionDriver($name, $config);
        } finally {
            $this->app->forgetInstance('session.store');
        }
    }
}
