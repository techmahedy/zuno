<?php

namespace Zuno\Providers;

use Zuno\Support\Router;

class RouteServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('route', function () {
            return new Router();
        });
    }

    public function boot()
    {

        require base_path('routes/web.php');
    }
}
