<?php

namespace Zuno\Providers;

trait CoreProviders
{
    /**
     * Loads the core service providers for the application.
     *
     * This method returns an array of core service provider classes that are essential
     * for the framework to function properly. These providers handle tasks such as
     * environment configuration, session management, facades, and routing.
     *
     * @return array
     *   An array of fully-qualified class names of core service providers.
     */
    protected function loadCoreProviders()
    {
        return [
            \Zuno\Providers\FacadeServiceProvider::class,
            \Zuno\Providers\EnvServiceProvider::class,
            \Zuno\Providers\SessionServiceProvider::class,
            \Zuno\Providers\RouteServiceProvider::class,
        ];
    }
}
