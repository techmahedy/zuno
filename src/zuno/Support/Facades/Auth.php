<?php

namespace Zuno\Support\Facades;

/**
 * @method static \Zuno\Auth\Security\Authenticate try(array $credentials, bool $remember = false)
 * @method static \Zuno\Auth\Security\Authenticate user()
 * @method static \Zuno\Auth\Security\Authenticate check()
 * @method static \Zuno\Auth\Security\Authenticate logout()
 * @see \Zuno\Auth\Security\Authenticate
 */

use Zuno\Facade\BaseFacade;

class Auth extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'auth';
    }
}
