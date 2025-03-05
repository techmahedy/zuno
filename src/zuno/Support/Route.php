<?php

namespace Zuno\Support;

use Zuno\Middleware\Middleware;
use Zuno\Http\Response;
use Zuno\Http\Request;
use App\Http\Kernel;
use Zuno\DI\Container;

class Route extends Kernel
{
    /**
     * Holds the registered routes.
     *
     * @var array
     */
    protected static array $routes = [];

    /**
     * Stores URL parameters extracted from routes.
     *
     * @var array
     */
    protected array $urlParams = [];

    /**
     * The current request instance.
     *
     * @var Request
     */
    public Request $request;

    /**
     * Holds the registered named routes.
     *
     * @var array<string, string>
     */
    protected static array $namedRoutes = [];

    /**
     * @var string|null The path of the current route being defined.
     */
    protected ?string $currentRoutePath = null;

    /**
     * @var array<string> The middleware keys for the current route.
     */
    protected static array $routeMiddlewares = [];

    /**
     * Constructor to initialize the request property.
     *
     * @param Request $request The request instance.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Magic method to handle static method calls for 'get' and 'post' route registration.
     *
     * @param string $name The method name.
     * @param array $arguments The method arguments.
     * @return Route
     * @throws \Exception If method name is not 'get' or 'post'.
     */
    public static function __callStatic($name, $arguments): Route
    {
        return match ($name) {
            'get' => (new Route(new Request))->getRoute($arguments[0], $arguments[1]),
            'post' => (new Route(new Request))->postRoute($arguments[0], $arguments[1]),
            default => throw new \Exception($name . ' method not found', true)
        };
    }

    /**
     * Registers a GET route with a callback.
     *
     * @param string $path The route path.
     * @param callable $callback The callback for the route.
     * @return Route
     */
    public function getRoute($path, $callback): Route
    {
        self::$routes['get'][$path] = $callback;
        $this->currentRoutePath = $path;

        return $this;
    }

    /**
     * Registers a POST route with a callback.
     *
     * @param string $path The route path.
     * @param callable $callback The callback for the route.
     * @return Route
     */
    public function postRoute($path, $callback): Route
    {
        self::$routes['post'][$path] = $callback;
        $this->currentRoutePath = $path;

        return $this;
    }

    /**
     * Assigns a name to the last registered route.
     *
     * @param string $name The name for the route.
     * @return Route
     */
    public function name(string $name): Route
    {
        if ($this->currentRoutePath) {
            self::$namedRoutes[$name] = $this->currentRoutePath;
        }

        return $this;
    }

    /**
     * Generates a URL for a named route.
     *
     * @param string $name The route name.
     * @param array $params The parameters for the route.
     * @return string|null The generated URL or null if the route doesn't exist.
     */
    public static function route(string $name, mixed $params = []): ?string
    {
        if (!isset(self::$namedRoutes[$name])) {
            return null;
        }

        $route = self::$namedRoutes[$name];

        // If $params is not an array, convert it into an associative array
        if (!is_array($params)) {
            // If the route contains only one parameter placeholder, use 0 as the key
            if (preg_match('/\{(\w+)(:[^}]+)?}/', $route, $matches)) {
                $params = [$matches[1] => $params];
            } else {
                $params = [$params]; // Fallback in case there's no named parameter
            }
        }

        // Replace route parameters with actual values
        foreach ($params as $key => $value) {
            $route = preg_replace('/\{' . $key . '(:[^}]+)?}/', $value, $route, 1);
        }

        return $route;
    }

    /**
     * Applies middleware to the route.
     *
     * @param string|array $key The middleware key.
     * @return Route
     * @throws \Exception If the middleware is not defined.
     */
    public function middleware(string|array $keys): Route
    {
        if ($this->currentRoutePath) {
            foreach ((array) $keys as $key) {
                self::$routeMiddlewares[$this->currentRoutePath] = (array) $keys;
            }
        }

        return $this;
    }

    /**
     * Retrieves the callback for the current route based on the request method and path.
     *
     * @return mixed The route callback or false if not found.
     */
    public function getCallback(): mixed
    {
        $method = $this->request->getMethod();
        $url = $this->request->getPath();
        $routes = self::$routes[$method] ?? [];
        $routeParams = false;

        // Start iterating registed routes
        foreach ($routes as $route => $callback) {
            // Trim slashes
            $routeNames = [];

            if (!$route) {
                continue;
            }

            // Find all route names from route and save in $routeNames
            if (preg_match_all('/\{(\w+)(:[^}]+)?}/', $route, $matches)) {
                $routeNames = $matches[1];
            }

            // Convert route name into regex pattern
            $routeRegex = "@^" . preg_replace_callback('/\{\w+(:([^}]+))?}/', fn($m) => isset($m[2]) ? "({$m[2]})" : '(\w+)', $route) . "$@";

            // Test and match current route against $routeRegex
            if (preg_match_all($routeRegex, $url, $valueMatches)) {
                $values = [];
                $counter = count($valueMatches);
                for ($i = 1; $i < $counter; $i++) {
                    $values[] = $valueMatches[$i][0];
                }
                $routeParams = array_combine($routeNames, $values);

                $this->request->setRouteParams($routeParams);
                return $callback;
            }
        }
        return false;
    }

