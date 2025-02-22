<?php

namespace Zuno;

use Zuno\Route;
use Zuno\Middleware\Middleware;
use App\Providers\AppServiceProvider;
use Zuno\Middleware\Contracts\Middleware as ContractsMiddleware;
use App\Http\Kernel;

final class Application extends AppServiceProvider
{
    /**
     * The version of the application.
     *
     * @var string
     */
    public const VERSION = '2.0';

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

    /**
     * @var Container
     */
    protected Container $container;

    /**
     * @var array
     */
    private $globalMiddlewares = [];

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
     * Creates and initializes a new Kernel instance.
     *
     * This method instantiates a new `Kernel` object and assigns its middleware
     * to the `$globalMiddlewares` array for later processing.
     *
     * @param string $kernel The Kernel class name (not used in the method, might be unnecessary).
     * @return Application Returns the current Application instance.
     */
    public function make(string $kernel): Application
    {
        // Instantiate a new Kernel object
        $kernel = new Kernel;

        // Add the Kernel's middleware to the global middlewares list
        $this->globalMiddlewares[] = $kernel->middleware;

        // Return the current Application instance for method chaining
        return $this;
    }

    /**
     * Sends the request through all registered global middlewares.
     *
     * This method iterates through the `$globalMiddlewares` array and applies each middleware.
     * It ensures that every middleware implements the `ContractsMiddleware` interface before applying it.
     * If a middleware does not implement the required interface, an exception is thrown.
     *
     * @throws Exception If a middleware does not implement ContractsMiddleware.
     * @return void
     */
    public function send(): void
    {
        foreach (array_merge(...$this->globalMiddlewares) as $middleware) {
            $middlewareInstance = new $middleware();
            if ($middlewareInstance instanceof ContractsMiddleware) {
                $this->applyMiddleware($middlewareInstance);
            } else {
                // Throw an exception if the middleware is not implements by ContractsMiddleware
                throw new \Exception("Error Processing Request: Invalid Middleware", 1);
            }
        }
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
