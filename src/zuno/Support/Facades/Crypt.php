<?php

namespace Zuno\Support\Facades;

/**
 * @method static \Zuno\Config\Config encrypt(mixed $payload)
 * @method static \Zuno\Config\Config decrypt(string $payload)
 * @see \Zuno\Support\Encryption
 */
use Zuno\Facade\BaseFacade;

class Crypt extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'crypt';
    }
}
