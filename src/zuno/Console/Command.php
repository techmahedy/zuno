<?php

namespace Zuno\Console;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Application;

class Command extends SymfonyCommand
{
    const PATH = '/Commands';

    const DIRECTORY = 'Zuno\Console\Commands\\';

    public static function dispatch(Application $console): void
    {
        // Get the current directory path (relative to the application root)
        $commandsDir = __DIR__ . Command::PATH;
        $commandFiles = glob($commandsDir . '/*.php');

        // Loop through each command file and add it to the console
        foreach ($commandFiles as $commandFile) {
            require_once $commandFile;
            $className = Command::DIRECTORY . basename($commandFile, '.php');
            $command = new $className();
            $console->add($command);
        }
    }
}
