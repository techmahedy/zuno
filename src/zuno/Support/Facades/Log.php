<?php

namespace Zuno\Support\Facades;

/**
 * @method static \Zuno\Support\LoggerService debug(mixed $message)
 * @method static \Zuno\Support\LoggerService info(mixed $message)
 * @method static \Zuno\Support\LoggerService notice(mixed $message)
 * @method static \Zuno\Support\LoggerService warning(mixed $message)
 * @method static \Zuno\Support\LoggerService error(mixed $message)
 * @method static \Zuno\Support\LoggerService critical(string $message)
 * @method static \Zuno\Support\LoggerService alert(string $message)
 * @method static \Zuno\Support\LoggerService emergency(string $message)
 * @see \Zuno\Support\LoggerService
 */

use Zuno\Facade\BaseFacade;

class Log extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'log';
    }
}
