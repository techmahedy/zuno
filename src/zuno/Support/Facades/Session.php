<?php

namespace Zuno\Support\Facades;

use Zuno\Facade\BaseFacade;

class Session extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'session';
    }
}
