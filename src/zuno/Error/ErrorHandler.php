<?php

namespace Zuno\Error;

class ErrorHandler
{
    public static function handle(): void
    {
        set_exception_handler(function ($exception) {
            $errorMessage = $exception->getMessage();
            $errorFile = $exception->getFile();
            $errorLine = $exception->getLine();
            $errorTrace = nl2br(htmlspecialchars($exception->getTraceAsString()));

            if (env('APP_DEBUG') === true) {
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

                // Detect file extension for syntax highlighting
                $fileExtension = pathinfo($errorFile, PATHINFO_EXTENSION);
                $languageClass = "language-$fileExtension";

                echo str_replace(
                    ['{{ error_message }}', '{{ error_file }}', '{{ error_line }}', '{{ error_trace }}', '{{ file_content }}', '{{ file_extension }}'],
                    [$errorMessage, $errorFile, $errorLine, $errorTrace, $formattedCode, $languageClass],
                    file_get_contents(__DIR__ . '/error_page_template.html')
                );
            }

            $logMessage = "Error: " . $exception->getMessage();
            $logMessage .= "\nFile: " . $exception->getFile();
            $logMessage .= "\nLine: " . $exception->getLine();
            $logMessage .= "\nTrace: " . $exception->getTraceAsString();

            logger()->error($logMessage);
        });
    }
}
