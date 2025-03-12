<?php

namespace Zuno\Support\Facades;

use Zuno\Facade\BaseFacade;

class Redirect extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'redirect';
    }
}
