<?php

namespace Zuno\Support\Facades;

use Zuno\Facade\BaseFacade;

class Mail extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'mail';
    }
}
