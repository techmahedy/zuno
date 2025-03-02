<?php

namespace Zuno;

use App\Http\Kernel;
use Zuno\Request;
use Zuno\Middleware\Middleware;

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
    public function applyRouteMiddleware(): Route
    {
        foreach ($this->getCurrentRouteMiddleware() as $key) {
            [$name, $params] = array_pad(explode(':', $key, 2), 2, null);
            $params = $params ? explode(',', $params) : [];

            if (!isset($this->routeMiddleware[$name])) {
                throw new \Exception("Middleware [$name] is not defined");
            }

            (new $this->routeMiddleware[$name])->handle(
                new Request,
                (new Middleware)->start,
                ...$params
            );
        }

        return $this;
    }

    /**
     * Resolves and executes the route callback with the middleware and dependencies.
     *
     * @param Middleware $middleware The middleware instance.
     * @param Container $container The container container for resolving dependencies.
     * @return mixed The result of the callback execution.
     * @throws \ReflectionException If there is an issue with reflection.
     * @throws \Exception If the route callback is not defined.
     */
    public function resolve(?Middleware $middleware, $container): mixed
    {
        // Process Route middleware
        $this->applyRouteMiddleware();

        // Get the callback (controller and method) for the route
        $callback = $this->getCallback();

        // Process Global middleware
        $middleware->handle($this->request);

        if (!$callback) {
            throw new \Exception("Route path " . '[' . $this->request->getPath() . ']' . " is not defined");
        }

        // Extract route parameters (e.g., from the URL)
        $routeParams = $this->request->getRouteParams();
        $resolveDependencies = [];

        if (is_array($callback)) {
            $controllerClass = $callback[0];
            $actionMethod = $callback[1];

            $reflector = new \ReflectionClass($controllerClass);

            // Resolve constructor dependencies
            $constructor = $reflector->getConstructor();
            $constructorDependencies = [];

            if ($constructor) {
                $constructorParameters = $constructor->getParameters();

                foreach ($constructorParameters as $parameter) {
                    $paramType = $parameter->getType();
                    $isBuiltin = $paramType && $paramType->isBuiltin();

                    if ($paramType && !$isBuiltin) {
                        $resolvedClass = $paramType->getName();

                        // Handle interfaces and resolve through the container
                        if (interface_exists($resolvedClass)) {
                            if (!$container->has($resolvedClass)) {
                                throw new \Exception("Cannot resolve interface '$resolvedClass'");
                            }
                            $constructorDependencies[] = $container->get($resolvedClass);
                        }
                        // Handle Eloquent models or other class dependencies
                        elseif (is_subclass_of($resolvedClass, \Illuminate\Database\Eloquent\Model::class)) {
                            $modelId = $routeParams[$parameter->getName()] ?? null;
                            if ($modelId) {
                                $constructorDependencies[] = $resolvedClass::findOrFail($modelId);
                            } else {
                                $constructorDependencies[] = $container->get($resolvedClass);
                            }
                        } else {
                            $constructorDependencies[] = $container->get($resolvedClass);
                        }
                    } else {
                        throw new \Exception("Cannot resolve built-in type for constructor parameter '{$parameter->getName()}'");
                    }
                }
            }

            // Instantiate the controller with constructor dependencies
            $controllerInstance = new $controllerClass(...$constructorDependencies);

            // Resolve method (action) parameters
            $method = $reflector->getMethod($actionMethod);
            $methodParameters = $method->getParameters();

            foreach ($methodParameters as $parameter) {
                $paramName = $parameter->getName();
                $paramType = $parameter->getType();
                $isBuiltin = $paramType && $paramType->isBuiltin();

                if ($paramType && !$isBuiltin) {
                    $resolvedClass = $paramType->getName();

                    // Handle models (e.g., User)
                    if (is_subclass_of($resolvedClass, \Illuminate\Database\Eloquent\Model::class)) {
                        // Check if route has a parameter (like an ID for the model)
                        $modelId = $routeParams[$paramName] ?? null;
                        if ($modelId) {
                            $resolveDependencies[] = $resolvedClass::findOrFail($modelId);
                        } else {
                            // Otherwise, resolve the model without an ID
                            $resolveDependencies[] = $container->get($resolvedClass);
                        }
                    } else {
                        // For other classes (like Request, etc.)
                        $resolveDependencies[] = $container->get($resolvedClass);
                    }
                } else if (isset($routeParams[$paramName])) {
                    // If the parameter is in the route parameters (like a scalar ID)
                    $resolveDependencies[] = $routeParams[$paramName];
                } else if ($parameter->isOptional()) {
                    // Handle optional parameters with default values
                    $resolveDependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new \Exception("Cannot resolve parameter '$paramName' in route callback");
                }
            }

            // Call the controller action with resolved dependencies
            return call_user_func([$controllerInstance, $actionMethod], ...$resolveDependencies);
        }

        return call_user_func($callback, ...array_filter($this->urlParams));
    }
}
