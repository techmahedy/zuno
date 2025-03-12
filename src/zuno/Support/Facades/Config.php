<?php

namespace Zuno\Support\Facades;

use Zuno\Facade\BaseFacade;

class Config extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'config';
    }
}
