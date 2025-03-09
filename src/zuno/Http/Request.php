<?php

namespace Zuno\Http;

use Zuno\Support\Session;
use Zuno\Support\File;
use Zuno\Http\Support\RequestParser;
use Zuno\Http\Support\RequestHelper;
use Zuno\Http\Rule;

class Request
{
    use RequestParser, RequestHelper, Rule;

    /**
     * Custom parameters.
     */
    public array $attributes = [];

    /**
     * Request body parameters ($_POST).
     */
    public array $request;

    /**
     * Query string parameters ($_GET).
     */
    public ?string $query;

    /**
     * Server and execution environment parameters ($_SERVER).
     */
    public array $server;

    /**
     * Uploaded files ($_FILES).
     */
    public array $files;

    /**
     * Cookies ($_COOKIE).
     */
    public array $cookies;

    /**
     * @var Session
     */
    public Session $session;

    /**
     * Headers (taken from the $_SERVER).
     */
    public array $headers;

    /**
     * @var string|resource|false|null
     */
    protected $content;

    protected ?string $requestUri = null;
    protected ?string $baseUrl = null;
    protected ?string $method = null;
    protected string $defaultLocale = 'en';

    /**
     * Constructor for the Request class.
     * Initializes request data from superglobals ($_GET, $_POST, $_FILES, $_SERVER).
     */
    public function __construct()
    {
        $this->request = array_merge($_POST, $_GET);
        $this->query = $this->query();
        $this->attributes = $this->all();
        $this->cookies = $this->cookie();
        $this->files = $_FILES;
        $this->server = $this->server();
        $this->headers = $this->headers();
        $this->content = $this->content();
        $this->requestUri = $this->getPath();
        $this->baseUrl = base_url();
        $this->method = $this->method();
        $this->session = app(Session::class);
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
     * Retrieves all input data from the request.
     *
     * @return array<string, mixed> The input data.
     */
    public function all(): array
    {
        return $this->request;
    }

    /**
     * Merges new input data into the request.
     *
     * @param array<string, mixed> $input The input data to merge.
     * @return self The current instance.
     */
    public function merge(array $input): self
    {
        $this->request = array_merge($this->all(), $input);
        return $this;
    }

    /**
     * Retrieves the current request URI path.
     *
     * @return string The decoded URI path.
     */
    public function getPath(): string
    {
        return urldecode(
            parse_url($this->server['REQUEST_URI'], PHP_URL_PATH)
        );
    }

    /**
     * Retrieves the HTTP method used for the request.
     *
     * @return string The HTTP method in lowercase.
     */
    public function getMethod(): string
    {
        return strtolower($this->server['REQUEST_METHOD']);
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
        $this->attributes = array_merge($this->attributes, $params);
        return $this;
    }

    /**
     * Retrieves the route parameters.
     *
     * @return array<string, mixed> The route parameters.
     */
    public function getRouteParams(): array
    {
        return $this->attributes;
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
        return $this->attributes[$param] ?? $default;
    }

    /**
     * Retrieves a specific file's information from the request.
     *
     * @param string $param The file parameter name.
     * @return File|null The File object or null if the file doesn't exist.
     */
    public function file(string $param): ?File
    {
        if (isset($this->files[$param])) {
            return new File($this->files[$param]);
        }
        return null;
    }

    /**
     * Get the session instance.
     *
     * @return Session
     */
    public function session(): Session
    {
        return $this->session;
    }
}
