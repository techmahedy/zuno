<?php

namespace Zuno;

use Zuno\Support\Route;
use Zuno\Middleware\Middleware;
use Zuno\Http\Response;
use Zuno\Http\Request;
use Zuno\Http\Exceptions\HttpException;
use Zuno\DI\Container;
use Zuno\Config\Config;
use App\Providers\AppServiceProvider;
use Zuno\Middleware\Contracts\Middleware as ContractsMiddleware;

class Application extends AppServiceProvider
{
    /**
     * @var Route
     */
    public Route $route;

    /**
     * @var Request
     */
    public Request $request;

    /**
     * @var Container
     */
    protected Container $container;

    /**
     * @var Middleware
     */
    protected Middleware $middleware;

    /**
     * @var array<string>
     */
    private $globalMiddlewares = [];

    /**
     * Constructs the Application instance.
     * Initializes the dependency resolver, route handler, and middleware handler.
     */
    public function __construct()
    {
        $this->middleware = new Middleware();
        $this->container = new Container();
        $this->route = new Route();
        $this->request = request();

        // Bootstraping application services
        $this->register();

        // Loading application configuration files
        if (!file_exists(storage_path('cache/config.php'))) {
            Config::initialize();
            Config::loadFromCache();
        }
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
        $kernel = new $kernel;
        foreach ($kernel->middleware as $middlewareClass) {
            $this->globalMiddlewares[] = $middlewareClass;
        }
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
    public function send(): Response
    {
        $request = $this->request;
        $finalHandler = function ($request) {
            return new Response();
        };

        foreach ($this->globalMiddlewares as $middlewareClass) {
            $middlewareInstance = new $middlewareClass();
            if ($middlewareInstance instanceof ContractsMiddleware) {
                $finalHandler = function (Request $request) use ($middlewareInstance, $finalHandler) {
                    return $middlewareInstance($request, $finalHandler);
                };
            } else {
                throw new \Exception("Unresolved dependency $middlewareClass", 1);
            }
        }

        return $this->middleware->handle($request, $finalHandler);
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
        return $this->middleware->applyMiddleware($middleware);
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
            $this->dispatch();
            $response = $this->route->resolve(
                $this->container,
                $this->request,
                $this->middleware
            );

            if ($response instanceof \Zuno\Http\Response) {
                $response->send();
            } else {
                echo $response;
            }
        } catch (HttpException $exception) {
            Response::dispatchHttpException($exception);
        }
    }

    private function dispatch(): void
    {
        require base_path() . '/routes/web.php';
    }
}
