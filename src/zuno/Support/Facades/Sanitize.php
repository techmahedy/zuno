<?php

namespace Zuno\Support\Facades;

/**
 * @method static \Zuno\Support\Validation\Sanitizer request(array $data, array $rules)
 * @method static \Zuno\Support\Validation\Sanitizer validate()
 * @method static \Zuno\Support\Validation\Sanitizer fails()
 * @method static \Zuno\Support\Validation\Sanitizer errors()
 * @method static \Zuno\Support\Validation\Sanitizer passed()
 * @method static \Zuno\Support\Validation\Sanitizer errors()
 * @see \Zuno\Support\Validation\Sanitizer
 */

use Zuno\Facade\BaseFacade;

class Sanitize extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'sanitize';
    }
}
