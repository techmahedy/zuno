<?php

namespace Zuno\Support\Facades;

/**
 * @method static \Zuno\Support\Router get(string $uri, array|string|callable|null)
 * @method static \Zuno\Support\Router post(string $uri, array|string|callable|null)
 * @method static \Zuno\Support\Router put(string $uri, array|string|callable|null)
 * @method static \Zuno\Support\Router patch(string $uri, array|string|callable|null)
 * @method static \Zuno\Support\Router delete(string $uri, array|string|callable|null)
 * @see \Zuno\Support\Router
 */

use Zuno\Facade\BaseFacade;

class Route extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'route';
    }
}
