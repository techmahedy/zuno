<?php

namespace Zuno\Http;

use Zuno\Http\Response\HttpStatus;
use Zuno\Http\Exceptions\HttpException;

/**
 * The Response class handles HTTP responses, including setting headers, status codes,
 * and sending the response body. It also provides methods for handling HTTP exceptions
 * and rendering error pages or JSON responses based on the request type.
 */
class Response implements HttpStatus
{
    /**
     * The response body content.
     *
     * @var string
     */
    protected mixed $body;

    /**
     * The HTTP status code for the response.
     *
     * @var int
     */
    protected int $statusCode;

    /**
     * The HTTP headers for the response.
     *
     * @var array<string, string>
     */
    protected array $headers = [];

    /**
     * Constructor for the Response class.
     *
     * @param mixed $body The response body content.
     * @param int $statusCode The HTTP status code (default: 200).
     * @param array<string, string> $headers The HTTP headers (default: empty array).
     */
    public function __construct(mixed $body = '', int $statusCode = 200, array $headers = [])
    {
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Set the response body content.
     *
     * @param string $body The new response body.
     * @return void
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    /**
     * Get the response body content.
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Set the HTTP status code for the response.
     *
     * @param int $statusCode The HTTP status code.
     * @return int The updated status code.
     */
    public function setStatusCode(int $statusCode): int
    {
        return $this->statusCode = $statusCode;
    }

    /**
     * Get the HTTP status code for the response.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the HTTP headers for the response.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set a specific HTTP header for the response.
     *
     * @param string $name The header name.
     * @param string $value The header value.
     * @return void
     */
    public function setHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    /**
     * Send the HTTP response to the client.
     *
     * This method sets the HTTP status code, sends the headers, and outputs the response body.
     * If the status code is a redirection (3xx), it exits after sending the headers.
     *
     * @return void
     */
    public function send(): void
    {
        // Set the HTTP response code
        http_response_code($this->statusCode);

        // Send all headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // If the status code is a redirection (3xx), exit after sending headers
        if (
            $this->statusCode >= Response::HTTP_MULTIPLE_CHOICES &&
            $this->statusCode < Response::HTTP_BAD_REQUEST
        ) {
            exit;
        }

        // Output the response body
        echo $this->body;
    }

    /**
     * Handle an HTTP exception and return a response.
     *
     * This method sets the appropriate HTTP status code and renders either a JSON response
     * (for API requests) or an HTML error page (for web requests).
     *
     * @param HttpException $exception The HTTP exception to handle.
     * @return void
     */
    public static function dispatchHttpException(HttpException $exception): void
    {
        $statusCode = $exception->getStatusCode();
        $message = $exception->getMessage() ?: 'An error occurred.';

        // Set the HTTP response code
        http_response_code($statusCode);

        // Render a JSON response for API requests
        if (self::isJsonRequest()) {
            echo json_encode([
                'error' => [
                    'code' => $statusCode,
                    'message' => $message,
                ],
            ]);
            return;
        }

        // Render an HTML error page for web requests
        self::renderErrorPage($statusCode, $message);
    }

    /**
     * Check if the request expects a JSON response.
     *
     * This method checks the `Accept` header to determine if the client expects JSON.
     *
     * @return bool True if the request expects JSON, false otherwise.
     */
    protected static function isJsonRequest(): bool
    {
        return isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
    }

    /**
     * Render an HTML error page.
     *
     * This method attempts to include an error page template corresponding to the status code.
     * If the template does not exist, it throws an HTTP exception.
     *
     * @param int $statusCode The HTTP status code.
     * @param string $message The error message.
     * @return void
     */
    protected static function renderErrorPage(int $statusCode, string $message): void
    {
        // Path to the error page template
        $errorPage = base_path() . "/vendor/zuno/zuno/src/zuno/Support/View/errors/{$statusCode}.blade.php";

        // Include the error page if it exists
        if (file_exists($errorPage)) {
            include $errorPage;
        } else {
            // If the error page does not exist, throw an HTTP exception
            throw new HttpException($statusCode, $message);
        }
    }
}
