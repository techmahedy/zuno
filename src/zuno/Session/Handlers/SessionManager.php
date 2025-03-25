<?php

namespace Zuno\Session\Handlers;

use Zuno\Session\Contracts\SessionHandlerInterface;
use Zuno\Config\Config;

class SessionManager
{
    private static ?SessionHandlerInterface $handler = null;

    public static function initialize(): void
    {
        if (php_sapi_name() === 'cli' || defined('STDIN')) {
            return;
        }

        $sessionConfig = (array) Config::get('session');

        self::$handler = SessionHandlerFactory::create($sessionConfig['driver'], $sessionConfig);
        self::$handler->initialize();
        self::$handler->start();
    }

    public static function getHandler(): SessionHandlerInterface
    {
        if (self::$handler === null) {
            throw new \RuntimeException("Session handler not initialized");
        }
        return self::$handler;
    }
}
