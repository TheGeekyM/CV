<?php namespace Geeky\CV;

use Illuminate\Support\ServiceProvider;

class ParserServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // register CVParesr controller
        $this->app->make(Parser::class);
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
