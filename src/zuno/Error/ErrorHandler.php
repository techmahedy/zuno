<?php

namespace Zuno\Error;

class ErrorHandler
{
    public static function handle(): void
    {
        set_exception_handler(function ($exception) {
            // Prepare the error message, file, line, and stack trace
            $errorMessage = $exception->getMessage();
            $errorFile = $exception->getFile();
            $errorLine = $exception->getLine();
            $errorTrace = $exception->getTraceAsString();

            // Check if APP_DEBUG is true to display detailed error message
            if (env('APP_DEBUG') === true) {
                // Create the error page with dynamic data
                echo str_replace(
                    ['{{ error_message }}', '{{ error_file }}', '{{ error_line }}', '{{ error_trace }}'],
                    [$errorMessage, $errorFile, $errorLine, nl2br($errorTrace)],
                    file_get_contents(__DIR__ . '/error_page_template.html')
                );
            } else {
                // Default message if debugging is disabled
                echo "An unexpected error occurred.";
            }

            // Optionally log the error message with detailed info
            $logMessage = "Error: " . $exception->getMessage();
            $logMessage .= "\nFile: " . $exception->getFile();
            $logMessage .= "\nLine: " . $exception->getLine();
            $logMessage .= "\nTrace: " . $exception->getTraceAsString();

            logger()->error($logMessage);
        });
    }
}
