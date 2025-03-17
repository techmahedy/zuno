<?php

namespace Zuno\Support;

use Zuno\Middleware\Contracts\Middleware as ContractsMiddleware;
use Zuno\Http\Response;
use Zuno\Http\Request;
use Zuno\DI\Container;
use Ramsey\Collection\Collection;
use App\Http\Kernel;

class Router extends Kernel
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
     * Holds the registered named routes.
     *
     * @var array<string, string>
     */
    public static array $namedRoutes = [];

    /**
     * @var string|null The path of the current route being defined.
     */
    protected ?string $currentRoutePath = null;

    /**
     * @var string
     */
    protected string $currentRequestMethod;

    /**
     * @var array<string> The middleware keys for the current route.
     */
    protected static array $routeMiddlewares = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'PATCH' => [],
        'DELETE' => [],
    ];

    /**
     * Registers a GET route with a callback.
     *
     * @param string $path The route path.
     * @param callable $callback The callback for the route.
     * @return self
     */
    public function get($path, $callback): self
    {
        if (
            request()->_method === 'PUT' ||
            request()->_method === 'PATCH' ||
            request()->_method === 'DELETE'
        ) {
            $this->currentRequestMethod = request()->_method;
            self::$routes[request()->_method][$path] = $callback;
            $this->currentRoutePath = $path;

            return $this;
        }

        $this->currentRequestMethod = 'GET';
        self::$routes['GET'][$path] = $callback;
        $this->currentRoutePath = $path;

        return $this;
    }

    /**
     * Registers a POST route with a callback.
     *
     * @param string $path The route path.
     * @param callable $callback The callback for the route.
     * @return self
     */
    public function post($path, $callback): self
    {
        $this->currentRequestMethod = 'POST';
        self::$routes['POST'][$path] = $callback;
        $this->currentRoutePath = $path;

        return $this;
    }

    /**
     * Registers a PUT route with a callback.
     *
     * @param string $path The route path.
     * @param callable $callback The callback for the route.
     * @return self
     */
    public function put($path, $callback): self
    {
        $this->currentRequestMethod = 'PUT';
        self::$routes['PUT'][$path] = $callback;
        $this->currentRoutePath = $path;

        return $this;
    }

    /**
     * Registers a PATCH route with a callback.
     *
     * @param string $path The route path.
     * @param callable $callback The callback for the route.
     * @return self
     */
    public function patch($path, $callback): self
    {
        $this->currentRequestMethod = 'PATCH';
        self::$routes['PATCH'][$path] = $callback;
        $this->currentRoutePath = $path;

        return $this;
    }

    /**
     * Registers a DELETE route with a callback.
     *
     * @param string $path The route path.
     * @param callable $callback The callback for the route.
     * @return self
     */
    public function delete($path, $callback): self
    {
        $this->currentRequestMethod = 'DELETE';
        self::$routes['DELETE'][$path] = $callback;
        $this->currentRoutePath = $path;

        return $this;
    }

    /**
     * Assigns a name to the last registered route.
     *
     * @param string $name The name for the route.
     * @return self
     */
    public function name(string $name): self
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
    public function route(string $name, mixed $params = []): ?string
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
    public function middleware(string|array $keys): self
    {
        if ($this->currentRoutePath) {
            $method = $this->getCurrentRequestMethod();
            foreach ((array) $keys as $key) {
                self::$routeMiddlewares[$method][$this->currentRoutePath] = (array) $keys;
            }
        }

        return $this;
    }

    protected function getCurrentRequestMethod(): string
    {
        return $this->currentRequestMethod ?? 'GET';
    }

    /**
     * Retrieves the callback for the current route based on the request method and path.
     *
     * @return mixed The route callback or false if not found.
     */
    public function getCallback($request): mixed
    {
        $method = $request->getMethod();
        $url = $request->getPath();
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

                $request->setRouteParams($routeParams);
                return $callback;
            }
        }
        return false;
    }

    /**
     * Checks if the request is a modifying request (POST, PUT, PATCH, DELETE).
     *
     * @param Request $request The incoming request instance.
     * @return bool
     */
    protected function isModifyingRequest(Request $request): bool
    {
        return $request->isPost() || $request->isPut() || $request->isPatch() || $request->isDelete();
    }

    /**
     * Get the current route middleware
     * @return array|null
     */
    public function getCurrentRouteMiddleware($request): ?array
    {
        $url = $request->getPath();
        $method = $request->getMethod();
        $routes = self::$routes[$method] ?? [];

        foreach ($routes as $route => $callback) {
            $routeRegex = "@^" . preg_replace('/\{(\w+)(:[^}]+)?}/', '([^/]+)', $route) . "$@";
            if (preg_match($routeRegex, $url)) {
                return self::$routeMiddlewares[$method][$route] ?? null;
            }
        }

        return null;
    }

    /**
     * Applies middleware to the route
     * @return Route
     */
    private function applyRouteMiddleware($currentMiddleware): void
    {
        foreach ($currentMiddleware as $key) {
            [$name, $params] = array_pad(explode(':', $key, 2), 2, null);
            $params = $params ? explode(',', $params) : [];
            if (!isset($this->routeMiddleware[$name])) {
                throw new \Exception("[$name] Middleware not defined");
            }

            $middlewareClass = $this->routeMiddleware[$name];
            $middlewareInstance = new $middlewareClass();
            if (!$middlewareInstance instanceof ContractsMiddleware) {
                throw new \Exception("Unresolved dependency $middlewareClass", 1);
            }
            $this->applyMiddleware($middlewareInstance, $params);
        }
    }

    /**
     * Resolves and executes the route callback with middleware and dependencies.
     * @param Container $container
     * @param Request $request
     * @throws \ReflectionException If there is an issue with reflection.
     * @throws \Exception
     * @return Response
     */
    public function resolve(Container $container, Request $request)
    {
        $currentMiddleware = $this->getCurrentRouteMiddleware($request);

        if ($currentMiddleware) {
            $this->applyRouteMiddleware($currentMiddleware);
        }
        $callback = $this->getCallback($request);
        if (!$callback) abort(404);

        $routeParams = $request->getRouteParams();
        $finalHandler = function ($request) use ($callback, $container, $routeParams) {
            if (is_array($callback)) {
                $result = $this->resolveControllerAction($callback, $container, $routeParams);
            } elseif (is_string($callback) && class_exists($callback)) {
                $controller = $container->get($callback);
                $reflectionMethod = new \ReflectionMethod($controller, '__invoke');
                $parameters = $reflectionMethod->getParameters();

                $resolvedParameters = [];
                foreach ($parameters as $parameter) {
                    $paramName = $parameter->getName();
                    $paramType = $parameter->getType();

                    if ($paramType) {
                        // If the parameter is a class, resolve it from the container
                        if (class_exists($paramType->getName())) {
                            $resolvedParameters[] = $container->get($paramType->getName());
                        }
                        // If the parameter is a route parameter, use the value from $routeParams
                        elseif (array_key_exists($paramName, $routeParams)) {
                            $resolvedParameters[] = $routeParams[$paramName];
                        }
                    } else {
                        // If the parameter is not type-hinted, try to resolve it from $routeParams
                        if (array_key_exists($paramName, $routeParams)) {
                            $resolvedParameters[] = $routeParams[$paramName];
                        } else {
                            // If the parameter is not found, throw an exception or provide a default value
                            throw new \Exception("Unable to resolve parameter: {$paramName}");
                        }
                    }
                }

                // Call the __invoke method with resolved parameters
                $result = $controller->__invoke(...$resolvedParameters);
            } else {
                $result = call_user_func($callback, ...array_values($routeParams));
            }

            if (!($result instanceof \Zuno\Http\Response)) {
                if ($result instanceof Collection) {
                    $result = $result->toArray();
                }
                return new \Zuno\Http\Response($result);
            }

            return $result;
        };

        $response = $this->handle($request, $finalHandler);

        return $response;
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
                if (is_subclass_of($resolvedClass, \Zuno\Database\Eloquent\Model::class)) {
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
