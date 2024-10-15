<?php

namespace Zuno;

use Zuno\Route;
use Zuno\Middleware\Middleware;
use App\Providers\AppServiceProvider;
use Zuno\Middleware\Contracts\Middleware as ContractsMiddleware;

final class Application extends AppServiceProvider
{
    /**
     * The version of the application.
     *
     * @var string
     */
    public const VERSION = '1.0.2';

    /**
     * Dependency resolver instance.
     *
     * This property holds the result of the dependency registration.
     *
     * @var mixed
     */
    public mixed $resolveDependency;

    /**
     * The route handler instance.
     *
     * This property holds an instance of the Route class responsible for routing.
     *
     * @var Route
     */
    public Route $route;

    /**
     * The middleware handler instance.
     *
     * This property holds an instance of the Middleware class responsible for handling middleware.
     *
     * @var Middleware
     */
    protected Middleware $middleware;

    protected Container $container;

    /**
     * Constructs the Application instance.
     *
     * Initializes the dependency resolver, route handler, and middleware handler.
     *
     * @param Route $route
     * @param Middleware $middleware
     * @param Container $container
     */
    public function __construct(Route $route, Middleware $middleware, Container $container)
    {
        // handle the registration of dependencies for the application.
        $this->resolveDependency = $this->register();

        // Assign the route handler instance.
        $this->route = $route;

        // Assign the middleware handler instance.
        $this->middleware = $middleware;

        // Assign service container
        $this->container = $container;
    }

    /**
     * Apply global middleware to the application.
     *
     * Delegates the application of middleware to the middleware handler.
     *
     * @param ContractsMiddleware $middleware The middleware to be applied.
     * @return mixed The result of applying the middleware.
     */
    public function applyMiddleware(ContractsMiddleware $middleware)
    {
        // Delegate the middleware application to the middleware handler.
        return $this->middleware->applyMiddleware($middleware);
    }

    /**
     * Run the application and resolve the route.
     *
     * Executes the application logic by resolving the route using the provided middleware
     * and dependency resolver.
     *
     * @return void
     * @throws \ReflectionException If there is an issue with reflection during route resolution.
     */
    public function run(): void
    {
        // Resolve and output the route result using the route handler,
        // middleware handler, and dependency resolver.
        echo $this->route->resolve(
            $this->middleware,
            $this->container
        );
    }
}
