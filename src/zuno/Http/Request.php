<?php

namespace Zuno\Http;

use Zuno\Support\Session;
use Zuno\Support\File;
use Zuno\Http\Support\RequestParser;
use Zuno\Http\Support\RequestHelper;
use Zuno\Http\Rule;
use Zuno\Http\InputBag;
use Zuno\Http\HeaderBag;
use Zuno\Http\ServerBag;
use Zuno\Http\ParameterBag;

class Request
{
    use RequestParser, RequestHelper, Rule;

    // Constants representing various HTTP headers used for forwarded requests.
    public const HEADER_FORWARDED = 0b000001;
    public const HEADER_X_FORWARDED_FOR = 0b000010;
    public const HEADER_X_FORWARDED_HOST = 0b000100;
    public const HEADER_X_FORWARDED_PROTO = 0b001000;
    public const HEADER_X_FORWARDED_PORT = 0b010000;
    public const HEADER_X_FORWARDED_PREFIX = 0b100000;

    // Constants for specific proxy headers used by AWS ELB and Traefik.
    public const HEADER_X_FORWARDED_AWS_ELB = 0b0011010;
    public const HEADER_X_FORWARDED_TRAEFIK = 0b0111110;

    // Constants for HTTP request methods.
    public const METHOD_HEAD = "HEAD";
    public const METHOD_GET = "GET";
    public const METHOD_POST = "POST";
    public const METHOD_PUT = "PUT";
    public const METHOD_PATCH = "PATCH";
    public const METHOD_DELETE = "DELETE";
    public const METHOD_PURGE = "PURGE";
    public const METHOD_OPTIONS = "OPTIONS";
    public const METHOD_TRACE = "TRACE";
    public const METHOD_CONNECT = "CONNECT";

    // Mappings for forwarded parameters.
    private const FORWARDED_PARAMS = [
        self::HEADER_X_FORWARDED_FOR => "for",
        self::HEADER_X_FORWARDED_HOST => "host",
        self::HEADER_X_FORWARDED_PROTO => "proto",
        self::HEADER_X_FORWARDED_PORT => "port",
    ];

    // Trusted headers that may be validated.
    private const TRUSTED_HEADERS = [
        self::HEADER_FORWARDED => "FORWARDED",
        self::HEADER_X_FORWARDED_FOR => "X_FORWARDED_FOR",
        self::HEADER_X_FORWARDED_HOST => "X_FORWARDED_HOST",
        self::HEADER_X_FORWARDED_PROTO => "X_FORWARDED_PROTO",
        self::HEADER_X_FORWARDED_PORT => "X_FORWARDED_PORT",
        self::HEADER_X_FORWARDED_PREFIX => "X_FORWARDED_PREFIX",
    ];

    private const VALID_HTTP_METHODS = [
        self::METHOD_GET,
        self::METHOD_HEAD,
        self::METHOD_POST,
        self::METHOD_PUT,
        self::METHOD_DELETE,
        self::METHOD_CONNECT,
        self::METHOD_OPTIONS,
        self::METHOD_PATCH,
        self::METHOD_PURGE,
        self::METHOD_TRACE,
    ];

    public InputBag $request;
    public InputBag $query;
    public ParameterBag $attributes;
    public InputBag $cookies;
    public ServerBag $server;
    public HeaderBag $headers;
    public Session $session;
    public array $files;
    protected $content;
    protected static bool $httpMethodParameterOverride = false;
    protected ?string $requestUri = null;
    protected ?string $baseUrl = null;
    protected ?string $method = null;
    protected string $defaultLocale = "en";

    /**
     * Constructor: Initializes request data from PHP superglobals.
     */
    public function __construct()
    {
        $this->server = new ServerBag($_SERVER);
        $this->headers = new HeaderBag($this->server->getHeaders());
        $this->request = new InputBag($this->createFromGlobals());
        $this->query = new InputBag($_GET);
        $this->attributes = new ParameterBag();
        $this->cookies = new InputBag($_COOKIE);
        $this->files = $_FILES;
        $this->content = $this->content();
        $this->requestUri = $this->getPath();
        $this->baseUrl = base_url();
        $this->method = $this->method();
        $this->session = new Session($_SESSION);
    }

