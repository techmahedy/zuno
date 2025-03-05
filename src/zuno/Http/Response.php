<?php

namespace Zuno\Http;

use Zuno\Http\Exceptions\HttpException;

class Response extends Request
{
    protected string $body;

    protected int $statusCode;

    protected array $headers = [];

    public function __construct(string $body = '', int $statusCode = 200, array $headers = [])
    {
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setStatusCode(int $statusCode): int
    {
        return $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers() as $name => $value) {
            header("$name: $value");
        }
        echo $this->body;
    }

    /**
     * Handle an HTTP exception and return a response.
     *
     * @param HttpException $exception The HTTP exception.
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
     * @return bool
     */
    protected static function isJsonRequest(): bool
    {
        return isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
    }

    /**
     * Render an HTML error page.
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
}
