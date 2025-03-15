<?php

namespace Zuno\Support\Facades;

/**
 * @method static \Zuno\Http\RedirectResponse to(string $url, int $statusCode = 302)
 * @method static \Zuno\Http\RedirectResponse back()
 * @method static \Zuno\Http\RedirectResponse withInput()
 * @method static \Zuno\Http\RedirectResponse route(string $name, array $params = [])
 * @method static \Zuno\Http\RedirectResponse withErrors(array $errors)
 * @method static \Zuno\Http\RedirectResponse away(string $url, int $statusCode = 302)
 * @see \Zuno\Http\RedirectResponse
 */

use Zuno\Facade\BaseFacade;

class Redirect extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'redirect';
    }
}