    /**
     * Get the current route middleware
     * @return array|null
     */
    public function getCurrentRouteMiddleware(): ?array
    {
        $url = $this->request->getPath();
        $method = $this->request->getMethod();
        $routes = self::$routes[$method] ?? [];

        foreach ($routes as $route => $callback) {
            $routeRegex = "@^" . preg_replace('/\{(\w+)(:[^}]+)?}/', '([^/]+)', $route) . "$@";
            if (preg_match($routeRegex, $url)) {
                return self::$routeMiddlewares[$route] ?? null;
            }
        }

        return null;
    }

    /**
     * Applies middleware to the route
     * @return Route
     */
    private function applyRouteMiddleware(): Route
    {
        foreach ($this->getCurrentRouteMiddleware() ?? [] as $key) {
            [$name, $params] = array_pad(explode(':', $key, 2), 2, null);
            $params = $params ? explode(',', $params) : [];

            if (!isset($this->routeMiddleware[$name])) {
                throw new \Exception("Middleware [$name] is not defined");
            }

            $middlewareClass = $this->routeMiddleware[$name];
            $middleware = new $middlewareClass();
            $request = new Request();

            $next = fn(Request $request) => new Response();
            $middleware->handle($request, $next, ...$params);
        }

        return $this;
    }

    private function applyWebMiddleware(): void
    {
        foreach ($this->middleware ?? [] as $middlewareClass) {
            $middleware = new $middlewareClass();
            $next = fn(Request $request) => new Response();
            $middleware($this->request, $next);
        }
    }

    /**
     * Resolves and executes the route callback with middleware and dependencies.
     *
     * @param Container $container The container for resolving dependencies.
     * @return mixed The result of the callback execution.
     * @throws \ReflectionException If there is an issue with reflection.
     * @throws \Exception If the route callback is not defined or dependency resolution fails.
     */
    public function resolve(Container $container): mixed
    {
        $this->applyWebMiddleware();

        $this->applyRouteMiddleware();

        $callback = $this->getCallback();
        if (!$callback) abort(404);

        $routeParams = $this->request->getRouteParams();

        if (is_array($callback)) {
            return $this->resolveControllerAction($callback, $container, $routeParams);
        }

        return call_user_func($callback, ...array_filter($this->urlParams));
    }

    /**
     * Resolves and executes a controller action with dependencies.
     *
     * @param array $callback The controller callback (e.g., [Controller::class, 'action']).
     * @param Container $container The container for resolving dependencies.
     * @param array $routeParams The route parameters.
     * @return mixed The result of the controller action execution.
     * @throws \ReflectionException If there is an issue with reflection.
     * @throws \Exception If dependency resolution fails.
     */
    private function resolveControllerAction(array $callback, $container, array $routeParams): mixed
    {
        [$controllerClass, $actionMethod] = $callback;
        $reflector = new \ReflectionClass($controllerClass);

        $constructorDependencies = $this->resolveConstructorDependencies($reflector, $container, $routeParams);
        $controllerInstance = new $controllerClass(...$constructorDependencies);

        $actionDependencies = $this->resolveActionDependencies($reflector, $actionMethod, $container, $routeParams);

        return call_user_func([$controllerInstance, $actionMethod], ...$actionDependencies);
    }

    /**
     * Resolves constructor dependencies for a controller.
     *
     * @param \ReflectionClass $reflector The reflection class of the controller.
     * @param Container $container The container for resolving dependencies.
     * @param array $routeParams The route parameters.
     * @return array The resolved constructor dependencies.
     * @throws \Exception If dependency resolution fails.
     */
    private function resolveConstructorDependencies(
        \ReflectionClass $reflector,
        $container,
        array $routeParams
    ): array {
        $constructor = $reflector->getConstructor();
        if (!$constructor) {
            return [];
        }

        return $this->resolveParameters($constructor->getParameters(), $container, $routeParams);
    }

    /**
     * Resolves action dependencies for a controller method.
     *
     * @param \ReflectionClass $reflector The reflection class of the controller.
     * @param string $actionMethod The name of the action method.
     * @param Container $container The container for resolving dependencies.
     * @param array $routeParams The route parameters.
     * @return array The resolved action dependencies.
     * @throws \ReflectionException If there is an issue with reflection.
     * @throws \Exception If dependency resolution fails.
     */
    private function resolveActionDependencies(
        \ReflectionClass $reflector,
        string $actionMethod,
        $container,
        array $routeParams
    ): array {
        $method = $reflector->getMethod($actionMethod);

        return $this->resolveParameters($method->getParameters(), $container, $routeParams);
    }

    /**
     * Resolves parameters for a method or constructor.
     *
     * @param array $parameters The parameters to resolve.
     * @param Container $container The container for resolving dependencies.
     * @param array $routeParams The route parameters.
     * @return array The resolved parameters.
     * @throws \Exception If dependency resolution fails.
     */
    private function resolveParameters(array $parameters, $container, array $routeParams): array
    {
        $dependencies = [];
        foreach ($parameters as $parameter) {
            $paramName = $parameter->getName();
            $paramType = $parameter->getType();

            if ($paramType && !$paramType->isBuiltin()) {
                $resolvedClass = $paramType->getName();
                if (is_subclass_of($resolvedClass, \Illuminate\Database\Eloquent\Model::class)) {
                    $modelId = $routeParams[$paramName] ?? null;
                    $dependencies[] = $modelId ? $resolvedClass::findOrFail($modelId) : $container->get($resolvedClass);
                } else {
                    $dependencies[] = $container->get($resolvedClass);
                }
            } elseif (isset($routeParams[$paramName])) {
                $dependencies[] = $routeParams[$paramName];
            } elseif ($parameter->isOptional()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                throw new \Exception("Cannot resolve parameter '$paramName' in route callback");
            }
        }
        return $dependencies;
    }
}
