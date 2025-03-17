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
        if ($this->trustedHeaderSet & self::HEADER_FORWARDED) {
            $forwarded = $this->headers->get('FORWARDED');
            if ($forwarded) {
                $parts = explode(';', $forwarded);
                foreach ($parts as $part) {
                    $keyValue = explode('=', trim($part), 2);
                    if (count($keyValue) === 2 && $keyValue[0] === 'for') {
                        return $keyValue[1];
                    }
                }
            }
        }

        if ($this->trustedHeaderSet & self::HEADER_X_FORWARDED_FOR) {
            $xForwardedFor = $this->headers->get('X_FORWARDED_FOR');
            if ($xForwardedFor) {
                $ips = explode(',', $xForwardedFor);
                return trim($ips[0]);
            }
        }

        return $this->server->get('REMOTE_ADDR');
    }

    /**
     * Retrieves the full request URI.
     *
     * @return string The full URI.
     */
    public function uri(): string
    {
        return $this->server->get('REQUEST_URI', '/');
    }

    /**
     * Retrieves the server data.
     *
     * @return array The server data.
     */
    public function server(): array
    {
        return $this->server->all();
    }

    /**
     * Retrieves the request headers.
     *
     * @return array<string, string> The request headers.
     */
    public function headers(): array
    {
        return $this->headers->all();
    }

    /**
     * Retrieves a specific request header.
     *
     * @param string $name The header name.
     * @return string|null The header value or null if not found.
     */
    public function header(string $name): ?string
    {
        return $this->headers->get($name);
    }

    /**
     * Retrieves the request scheme (http or https).
     *
     * @return string The request scheme.
     */
    public function scheme(): string
    {
        return $this->server->get('HTTPS') === 'on' ? 'https' : 'http';
    }

    /**
     * Retrieves the request host.
     *
     * @return string The request host.
     */
    public function host(): string
    {
        return $this->headers->get('HOST');
    }

    /**
     * Retrieves the request URL (scheme + host + URI).
     *
     * @return string The full request URL.
     */
    public function url(): string
    {
        return $this->scheme() . '://' . $this->host() . $this->uri();
    }

    /**
     * Retrieves the request query string.
     *
     * @return string The query string.
     */
    public function query(): ?string
    {
        return $this->server->get('QUERY_STRING');
    }

    /**
     * Retrieves the raw body content of the request.
     *
     * @return string|resource|false|null The raw body content.
     */
    public function content()
    {
        return $this->getContent();
    }

    /**
     * Retrieves the HTTP method used for the request.
     *
     * @return string The HTTP method in lowercase.
     */
    public function method(): string
    {
        return strtolower($this->server->get('REQUEST_METHOD', 'GET'));
    }

    /**
     * Retrieves the request cookies.
     *
     * @return array|null The cookies.
     */
    public function cookie(): ?array
    {
        return $this->cookies->all();
    }

    /**
     * Retrieves the request user agent.
     *
     * @return string|null The user agent or null if not available.
     */
    public function userAgent(): ?string
    {
        return $this->headers->get('USER_AGENT');
    }

    /**
     * Retrieves the request referer.
     *
     * @return string|null The referer or null if not available.
     */
    public function referer(): ?string
    {
        return $this->headers->get('REFERER');
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
        return $this->headers->get('X-REQUESTED-WITH') === 'XMLHttpRequest';
    }

    /**
     * Checks if the request is a JSON request.
     *
     * @return bool True if the request is JSON, false otherwise.
     */
    public function isJson(): bool
    {
        $accept = $this->headers->get('ACCEPT');
        return $accept && strpos($accept, 'application/json') !== false;
    }

    /**
     * Retrieves the request content type.
     *
     * @return string|null The content type or null if not available.
     */
    public function contentType(): ?string
    {
        return $this->headers->get('CONTENT_TYPE');
    }

    /**
     * Retrieves the request content length.
     *
     * @return int|null The content length or null if not available.
     */
    public function contentLength(): ?int
    {
        $length = $this->headers->get('CONTENT_LENGTH');
        return $length !== null ? (int)$length : null;
    }
}
