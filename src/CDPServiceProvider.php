<?php

namespace Cellphones\Cdp;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class CDPServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/cdp.php', 'cdp');

        $this->publishConfig();

        // $this->loadViewsFrom(__DIR__.'/resources/views', 'customers');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');


    }

    /**
     * Register the package routes.
     *
     * @return void
     */


    /**
     * Get route group configuration array.
     *
     * @return array
     */


    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Publish Config
     *
     * @return void
     */
    public function publishConfig()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/cdp.php' => config_path('cdp.php'),
            ], 'config');
        }
    }
}
