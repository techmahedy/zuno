<?php

namespace Zuno\Support\Facades;

use Zuno\Facade\BaseFacade;

class Auth extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'auth';
    }
}
