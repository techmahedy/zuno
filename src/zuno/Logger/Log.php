<?php

namespace Zuno\Logger;

use Monolog\Level;
use Monolog\Logger;
use Monolog\ResettableInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;

/**
 * Log class provides a mechanism to configure and handle logging using Monolog.
 * It implements the ResettableInterface to allow resetting the logger state.
 */
class Log implements ResettableInterface
{
    /**
     * @var Logger The Monolog logger instance.
     */
    protected Logger $logger;

    /**
     * Configures and returns a Monolog logger instance with specific handlers.
     *
     * This method sets up a logger with two handlers:
     * - StreamHandler for logging to a file
     * - FirePHPHandler for logging to the FirePHP console
     *
     * It ensures the log directory exists and creates it if necessary.
     *
     * @return Logger The configured Monolog logger instance.
     */
    public function logReader(): Logger
    {
        $channel = env('LOG_CHANNEL', 'stack');
        $this->logger = new Logger($channel);

        $path = base_path() . '/storage/logs';

        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }

        $logFile = $channel !== 'daily'
            ? $path . '/zuno.log'
            : $path . '/' . date('Y_m_d') . '_zuno.log';

        if (!is_file($logFile)) {
            touch($logFile);
        }

        $this->logger->pushHandler(new StreamHandler($logFile, Level::Debug));

        return $this->logger;
    }

    /**
     * Resets the logger state by clearing all handlers.
     *
     * This method is required by the ResettableInterface and allows for
     * resetting the logger to its initial state.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->logger->reset();
    }
}
