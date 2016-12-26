<?php

namespace Exp\Discuss;

use Illuminate\Support\ServiceProvider;

class DiscussServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        include __DIR__ . '/routers.php';
        $this->loadViewsFrom(__DIR__ . '/Views', 'forum');
        $this->loadMigrationsFrom(__DIR__.'/Migrations','discuss');
        $this->publishes([__DIR__.'/asset'=>public_path('forum')],'public');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app['discuss'] = $this->app->share(function ($app) {
            return new Discuss;
        });
    }
}