    /**
     * Initializes headers from the $_SERVER superglobal.
     */
    protected function initializeHeaders(): void
    {
        foreach ($this->server->all() as $key => $value) {
            if (str_starts_with($key, "HTTP_")) {
                $header = str_replace("_", "-", substr($key, 5));
                $this->headers->set($header, $value);
            }
        }
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
     * Creates request data from PHP superglobals.
     *
     * @return array The request data.
     */
    public function createFromGlobals(): array
    {
        $request = $_POST + $_GET;

        $contentType = $this->server->get("CONTENT_TYPE", "");
        $requestMethod = strtoupper(
            $this->server->get("REQUEST_METHOD", "GET")
        );

        if (
            str_starts_with(
                $contentType,
                "application/x-www-form-urlencoded"
            ) &&
            in_array($requestMethod, ["PUT", "DELETE", "PATCH"], true)
        ) {
            $rawContent = $this->getContent();
            if ($rawContent !== false) {
                parse_str($rawContent, $data);
                $request = $data;
            } else {
                // Handle error: unable to get raw content
                throw new \RuntimeException(
                    "Unable to retrieve request content."
                );
            }
        }

        return $request;
    }

    /**
     * Retrieves the request body content.
     *
     * @param bool $asResource Whether to return a resource instead of a string.
     * @return string|resource The request content.
     */
    public function getContent(bool $asResource = false)
    {
        $currentContentIsResource = \is_resource($this->content);

        if (true === $asResource) {
            if ($currentContentIsResource) {
                rewind($this->content);
                return $this->content;
            }

            if (\is_string($this->content)) {
                $resource = fopen("php://temp", "r+");
                fwrite($resource, $this->content);
                rewind($resource);
                return $resource;
            }

            $this->content = false;
            return fopen("php://input", "r");
        }

        if ($currentContentIsResource) {
            rewind($this->content);
            return stream_get_contents($this->content);
        }

        if (null === $this->content || false === $this->content) {
            $this->content = file_get_contents("php://input");
        }

        return $this->content;
    }

    /**
     * Checks if the request method is valid.
     *
     * @return bool True if valid, false otherwise.
     */
    public function isValidMethod(): bool
    {
        return in_array(
            strtoupper($this->method),
            [
                self::METHOD_GET,
                self::METHOD_POST,
                self::METHOD_PUT,
                self::METHOD_PATCH,
                self::METHOD_DELETE,
                self::METHOD_OPTIONS,
                self::METHOD_HEAD,
                self::METHOD_TRACE,
                self::METHOD_CONNECT,
                self::METHOD_PURGE,
            ],
            true
        );
    }

    /**
     * Validates the request based on trusted proxies and headers.
     *
     * @return bool True if the request is valid, false otherwise.
     */
    public function isValidRequest(): bool
    {
        if (empty($this->trustedProxies)) {
            return true; // No trusted proxies, all requests are valid.
        }

        $remoteAddress = $this->server->get("REMOTE_ADDR");

        if (!in_array($remoteAddress, $this->trustedProxies, true)) {
            return true; // Request not from a trusted proxy.
        }

        if ($this->trustedHeaderSet === 0) {
            return true; // No trusted headers set, all requests are valid.
        }

        foreach (self::TRUSTED_HEADERS as $headerBit => $headerName) {
            if (($this->trustedHeaderSet & $headerBit) === $headerBit) {
                if (!$this->server->has("HTTP_" . $headerName)) {
                    return false; // Required trusted header is missing.
                }
            }
        }

        return true; // All checks passed, request is valid.
    }

    /**
     * Checks if the request originates from a trusted proxy.
     *
     * This method verifies whether the `REMOTE_ADDR` of the request
     * is included in the list of trusted proxies. If the request
     * comes from a trusted proxy, it returns true.
     *
     * @return bool True if the request is from a trusted proxy, false otherwise.
     */
    public function isFromTrustedProxy(): bool
    {
        return $this->server->has("REMOTE_ADDR") &&
            in_array(
                $this->server->get("REMOTE_ADDR"),
                $this->trustedProxies ?? [],
                true
            );
    }

    /**
     * Retrieves the value of a trusted header from the request.
     *
     * This method checks if a given header (identified by its constant)
     * is present in the request's headers. If found, it returns the header's value;
     * otherwise, it returns null.
     *
     * @param int $headerConstant The constant representing the trusted header.
     * @return string|null The value of the header if it exists, or null if not found.
     */
    public function getTrustedHeaderValue(int $headerConstant): ?string
    {
        $headerName = self::TRUSTED_HEADERS[$headerConstant] ?? null;
        return $headerName ? $this->headers->get($headerName) : null;
    }

    /**
     * Retrieves all input data from the request.
     *
     * @return array<string, mixed> The input data.
     */
    public function all(): array
    {
        return $this->request->all();
    }

    /**
     * Merges new input data into the request.
     *
     * @param array<string, mixed> $input The input data to merge.
     * @return self The current instance.
     */
    public function merge(array $input): self
    {
        $this->request->replace(array_merge($this->request->all(), $input));

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
            parse_url($this->server->get("REQUEST_URI", "/"), PHP_URL_PATH)
        );
    }

