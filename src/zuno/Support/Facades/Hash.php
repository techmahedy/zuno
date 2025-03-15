<?php

namespace Zuno\Support\Facades;

/**
 * @method static \Zuno\Auth\Security\PasswordHashing make(string $plainText)
 * @method static \Zuno\Auth\Security\PasswordHashing check(string $plainText, string $hashedText)
 * @method static \Zuno\Auth\Security\PasswordHashing needsRehash(string $password)
 * @see \Zuno\Auth\Security\PasswordHashing
 */

use Zuno\Facade\BaseFacade;

class Hash extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'hash';
    }
}
