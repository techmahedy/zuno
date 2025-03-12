<?php

namespace Zuno\Support\Facades;

use Zuno\Facade\BaseFacade;

class Abort extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'abort';
    }
}