    /**
     * Gets the request "intended" method.
     *
     * If the X-HTTP-Method-Override header is set, and if the method is a POST,
     * then it is used to determine the "real" intended HTTP method.
     *
     * The _method request parameter can also be used to determine the HTTP method,
     * but only if enableHttpMethodParameterOverride() has been called.
     *
     * The method is always an uppercased string.
     * @return string
     * @@throws \InvalidArgumentException
     */
    public function getMethod(): string
    {
        $this->method = strtoupper($this->server->get("REQUEST_METHOD", "GET"));

        if ($this->headers->has("X-HTTP-METHOD-OVERRIDE")) {
            $method = strtoupper($this->headers->get("X-HTTP-METHOD-OVERRIDE"));
            $this->headers->set("X-HTTP-METHOD-OVERRIDE", $method);
            self::$httpMethodParameterOverride = true;
        } elseif ($this->request->has("_method")) {
            $method = strtoupper($this->request->get("_method"));
            $this->headers->set("X-HTTP-METHOD-OVERRIDE", $method);
            self::$httpMethodParameterOverride = true;
        } else {
            $method = $this->method;
        }

        if (in_array($method, self::VALID_HTTP_METHODS, true)) {
            $this->method = $method;
            return $this->method;
        }

        throw new \InvalidArgumentException("Invalid HTTP method override: $method.");
    }

    /**
     * Checks if the request method is GET.
     *
     * @return bool True if the method is GET, false otherwise.
     */
    public function isGet(): bool
    {
        return $this->getMethod() === "GET";
    }

    /**
     * Checks if the request method is POST.
     *
     * @return bool True if the method is POST, false otherwise.
     */
    public function isPost(): bool
    {
        return $this->getMethod() === "POST";
    }

    /**
     * Checks if the request method is PUT.
     *
     * @return bool True if the method is PUT, false otherwise.
     */
    public function isPut(): bool
    {
        return $this->getMethod() === "PUT";
    }

    /**
     * Checks if the request method is PATCH.
     *
     * @return bool True if the method is PATCH, false otherwise.
     */
    public function isPatch(): bool
    {
        return $this->getMethod() === "PATCH";
    }

    /**
     * Checks if the request method is DELETE.
     *
     * @return bool True if the method is DELETE, false otherwise.
     */
    public function isDelete(): bool
    {
        return $this->getMethod() === "DELETE";
    }


    /**
     * Sets route parameters for the request.
     *
     * @param array<string, mixed> $params The route parameters.
     * @return self The current instance.
     */
    public function setRouteParams(array $params): self
    {
        $this->attributes->replace(
            array_merge($this->attributes->all(), $params)
        );

        return $this;
    }

    /**
     * Retrieves the route parameters.
     *
     * @return array<string, mixed> The route parameters.
     */
    public function getRouteParams(): array
    {
        return $this->attributes->all();
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

    /**
     * @return Request
     */
    public static function capture()
    {
        return app(Request::class);
    }
}
