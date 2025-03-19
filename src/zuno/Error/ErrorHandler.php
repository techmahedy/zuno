<?php

namespace Zuno\Error;

use Zuno\Http\Exceptions\HttpResponseException;
use Zuno\Support\Facades\Log;

class ErrorHandler
{
    public static function handle(): void
    {
        set_exception_handler(function ($exception) {
            $errorMessage = $exception->getMessage();
            $errorFile = $exception->getFile();
            $errorLine = $exception->getLine();
            $errorTrace = $exception->getTraceAsString();

            if ($exception instanceof HttpResponseException) {
                $validationErrors = $exception->getValidationErrors();
                $statusCode = $exception->getStatusCode();

                self::sendJsonErrorResponse($errorMessage, $errorFile, $errorLine, $errorTrace, $statusCode, $validationErrors);
                return;
            }

            if (env('APP_DEBUG') === "true") {
                $fileContent = file_exists($errorFile) ? file_get_contents($errorFile) : 'File not found.';
                $lines = explode("\n", $fileContent);
                $startLine = max(0, $errorLine - 10);
                $endLine = min(count($lines) - 1, $errorLine + 10);
                $displayedLines = array_slice($lines, $startLine, $endLine - $startLine + 1);

                $highlightedLines = [];
                foreach ($displayedLines as $index => $line) {
                    $lineNumber = $startLine + $index + 1;
                    if ($lineNumber == $errorLine) {
                        $highlightedLines[] = '<span class="line-number highlight">' . $lineNumber . '</span><span class="highlight">' . htmlspecialchars($line) . '</span>';
                    } else {
                        $highlightedLines[] = '<span class="line-number">' . $lineNumber . '</span>' . htmlspecialchars($line);
                    }
                }

                $formattedCode = implode("\n", $highlightedLines);

                $fileExtension = pathinfo($errorFile, PATHINFO_EXTENSION);
                $languageClass = "language-$fileExtension";

                echo str_replace(
                    ['{{ error_message }}', '{{ error_file }}', '{{ error_line }}', '{{ error_trace }}', '{{ file_content }}', '{{ file_extension }}'],
                    [$errorMessage, $errorFile, $errorLine, nl2br(htmlspecialchars($errorTrace)), $formattedCode, $languageClass],
                    file_get_contents(__DIR__ . '/error_page_template.html')
                );
            }

            $logMessage = "Error: " . $exception->getMessage();
            $logMessage .= "\nFile: " . $exception->getFile();
            $logMessage .= "\nLine: " . $exception->getLine();
            $logMessage .= "\nTrace: " . $exception->getTraceAsString();

            Log::channel(env('LOG_CHANNEL', 'stack'))->error($logMessage);
        });
    }

    /**
     * Send a JSON error response for AJAX requests.
     *
     * @param string $errorMessage
     * @param string $errorFile
     * @param int $errorLine
     * @param string $errorTrace
     * @param int $statusCode
     * @param array|null $validationErrors
     */
    public static function sendJsonErrorResponse(
        string $errorMessage,
        string $errorFile,
        int $errorLine,
        string $errorTrace,
        int $statusCode,
        mixed $validationErrors = null
    ): void {
        $response = [
            'success' => false,
            'message' => $errorMessage,
            'error' => [
                'file' => $errorFile,
                'line' => $errorLine,
                'trace' => $errorTrace,
            ],
        ];

        if ($validationErrors) {
            $response['errors'] = $validationErrors;
        }

        header('Content-Type: application/json');
        http_response_code($statusCode);

        echo json_encode($response);
        exit;
    }
}
