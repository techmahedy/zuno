<?php

namespace Zuno\Logger\Contracts;

use Monolog\Logger;
use Monolog\Level;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

abstract class AbstractHandler
{
    /**
     * Configures the Monolog handler for logging.
     *
     * @param Logger $logger The Monolog logger instance.
     * @param string $channel The logging channel.
     * @return void
     */
    public function handleConfiguration(Logger $logger, ?string $logFile = null): void
    {
        $streamHandler = new StreamHandler($logFile, Level::Debug);
        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message%\n",
            null,
            true,
            true
        );

        $streamHandler->setFormatter($formatter);

        $logger->pushHandler($streamHandler);
    }
}
