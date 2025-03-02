<?php

namespace Zuno;

use Composer\Script\Event;

class Installer
{
    public static function postCreateProject(Event $event)
    {
        $io = $event->getIO();
        $io->write("Setting up Zuno project.");

        // Example: Copy .env.example to .env
        copy('.env.example', '.env');
        
        // Example: Copy .config.example to .config
        copy('config.example', 'config.php');

        // Additional setup steps...
    }
}
