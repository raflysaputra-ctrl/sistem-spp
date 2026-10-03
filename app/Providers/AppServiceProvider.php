<?php

namespace App\Providers;

use App\Session\PortalSessionManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('session', fn ($app) => new PortalSessionManager($app));
        $this->app->alias('session', PortalSessionManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
