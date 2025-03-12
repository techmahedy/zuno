<?php

namespace Zuno\Support\Facades;

use Zuno\Facade\BaseFacade;

class Route extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'route';
    }
}
