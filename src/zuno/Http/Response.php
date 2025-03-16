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
    public mixed $body;

    /**
     * The HTTP status code for the response.
     *
     * @var int
     */
    public int $statusCode;

    /**
     * The HTTP headers for the response.
     *
     * @var array<string, string>
     */
    public array $headers = [];

    /**
     * Constructor for the Response class.
     *
     * @param mixed $body The response body content.
     * @param int $statusCode The HTTP status code (default: 200).
     * @param array<string, string> $headers The HTTP headers (default: empty array).
     */
    public function __construct(mixed $body = null, int $statusCode = 200, array $headers = [])
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
    public function setHeader(string $name, string $value): Response
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Set multiple HTTP headers for the response.
     *
     * @param array<string, string> $headers An associative array of headers.
     * @return Response Returns the current Response instance.
     */
    public function withHeaders(array $headers): Response
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }

        return $this;
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
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        if (
            $this->statusCode >= Response::HTTP_MULTIPLE_CHOICES &&
            $this->statusCode < Response::HTTP_BAD_REQUEST
        ) {
            exit;
        }

        if (is_array($this->body) || is_object($this->body)) {
            header('Content-Type: application/json');
            echo json_encode($this->body);
        } else {
            echo $this->body;
        }
    }

    /**
     * Return a JSON response.
     *
     * This method sets the appropriate headers for a JSON response and returns the JSON-encoded data.
     *
     * @param mixed $data The data to encode as JSON.
     * @param int $statusCode The HTTP status code (default: 200).
     * @param array<string, string> $headers Additional headers to include in the response.
     * @return Response
     */
    public function json(mixed $data, int $statusCode = 200, array $headers = []): Response
    {
        $encoded = json_encode($data);

        if ($encoded === false) {
            $this->body = json_encode(['error' => 'JSON encoding failed: ' . json_last_error_msg()]);
            $this->statusCode = 500;
            $this->headers['Content-Type'] = 'application/json';
            return $this;
        }

        $this->body = $encoded;
        $this->statusCode = $statusCode;

        $this->headers['Content-Type'] = 'application/json';
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    /**
     * Return a plain text response.
     *
     * @param string $content The plain text content.
     * @param int $statusCode The HTTP status code (default: 200).
     * @param array<string, string> $headers Additional headers to include in the response.
     * @return Response
     */
    public function text(string $content, int $statusCode = 200, array $headers = []): Response
    {
        $this->body = $content;
        $this->statusCode = $statusCode;
        $this->headers = array_merge($this->headers, ['Content-Type' => 'text/plain'], $headers);
        return $this;
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

        http_response_code($statusCode);

        if (self::isJsonRequest()) {
            echo json_encode([
                'error' => [
                    'code' => $statusCode,
                    'message' => $message,
                ],
            ]);
            return;
        }

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
        $errorPage = base_path() . "/vendor/zuno/zuno/src/zuno/Support/View/errors/{$statusCode}.blade.php";

        if (file_exists($errorPage)) {
            include $errorPage;
        } else {
            throw new HttpException($statusCode, $message);
        }
    }

    /**
     * Renders a view with the given data and returns the rendered content as a string.
     */
    public function render(): string
    {
        if (empty($this->body)) {
            throw new \RuntimeException('No view content to render.');
        }

        if (is_string($this->body)) {
            return $this->body;
        }

        if (is_callable($this->body)) {
            return call_user_func($this->body);
        }

        if (is_object($this->body) && method_exists($this->body, '__toString')) {
            return $this->body->__toString();
        }

        return (string) $this->body;
    }

    /**
     * Renders a view with the given data and returns a Response object.
     *
     * @param string $view The name of the view file to render.
     * @param array $data An associative array of data to pass to the view (default is an empty array).
     * @return Response A Response object containing the rendered view.
     */
    public function view(string $view, array $data = []): Response
    {
        $content = $this->renderView($view, $data);

        $this->body = $content;

        return $this;
    }

    public function renderView(string $view, array $data = []): string
    {
        $viewPath = str_replace('.', '/', $view);

        extract($data);

        ob_start();

        include base_path("resources/views/{$viewPath}.blade.php");

        return ob_get_clean();
    }
}
