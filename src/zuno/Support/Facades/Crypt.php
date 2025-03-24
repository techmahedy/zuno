<?php

namespace Zuno\Support\Facades;

/**
 * @method static \Zuno\Support\Encryption encrypt(mixed $payload): string
 * @method static \Zuno\Support\Encryption decrypt(string $payload): string
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
