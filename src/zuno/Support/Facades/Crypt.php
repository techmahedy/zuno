<?php

namespace Zuno\Support\Facades;

use Zuno\Facade\BaseFacade;

class Crypt extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'crypt';
    }
}
