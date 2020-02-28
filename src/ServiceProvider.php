<?php
namespace serhiikamolov\Laravel\JsonApi;

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
            \serhiikamolov\Laravel\JsonApi\Contracts\Response::class,
            \serhiikamolov\Laravel\JsonApi\Response\Response::class
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