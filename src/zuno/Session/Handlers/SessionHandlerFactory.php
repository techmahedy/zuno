<?php

namespace Zuno\Session\Handlers;

use Zuno\Session\Handlers\FileSessionHandler;
use Zuno\Session\Handlers\CookieSessionHandler;
use Zuno\Session\Contracts\SessionHandlerInterface;

class SessionHandlerFactory
{
    public static function create(string $driver, array $config): SessionHandlerInterface
    {
        switch ($driver) {
            case 'cookie':
                return new CookieSessionHandler($config);
            case 'file':
                return new FileSessionHandler($config);
            default:
                throw new \RuntimeException("Unsupported session driver: {$driver}");
        }
    }
}
