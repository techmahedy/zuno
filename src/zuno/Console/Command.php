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
        $commandsDir = __DIR__ . Command::PATH;
        $commandFiles = glob($commandsDir . '/*.php');

        foreach ($commandFiles as $commandFile) {
            require_once $commandFile;
            $className = Command::DIRECTORY . basename($commandFile, '.php');
            $command = new $className();
            $console->add($command);
        }
    }
}
