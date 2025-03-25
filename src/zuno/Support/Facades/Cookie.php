<?php

namespace Zuno\Support\Facades;

/**
 * @method static \Zuno\Support\CookieJar make(string $name, ?string $value = null, array $options = []): Cookie
 * @method static \Zuno\Support\CookieJar get(string $key, $default = null)
 * @method static \Zuno\Support\CookieJar has(string $key): bool
 * @method static \Zuno\Support\CookieJar store($name, $value = null, array $options = []): bool
 * @method static \Zuno\Support\CookieJar remove(string $name, array $options = []): void
 * @method static \Zuno\Support\CookieJar forever(string $name, string $value, array $options = []): void
 * @method static \Zuno\Support\CookieJar all(): ?array
 * @see \Zuno\Support\CookieJar
 */

use Zuno\Facade\BaseFacade;

class Cookie extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'cookie';
    }
}
