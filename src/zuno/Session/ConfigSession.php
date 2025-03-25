<?php

namespace Zuno\Session;

use Zuno\Session\Handlers\SessionManager;

class ConfigSession
{
    public static function configAppSession(): void
    {
        SessionManager::initialize();
    }
}
