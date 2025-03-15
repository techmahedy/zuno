<?php

namespace Zuno\Support\Facades;

/**
 * @method static \Zuno\Support\Storage\StorageFileService disk(?string $name = null)
 * @method static \Zuno\Support\Storage\StorageFileService getDiskPath(string $disk)
 * @see \Zuno\Support\Storage\StorageFileService
 */

use Zuno\Facade\BaseFacade;

class Storage extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'storage';
    }
}
