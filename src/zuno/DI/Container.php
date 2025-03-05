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

    public function bind(string $abstract, callable|string $concrete): void
    {
        if (is_string($concrete) && class_exists($concrete)) {
            $concrete = fn() => new $concrete();
        }

        self::$bindings[$abstract] = $concrete;
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
     * Bind a service to the container.
     *
     * @param string $key The service name or class name (could be an interface).
     * @return mixed
     */
    public function get(string $abstract)
    {
        if (isset(self::$bindings[$abstract])) {
            return self::$bindings[$abstract]();
        }

        if (
            interface_exists($abstract)
            || (class_exists($abstract) &&
                (new \ReflectionClass($abstract))->isAbstract())
        ) {
            return;
        }

        return new $abstract();
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
}
