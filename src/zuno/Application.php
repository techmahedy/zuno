<?php

namespace Zuno;

use Zuno\Support\Route;
use Zuno\DI\Container;
use Zuno\Config\Config;
use App\Providers\AppServiceProvider;
use Zuno\Http\Exceptions\HttpException;
use Zuno\Http\Response;

final class Application extends AppServiceProvider
{
    /**
     * The route handler instance.
     *
     * This property holds an instance of the Route class responsible for routing.
     *
     * @var Route
     */
    public Route $route;

    /**
     * @var Container
     */
    protected Container $container;

    /**
     * Constructs the Application instance.
     *
     * Initializes the dependency resolver, route handler, and middleware handler.
     *
     * @param Route $route
     * @param Container $container
     */
    public function __construct(Route $route, Container $container)
    {
        $this->route = $route;
        $this->container = $container;

        // Bootstraping application services
        $this->register();

        // Loading application configuration files
        if (!file_exists(storage_path('cache/config.php'))) {
            Config::initialize();
            Config::loadFromCache();
        }
    }

    /**
     * Run the application and resolve the route.
     * Executes the application logic by resolving the route
     *
     * @return void
     * @throws \ReflectionException If there is an issue with reflection during route resolution.
     */
    public function run(): void
    {
        try {
            $response = $this->route->resolve($this->container);
            if ($response instanceof \Zuno\Http\Response) {
                $response->send();
            } else {
                echo $response;
            }
        } catch (HttpException $exception) {
            Response::dispatchHttpException($exception);
        }
    }

    public function dispatch(): void
    {
        require base_path() . '/routes/web.php';
    }
}
