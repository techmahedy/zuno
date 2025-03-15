<?php

namespace Zuno\Support\Facades;

/**
 * @method static \Zuno\Http\Support\RequestAbortion abort(int $code, string $message = '')
 * @method static \Zuno\Http\Support\RequestAbortion abortIf(bool $condition, int $code, string $message = '')
 * @see \Zuno\Http\Support\RequestAbortion
 */

use Zuno\Facade\BaseFacade;

class Abort extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'abort';
    }
}
