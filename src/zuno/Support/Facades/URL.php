<?php

namespace Zuno\Support\Facades;

/**
 * @method static \Zuno\Support\UrlGenerator enqueue(string $path = '/', $secure = null)
 * @method static \Zuno\Support\UrlGenerator full()
 * @method static \Zuno\Support\UrlGenerator current()
 * @method static \Zuno\Support\UrlGenerator route($name, array $parameters = [], $secure = null)
 * @method static \Zuno\Support\UrlGenerator to($path = '/')
 * @method static \Zuno\Support\UrlGenerator withQuery(array $query = [])
 * @method static \Zuno\Support\UrlGenerator withSignature($expiration = 3600)
 * @method static \Zuno\Support\UrlGenerator withFragment($fragment = '')
 * @method static \Zuno\Support\UrlGenerator make()
 * @method static \Zuno\Support\UrlGenerator signed($path = '/', array $parameters = [], $expiration = 3600, $secure = null)
 * @method static \Zuno\Support\UrlGenerator isValid(string $url)
 * @method static \Zuno\Support\UrlGenerator base(string $url)
 * @method static \Zuno\Support\UrlGenerator setSecure(bool $secure)
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
