<?php

namespace Zuno\Providers;

use Zuno\Application;

abstract class ServiceProvider
{
    /**
     * Create a new service provider instance.
     *
     * @param \Zuno\Application $app
     * @return void
     */
    public function __construct(protected Application $app) {}

    /**
     * Register bindings into the container.
     *
     * @return void
     */
    abstract public function register();

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    abstract public function boot();
}
