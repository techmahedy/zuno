<?php

namespace Zuno\Providers;

use Zuno\Support\Router;

/**
 * RouteServiceProvider is responsible for registering and bootstrapping
 * the application's routing functionality.
 *
 * This class binds the Router instance into the service container and
 * loads the application's route definitions.
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * This method is called when the service provider is registered.
     * It binds the Router instance as a singleton into the service container,
     * ensuring that the same Router instance is reused throughout the application.
     *
     * @return void
     */
    public function register()
    {
        // Bind the 'route' key in the container to a singleton instance of Router.
        // This ensures that the same Router instance is returned whenever 'route' is resolved.
        $this->app->singleton('route', function () {
            return new Router();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * This method is called after all service providers have been registered.
     * It is used to load the application's route definitions from the `routes/web.php` file.
     *
     * @return void
     */
    public function boot()
    {
        // Load the web routes file.
        // This file typically contains route definitions for the application.
        require base_path('routes/web.php');
    }
}
