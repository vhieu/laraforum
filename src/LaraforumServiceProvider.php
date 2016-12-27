<?php

namespace Exp\Laraforum;

use Illuminate\Support\ServiceProvider;

class LaraforumServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        include __DIR__ . '/routers.php';
        $this->loadViewsFrom(__DIR__ . '/Views', 'forum');
        $this->loadMigrationsFrom(__DIR__.'/Migrations','laraforum');
        $this->publishes([__DIR__.'/resources/public'=>public_path('forum')],'public');
        $this->mergeConfigFrom(__DIR__.'/resources/config/laraforum.php','config');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app['laraforum'] = $this->app->share(function ($app) {
            return new Laraforum;
        });
    }
}