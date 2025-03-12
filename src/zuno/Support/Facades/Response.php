<?php

namespace Zuno\Support\Facades;

use Zuno\Facade\BaseFacade;

class Response extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'response';
    }
}
