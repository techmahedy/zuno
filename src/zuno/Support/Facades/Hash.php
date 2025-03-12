<?php

namespace Zuno\Support\Facades;

use Zuno\Facade\BaseFacade;

class Hash extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'hash';
    }
}
