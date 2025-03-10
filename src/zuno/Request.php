<?php

namespace Zuno;

use Zuno\File;

class Request extends Rule
{
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

    public function __construct()
    {
        $this->files = $_FILES;
    }

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
     * Retrieves all input data, sanitizing it based on the request method.
     *
     * @return array<string, mixed> Sanitized input data.
     */
    public function all(): array
    {
        $body = [];
        $inputSource = $this->getMethod() === 'get' ? $_GET : $_POST;

        foreach ($inputSource as $key => $value) {
            $body[$key] = filter_input(
                $this->getMethod() === 'get' ? INPUT_GET : INPUT_POST,
                $key,
                FILTER_SANITIZE_SPECIAL_CHARS
            );
        }

        return $body;
    }

    /**
     * Retrieves a specific input parameter or all input data.
     *
     * @param string $param The parameter to retrieve.
     * @return mixed The input value if the parameter exists, otherwise the whole input array.
     */
    public function input(string $param): mixed
    {
        // Populate the input array if it's empty
        if (empty($this->input)) {
            $this->input = $this->all();
        }

        return $this->input[$param] ?? null;
    }

    /**
     * Checks if a specific parameter exists in the input data.
     *
     * @param string $param The parameter to check.
     * @return bool True if the parameter exists, false otherwise.
     */
    public function has(string $param): bool
    {
        if (empty($this->input)) {
            $this->input = $this->all();
        }

        return array_key_exists($param, $this->input);
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

    /**
     * Stores the provided input data in the session for later retrieval.
     * This is typically used when you want to "flash" the input data for the next request,
     * such as when a form submission fails, and you want to retain the user's input.
     *
     * @param array|null $data The input data to store in the session. If null, it clears the stored input.
     * 
     * @return void
     */
    public static function flashInput(?array $data)
    {
        // Store the provided input data in the session under the 'input' key
        // This allows the data to be accessible across subsequent requests
        $_SESSION['input'] = $data;
    }

    /**
     * Retrieves the old input data that was previously stored in the session.
     * If a specific key is provided, it will return the value associated with that key;
     * otherwise, it returns all stored input data.
     *
     * This is commonly used to retain form values between requests, for example, 
     * when a form is redisplayed after a validation failure.
     *
     * @param string|null $key The key for a specific input value (e.g., 'email'). If null, returns all stored input.
     *
     * @return string|null
     *
     */
    public static function old(?string $key = null): ?string
    {
        // Retrieve the stored input data
        $input = $_SESSION['input'] ?? [];

        // If a specific key is requested, get that value
        $value = $key ? ($input[$key] ?? null) : $input;

        return $value;
    }
}
