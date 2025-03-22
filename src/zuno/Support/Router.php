<?php

namespace Zuno\Support;

use Zuno\Middleware\Contracts\Middleware as ContractsMiddleware;
use Zuno\Http\Validation\FormRequest;
use Zuno\Http\Response;
use Zuno\Http\Request;
use Zuno\Http\RedirectResponse;
use Zuno\Database\Eloquent\Model;
use Zuno\Database\Eloquent\Builder;
use Zuno\Application;
use Ramsey\Collection\Collection;
use App\Http\Kernel;
use Zuno\Support\Facades\Redirect;

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
        'OPTIONS' => [],
        'HEAD' => [],
        'ANY' => [],
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
        $modifiedRequest = ['PUT', 'PATCH', 'DELETE', 'OPTIONS', 'ANY', 'HEAD'];

        if (in_array(request()->_method, $modifiedRequest)) {
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
     * Registers an OPTIONS route with a callback.
     *
     * @param string $path The route path.
     * @param callable $callback The callback for the route.
     * @return self
     */
    public function options($path, $callback): self
    {
        $this->currentRequestMethod = 'OPTIONS';
        self::$routes['OPTIONS'][$path] = $callback;
        $this->currentRoutePath = $path;

        return $this;
    }

    /**
     * Registers a HEAD route with a callback.
     *
     * @param string $path The route path.
     * @param callable $callback The callback for the route.
     * @return self
     */
    public function head($path, $callback): self
    {
        $this->currentRequestMethod = 'HEAD';
        self::$routes['HEAD'][$path] = $callback;
        $this->currentRoutePath = $path;

        return $this;
    }

    /**
     * Registers a route that matches any HTTP method.
     *
     * @param string $path The route path.
     * @param callable $callback The callback for the route.
     * @return self
     */
    public function any($path, $callback): self
    {
        $method = request()->_method ?? request()->getMethod();
        self::$routes[$method][$path] = $callback;

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
                // Fallback in case there's no named parameter
                $params = [$params];
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

        foreach ($routes as $route => $callback) {
            $routeNames = [];
            if (!$route) continue;

            // Handle wildcard routes (.*)
            if ($route === '(.*)') {
                // Directly return the callback for wildcard routes
                return $callback;
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
     * @param Application $app
     * @param Request $request
     * @throws \ReflectionException If there is an issue with reflection.
     * @throws \Exception
     * @return Response
     */
    public function resolve(Application $app, Request $request)
    {
        $currentMiddleware = $this->getCurrentRouteMiddleware($request);
        if ($currentMiddleware) $this->applyRouteMiddleware($currentMiddleware);

        $callback = $this->getCallback($request);
        if (!$callback) abort(404);

        $routeParams = $request->getRouteParams();
        $finalHandler = function ($request) use ($callback, $app, $routeParams) {
            if (is_array($callback)) {
                $result = $this->resolveControllerAction($callback, $app, $routeParams);
            } elseif (is_string($callback) && class_exists($callback)) {
                $controller = $app->get($callback);
                $result = $this->handleInvokableAction($app, $controller, $routeParams);
            } else {
                $result = call_user_func($callback, ...array_values($routeParams));
            }

            $response = app(Response::class);
            if (!($result instanceof Response)) {
                if ($result instanceof Collection) {
                    $result = json_encode($result->toArray());
                    $response->headers->set('Content-Type', 'application/json');
                } elseif (is_array($result)) {
                    $result = json_encode($result);
                    $response->headers->set('Content-Type', 'application/json');
                } elseif ($result instanceof Model) {
                    $result = json_encode($result);
                    $response->headers->set('Content-Type', 'application/json');
                } elseif ($result instanceof Builder) {
                    $result = json_encode($result);
                    $response->headers->set('Content-Type', 'application/json');
                }

                $response->setBody($result);

                return $response;
            }

            return $result;
        };
        $response = $this->handle($request, $finalHandler);

        return $response;
    }

    /**
     * Handling __invokable controller actions
     * @param mixed $app
     * @param mixed $controller
     * @param mixed $routeParams
     * @return mixed
     * @throws \ReflectionException If there is an issue with reflection.
     * @throws \Exception If dependency resolution fails.
     */
    public function handleInvokableAction($app, $controller, $routeParams): mixed
    {
        $reflectionMethod = new \ReflectionMethod($controller, '__invoke');
        $parameters = $reflectionMethod->getParameters();

        $resolvedParameters = [];
        foreach ($parameters as $parameter) {
            $paramName = $parameter->getName();
            $paramType = $parameter->getType();

            if ($paramType) {
                if (class_exists($paramType->getName())) {
                    $resolvedParameters[] = $app->get($paramType->getName());
                } elseif (array_key_exists($paramName, $routeParams)) {
                    $resolvedParameters[] = $routeParams[$paramName];
                }
            } else {
                if (array_key_exists($paramName, $routeParams)) {
                    $resolvedParameters[] = $routeParams[$paramName];
                } else {
                    throw new \Exception("Unable to resolve parameter: {$paramName}");
                }
            }
        }

        $result = $controller->__invoke(...$resolvedParameters);

        return $result;
    }

    /**
     * Resolves and executes a controller action with dependencies.
     *
     * @param array $callback The controller callback (e.g., [Controller::class, 'action']).
     * @param Application $app The Application instance for resolving dependencies.
     * @param array $routeParams The route parameters.
     * @return mixed The result of the controller action execution.
     * @throws \ReflectionException If there is an issue with reflection.
     * @throws \Exception If dependency resolution fails.
     */
    private function resolveControllerAction(array $callback, $app, array $routeParams): mixed
    {
        [$controllerClass, $actionMethod] = $callback;
        $reflector = new \ReflectionClass($controllerClass);

        $constructorDependencies = $this->resolveConstructorDependencies($reflector, $app, $routeParams);
        $controllerInstance = new $controllerClass(...$constructorDependencies);

        $actionDependencies = $this->resolveActionDependencies($reflector, $actionMethod, $app, $routeParams);

        return call_user_func([$controllerInstance, $actionMethod], ...$actionDependencies);
    }

    /**
     * Resolves constructor dependencies for a controller.
     *
     * @param \ReflectionClass $reflector The reflection class of the controller.
     * @param $app The Application instance for resolving dependencies.
     * @param array $routeParams The route parameters.
     * @return array The resolved constructor dependencies.
     * @throws \Exception If dependency resolution fails.
     */
    private function resolveConstructorDependencies(
        \ReflectionClass $reflector,
        $app,
        array $routeParams
    ): array {
        $constructor = $reflector->getConstructor();
        if (!$constructor) {
            return [];
        }

        return $this->resolveParameters($constructor->getParameters(), $app, $routeParams);
    }

    /**
     * Resolves action dependencies for a controller method.
     *
     * @param \ReflectionClass $reflector The reflection class of the controller.
     * @param string $actionMethod The name of the action method.
     * @param $app The Application instance for resolving dependencies.
     * @param array $routeParams The route parameters.
     * @return array The resolved action dependencies.
     * @throws \ReflectionException If there is an issue with reflection.
     * @throws \Exception If dependency resolution fails.
     */
    private function resolveActionDependencies(
        \ReflectionClass $reflector,
        string $actionMethod,
        $app,
        array $routeParams
    ): array {
        $method = $reflector->getMethod($actionMethod);

        return $this->resolveParameters($method->getParameters(), $app, $routeParams);
    }

    /**
     * Resolves parameters for a method or constructor.
     *
     * @param array $parameters The parameters to resolve.
     * @param $app The Application instance for resolving dependencies.
     * @param array $routeParams The route parameters.
     * @return array The resolved parameters.
     * @throws \Exception If dependency resolution fails.
     */
    private function resolveParameters(array $parameters, $app, array $routeParams): array
    {
        $dependencies = [];
        foreach ($parameters as $parameter) {
            $paramName = $parameter->getName();
            $paramType = $parameter->getType();

            if ($paramType && !$paramType->isBuiltin()) {
                $resolvedClass = $paramType->getName();

                if (!$app->has($resolvedClass)) {
                    if (is_subclass_of($resolvedClass, FormRequest::class)) {
                        $app->singleton($resolvedClass, function () use ($app, $resolvedClass) {
                            return new $resolvedClass($app->get(Request::class));
                        });
                    } else {
                        $app->singleton($resolvedClass, $resolvedClass);
                    }
                }

                $resolvedInstance = app($resolvedClass);
                if ($resolvedInstance instanceof FormRequest) {
                    $resolvedInstance->resolvedFormRequestValidation();
                }
                $dependencies[] = $resolvedInstance;
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
