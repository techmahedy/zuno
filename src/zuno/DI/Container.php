<?php

namespace Zuno\DI;

class Container
{
    /**
     * Array to hold service definitions.
     *
     * @var array<string, mixed>
     */
    private static array $bindings = [];

    /**
     * Array to hold singleton instances.
     *
     * @var array<string, mixed>
     */
    private static array $instances = [];

    /**
     * The container instance.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Bind a service to the container.
     *
     * @param string $abstract The abstract type or service name.
     * @param callable|string $concrete The concrete implementation or class name.
     * @param bool $singleton Whether the binding should be a singleton.
     * @return void
     */
    public function bind(string $abstract, callable|string $concrete, bool $singleton = false): void
    {
        if (is_string($concrete) && class_exists($concrete)) {
            $concrete = fn() => new $concrete();
        }

        self::$bindings[$abstract] = $concrete;

        if ($singleton) {
            self::$instances[$abstract] = null;
        }
    }

    /**
     * Bind a singleton service to the container.
     *
     * @param string $abstract The abstract type or service name.
     * @param callable|string $concrete The concrete implementation or class name.
     * @return void
     */
    public function singleton(string $abstract, callable|string $concrete): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Conditionally execute bindings.
     *
     * @param callable|bool $condition A boolean or a function returning a boolean.
     * @return self|null Returns self if condition is true, otherwise null.
     */
    public function when(callable|bool $condition): ?self
    {
        if (is_callable($condition)) {
            $condition = $condition();
        }

        return $condition ? $this : null;
    }

    /**
     * Resolve a service from the container.
     *
     * @param string $abstract The service name or class name.
     * @param array $parameters Additional parameters for the constructor.
     * @return mixed
     */
    public function get(string $abstract, array $parameters = [])
    {
        if (isset(self::$bindings[$abstract])) {
            if (isset(self::$instances[$abstract])) {
                if (is_null(self::$instances[$abstract])) {
                    self::$instances[$abstract] = self::$bindings[$abstract]();
                }
                return self::$instances[$abstract];
            }
            return self::$bindings[$abstract]();
        }

        if (
            interface_exists($abstract)
            || (class_exists($abstract) &&
                (new \ReflectionClass($abstract))->isAbstract())
        ) {
            return;
        }

        return new $abstract(...$parameters);
    }

    /**
     * Check if the container has a binding for the given service.
     *
     * @param string $key The service name or class name.
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, self::$bindings);
    }

    /**
     * Get the container instance.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
