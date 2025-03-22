<?php

namespace Zuno\Http;

/**
 * Class ServerBag
 *
 * A container for managing server-related data (e.g., $_SERVER superglobal).
 * Provides methods to retrieve server variables, check their existence, and extract HTTP headers.
 */
class ServerBag
{
    /**
     * @var array The internal storage for server data.
     */
    private array $server;

    /**
     * ServerBag constructor.
     *
     * @param array $server Initial server data (typically the $_SERVER superglobal).
     */
    public function __construct(array $server = [])
    {
        $this->server = $server;
    }

    /**
     * Retrieves the value for a given key from the server data.
     *
     * @param string $key The key to retrieve (e.g., 'REQUEST_METHOD', 'HTTP_HOST').
     * @param mixed $default The default value to return if the key does not exist.
     * @return mixed The value associated with the key, or the default value if the key is not found.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Checks if a key exists in the server data.
     *
     * @param string $key The key to check.
     * @return bool True if the key exists, false otherwise.
     */
    public function has(string $key): bool
    {
        return isset($this->server[$key]);
    }

    /**
     * Retrieves all server data as an associative array.
     *
     * @return array The entire server data.
     */
    public function all(): array
    {
        return $this->server;
    }

    /**
     * Extracts and returns HTTP headers from the server data.
     *
     * HTTP headers in the $_SERVER superglobal are prefixed with 'HTTP_'.
     * This method converts keys like 'HTTP_CONTENT_TYPE' to 'Content-Type'.
     *
     * @return array An associative array of HTTP headers.
     */
    public function getHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                // Convert 'HTTP_HEADER_NAME' to 'Header-Name'
                $headerName = str_replace('_', '-', substr($key, 5));
                $headers[$headerName] = $value;
            }
        }

        return $headers;
    }
}
