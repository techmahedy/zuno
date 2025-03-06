<?php

namespace Zuno\Http\Support;

trait RequestParser
{
    /**
     * Retrieves the client's IP address.
     *
     * @return string|null The IP address or null if not available.
     */
    public function ip(): ?string
    {
        return $_SERVER['HTTP_CLIENT_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? null;
    }

    /**
     * Retrieves the full request URI.
     *
     * @return string The full URI.
     */
    public function uri(): string
    {
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * Retrieves server
     *
     * @return array
     */
    public function server(): array
    {
        return $_SERVER;
    }

    /**
     * Retrieves the request headers.
     *
     * @return array<string, string> The request headers.
     */
    public function headers(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    /**
     * Retrieves a specific request header.
     *
     * @param string $name The header name.
     * @return string|null The header value or null if not found.
     */
    public function header(string $name): ?string
    {
        $headers = $this->headers();
        return $headers[$name] ?? null;
    }

    /**
     * Retrieves the request scheme (http or https).
     *
     * @return string The request scheme.
     */
    public function scheme(): string
    {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    }

    /**
     * Retrieves the request host.
     *
     * @return string The request host.
     */
    public function host(): string
    {
        return $_SERVER['HTTP_HOST'];
    }

    /**
     * Retrieves the request URL (scheme + host + URI).
     *
     * @return string The full request URL.
     */
    public function url(): string
    {
        return $this->scheme() . '://' . $this->host() . $this->URI();
    }

    /**
     * Retrieves the request query string.
     *
     * @return string The query string.
     */
    public function query(): ?string
    {
        return $_SERVER['QUERY_STRING'];
    }

    /**
     * Retrieves the raw body content of the request.
     *
     * @return string|resource|false|null The raw body content.
     */
    public function content()
    {
        return file_get_contents('php://input');
    }

    /**
     * Retrieves the HTTP method used for the request.
     *
     * @return string The HTTP method in lowercase.
     */
    public function method(): string
    {
        return $this->server['REQUEST_METHOD'];
    }

    /**
     * Retrieves the request query string.
     *
     * @return null|array
     */
    public function cookie(): ?array
    {
        return $_COOKIE;
    }

    /**
     * Retrieves the request user agent.
     *
     * @return string|null The user agent or null if not available.
     */
    public function userAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * Retrieves the request referer.
     *
     * @return string|null The referer or null if not available.
     */
    public function referer(): ?string
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }

    /**
     * Checks if the request is secure (HTTPS).
     *
     * @return bool True if the request is secure, false otherwise.
     */
    public function isSecure(): bool
    {
        return $this->scheme() === 'https';
    }

    /**
     * Checks if the request is an AJAX request.
     *
     * @return bool True if the request is AJAX, false otherwise.
     */
    public function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Checks if the request is a JSON request.
     *
     * @return bool True if the request is JSON, false otherwise.
     */
    public function isJson(): bool
    {
        return isset($_SERVER['HTTP_ACCEPT'])
            && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
    }

    /**
     * Retrieves the request content type.
     *
     * @return string|null The content type or null if not available.
     */
    public function contentType(): ?string
    {
        return $_SERVER['CONTENT_TYPE'] ?? null;
    }

    /**
     * Retrieves the request content length.
     *
     * @return int|null The content length or null if not available.
     */
    public function contentLength(): ?int
    {
        return isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : null;
    }
}
