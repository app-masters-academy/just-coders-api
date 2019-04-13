<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RollbarStarterServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @param \Rollbar\RollbarLogger $rollbarLogger
     * @return void
     */
    public function boot(\Rollbar\RollbarLogger $rollbarLogger)
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}