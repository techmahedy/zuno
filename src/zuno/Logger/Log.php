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
        // Instantiate the logger with a specific channel name
        $channel = env('LOG_CHANNEL', 'daily');
        $this->logger = new Logger($channel);

        // Define the log file path
        $path = getcwd() . '/storage/logs';

        // Ensure the log directory exists; create it if it doesn't
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }

        // Define log file path based on the channel
        $logFile = $channel !== 'daily'
            ? $path . '/zuno.log'
            : $path . '/' . date('Y_m_d') . '_zuno.log';

        // Ensure the log file exists (create it if it doesn't)
        if (!is_file($logFile)) {
            touch($logFile);
        }

        // Add a handler to write logs to the file with DEBUG level
        $this->logger->pushHandler(new StreamHandler($logFile, Level::Debug));

        // Return the configured logger instance
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
        // Reset the logger by clearing all handlers
        $this->logger->reset();
    }
}
