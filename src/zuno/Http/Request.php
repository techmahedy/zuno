<?php

namespace Zuno\Http;

use Zuno\Support\File;
use Zuno\Http\Support\RequestParser;
use Zuno\Http\Support\RequestHelper;
use Zuno\Http\Rule;

/**
 * The Request class encapsulates all HTTP request-related data and functionality.
 * It handles query parameters, form data, uploaded files, headers, and more.
 */
class Request
{
    use RequestParser, RequestHelper, Rule;

    /**
     * Stores query and post parameters.
     *
     * @var array<string, mixed>
     */
    public array $params = [];

    /**
     * Stores sanitized input parameters.
     *
     * @var array<string, mixed>
     */
    public array $input = [];

    /**
     * Stores files uploaded with the request.
     *
     * @var array<string, mixed>
     */
    public array $files = [];

    /**
     * Constructor for the Request class.
     * Initializes request data from superglobals ($_GET, $_POST, $_FILES, $_SERVER).
     */
    public function __construct()
    {
        $this->files = $_FILES;
    }

    /**
     * Magic method to allow dynamic access to input or file data.
     *
     * @param string $name The name of the input or file.
     * @return mixed The input value or File object, or null if not found.
     */
    public function __get(string $name): mixed
    {
        return $this->input($name) ?? $this->file($name);
    }

    /**
     * Retrieves the current request URI path.
     *
     * @return string The decoded URI path.
     */
    public function getPath(): string
    {
        return urldecode(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
        );
    }

    /**
     * Retrieves the HTTP method used for the request.
     *
     * @return string The HTTP method in lowercase.
     */
    public function getMethod(): string
    {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    /**
     * Checks if the request method is GET.
     *
     * @return bool True if the method is GET, false otherwise.
     */
    public function isGet(): bool
    {
        return $this->getMethod() === 'get';
    }

    /**
     * Checks if the request method is POST.
     *
     * @return bool True if the method is POST, false otherwise.
     */
    public function isPost(): bool
    {
        return $this->getMethod() === 'post';
    }

    /**
     * Sets route parameters for the request.
     *
     * @param array<string, mixed> $params The route parameters.
     * @return self The current instance.
     */
    public function setRouteParams(array $params): self
    {
        $this->params = $params;
        return $this;
    }

    /**
     * Retrieves the route parameters.
     *
     * @return array<string, mixed> The route parameters.
     */
    public function getRouteParams(): array
    {
        return $this->params;
    }

    /**
     * Retrieves a specific route parameter with an optional default value.
     *
     * @param string $param The route parameter to retrieve.
     * @param mixed $default The default value if the parameter does not exist.
     * @return mixed The route parameter value or the default value.
     */
    public function getRouteParam(string $param, mixed $default = null): mixed
    {
        return $this->params[$param] ?? $default;
    }

    /**
     * Retrieves a specific file's information from the request.
     *
     * @param string $param The file parameter name.
     * @return File|null The File object or null if the file doesn't exist.
     */
    public function file(string $param): ?File
    {
        if (isset($this->files[$param]) && $this->files[$param]['error'] === UPLOAD_ERR_OK) {
            return new File($this->files[$param]);
        }
        return null;
    }
}
