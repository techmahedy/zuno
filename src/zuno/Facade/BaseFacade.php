<?php

namespace Zuno\Facade;

use Zuno\DI\Container;

abstract class BaseFacade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    abstract protected static function getFacadeAccessor();

    /**
     * Resolve the instance from the container.
     *
     * @return mixed
     */
    protected static function resolveInstance()
    {
        return Container::getInstance()->get(static::getFacadeAccessor());
    }

    /**
     * Handle static method calls.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        return static::resolveInstance()->$method(...$args);
    }
}
