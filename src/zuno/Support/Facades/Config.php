<?php

namespace Zuno\Support\Facades;

/**
 * @method static \Zuno\Config\Config set(string key, mixed $value)
 * @method static \Zuno\Config\Config get(string key)
 * @method static \Zuno\Config\Config all()
 * @method static \Zuno\Config\Config clearCache()
 * @see \Zuno\Config\Config
 */
use Zuno\Facade\BaseFacade;

class Config extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'config';
    }
}
