<?php

namespace Geeky\CVParser;

use Illuminate\Support\ServiceProvider;

class CVParserServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // register CVParesr controller
        $this->app->make(CVParserController::class);
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
