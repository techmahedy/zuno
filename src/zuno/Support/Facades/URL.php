<?php

namespace Zuno\Support\Facades;

use Zuno\Facade\BaseFacade;

class URL extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'url';
    }
}
