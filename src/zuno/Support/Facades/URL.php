<?php

namespace Zuno\Support\Facades;

/**
 * @method static \Zuno\Support\UrlGenerator enqueue(string $path = '/', $secure = null)
 * @see \Zuno\Support\UrlGenerator
 */

use Zuno\Facade\BaseFacade;

class URL extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'url';
    }
}
