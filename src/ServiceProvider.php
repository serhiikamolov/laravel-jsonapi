<?php
namespace serhiikamolov\Laravel\JsonApi;

use Illuminate\Support\Facades\DB;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // default response class
        $this->app->bind(
            \JsonAPI\Contracts\Response::class,
            \JsonAPI\Response\Response::class
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}