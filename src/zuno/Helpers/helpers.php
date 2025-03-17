<?php

use Zuno\Utilities\Paginator;
use Zuno\Support\Session;
use Zuno\Support\Facades\Hash;
use Zuno\Support\Facades\Auth;
use Zuno\Support\Facades\Abort;
use Zuno\Session\Input;
use Zuno\Session\FlashMessage;
use Zuno\Logger\Log as Reader;
use Zuno\Http\Response;
use Zuno\Http\Request;
use Zuno\Http\RedirectResponse;
use Zuno\Http\Controllers\Controller;
use Zuno\DI\Container;
use Zuno\Config\Config;

/**
 * Gets an environment variable from available sources, and provides emulation
 * for unsupported or inconsistent environment variables (i.e., DOCUMENT_ROOT on
 * IIS, or SCRIPT_NAME in CGI mode). Also exposes some additional custom
 * environment information.
 *
 * @param string $key Environment variable name.
 * @param string|float|int|bool|null $default Specify a default value in case the environment variable is not defined.
 * @return string|float|int|bool|null Environment variable setting.
 */
function env(string $key, string|float|int|bool|null $default = null): string|float|int|bool|null
{
    return zunoEnv($key, $default);
}

/**
 * Retrieves an environment variable or returns a default value if the variable is not set.
 *
 * @param string $key Environment variable name.
 * @param string|float|int|bool|null $default Default value to return if the variable is not set.
 * @return string|float|int|bool|null Environment variable value or default.
 */
function zunoEnv(string $key, string|float|int|bool|null $default = null): string|float|int|bool|null
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    return $value !== false ? $value : $default;
}

/**
 * Get the available container instance
 *
 * @param string|class-string|null $abstract
 * @param array  $parameters
 * @return mixed
 */
function app($abstract = null, array $parameters = [])
{
    if (is_null($abstract)) {
        return Container::getInstance();
    }

    return Container::getInstance()->get($abstract, $parameters);
}

/**
 * Creates a new request instance to handle HTTP requests.
 *
 * @return Request A new instance of the Request class.
 */
function request(): Request
{
    return app(Request::class);
}

/**
 * Creates a new response instance to handle HTTP requests.
 *
 * @return Request A new instance of the Request class.
 */
function response(): Response
{
    return app(Response::class);
}

/**
 * Renders a view with the given data.
 *
 * @param string $view The name of the view file to render.
 * @param array $data An associative array of data to pass to the view (default is an empty array).
 * @return mixed The rendered view output.
 */
function view($view, $data = []): Response
{
    $instance = app(Controller::class);

    $content = $instance->render($view, $data, true);

    return new Response($content);
}

/**
 * Creates a new redirect instance for handling HTTP redirects.
 *
 * @return RedirectResponse A new instance of the RedirectResponse class.
 */
function redirect(): RedirectResponse
{
    return app(RedirectResponse::class);
}

/**
 * Creates a new redirect instance for handling HTTP redirects.
 *
 * @return Redirect A new instance of the Redirect class.
 */
function session(): Session
{
    return app(Session::class);
}

/**
 * Fetch csrf token
 *
 * @return null|string
 */
function csrf_token(): ?string
{
    return $_SESSION['_token'];
}

/**
 * Creates a password hashing helper
 *
 * @return string
 */
function bcrypt(string $value): string
{
    return Hash::make($value);
}

/**
 * Retrieves the old input value for a given key from the session.
 *
 * @param mixed $key The key to retrieve the old input for.
 * @return string|null The old input value or null if not found.
 */
function old($key): ?string
{
    return Input::old($key);
}

/**
 * Creates and returns a logger instance for logging messages.
 *
 * @return \Monolog\Logger An instance of the Monolog Logger.
 */
function logger(): \Monolog\Logger
{
    return app(Reader::class)->logReader();
}

/**
 * Creates and returns a Faker generator instance for generating fake data.
 *
 * @return \Faker\Generator An instance of the Faker Generator.
 */
function fake(): \Faker\Generator
{
    $faker = Faker\Factory::create();
    return $faker;
}

/**
 * Generates a full URL for a named route.
 *
 * @param string $name The route name.
 * @param mixed $params The parameters for the route.
 * @return string|null The generated URL or null if the route doesn't exist.
 */
function route(string $name, mixed $params = []): ?string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim($scheme . '://' . $host, '/');
    $routePath = app('route')->route($name, $params);

    return $routePath ? $basePath . '/' . ltrim($routePath, '/') : null;
}

/**
 * Retrieve a configuration value by key.
 *
 * @param string $key The configuration key to retrieve.
 * @param default $kedefault The default configuration key to retrieve.
 * @return string|array|null The configuration value associated with the key, or null if not found.
 */
function config(string $key, ?string $default = null): null|string|array
{
    if (app()->runningInConsole()) {
        return Config::get($key) ?? $default;
    }

    return Config::get($key) ?? $default;
}

/**
 * Check if the user is authenticated.
 *
 * @return bool Returns true if the user is logged in, otherwise false.
 */
function isAuthenticated(): bool
{
    return Auth::check();
}

/**
 * Get the paginator instance
 *
 * @return Paginator
 */
function paginator($data): Paginator
{
    return new Paginator($data);
}

/**
 * Create a new flash message instance.
 *
 * @return FlashMessage Returns a new FlashMessage object.
 */
function flash(): FlashMessage
{
    return app(FlashMessage::class);
}

/**
 * Get the base path of the application.
 *
 * @param string $path An optional path to append to the base path.
 * @return string The full base path.
 */
function base_path(string $path = ''): string
{
    if (app()->runningInConsole()) {
        return realpath(__DIR__ . '/../../../../../../') . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    return BASE_PATH . ($path ? DIRECTORY_SEPARATOR . $path : '');
}

/**
 * Get the base URL of the application.
 *
 * @param string $path An optional path to append to the base URL.
 * @return string The full base URL.
 */
function base_url(string $path = ''): string
{
    if (app()->runningInConsole()) {
        $appUrl = getenv('APP_URL') ?: 'http://localhost';
        return rtrim($appUrl, '/') . ($path ? '/' . ltrim($path, '/') : '');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = $scheme . '://' . $host;

    return rtrim($base, '/') . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Get the storage path of the application.
 *
 * @param string $path An optional path to append to the storage path.
 * @return string The full storage path.
 */
function storage_path(string $path = ''): string
{
    return app()->storagePath() . '/' . $path;
}

/**
 * Get the public path of the application.
 *
 * @param string $path An optional path to append to the public path.
 * @return string The full public path.
 */
function public_path(string $path = ''): string
{
    return app()->publicPath($path) . '/' . $path;;
}

/**
 * Get the resources path of the application.
 *
 * @param string $path An optional path to append to the resources path.
 * @return string The full resources path.
 */
function resource_path(string $path = ''): string
{
    return app()->resourcesPath($path) . '/' . $path;;
}

/**
 * Generate the URL for an asset in the public directory.
 *
 * @param string $path The path to the asset relative to the public directory.
 * @return string The full URL to the asset.
 */
function enqueue(string $path = '', $secure = null): string
{
    return app('url')->enqueue($path, $secure);
}

/**
 * Create a new redirect response to the previous location.
 *
 * @param  int  $status
 * @param  array  $headers
 * @param  mixed  $fallback
 * @return \Zuno\Http\RedirectResponse
 */
function back($status = 302, $headers = [], $fallback = false)
{
    return app('redirect')->back($status, $headers, $fallback);
}

/**
 * Abort the request with a specific HTTP status code and optional message.
 *
 * @param int $code The HTTP status code.
 * @param string $message The optional error message.
 * @throws HttpException
 */
function abort(int $code, string $message = ''): void
{
    Abort::abort($code, $message);
}

/**
 * Abort the request if a condition is true.
 *
 * @param bool $condition The condition to check.
 * @param int $code The HTTP status code.
 * @param string $message The optional error message.
 * @throws HttpException
 */
function abort_if(bool $condition, int $code, string $message = ''): void
{
    Abort::abortIf($condition, $code, $message);
}



/**
 * Mask a string with a specified number of visible characters at the start and end.
 * 
 * @param string $string The string to mask
 * @param int $visibleFromStart Number of visible characters from the start of the string
 * @param int $visibleFromEnd Number of visible characters from the end of the string
 * @param string $maskCharacter The character used to mask the string
 * 
 * @return string The masked string
 */
function maskString(string $string, int $visibleFromStart = 1, int $visibleFromEnd = 1, string $maskCharacter = '*'): string
{
    $length = strlen($string);

    $maskedLength = $length - $visibleFromEnd;

    $startPart = substr($string, 0, $visibleFromStart);
    $endPart = substr($string, -$visibleFromEnd);

    return str_pad($startPart, $maskedLength, $maskCharacter, STR_PAD_RIGHT) . $endPart;
}

/**
 * Truncate a string to a specific length and append a suffix if truncated.
 *
 * @param string $string The input string.
 * @param int $maxLength The maximum allowed length.
 * @param string $suffix The suffix to append if truncated (default: '...').
 * @return string The truncated string.
 */
function truncateString(string $string, int $maxLength, string $suffix = '...'): string
{
    return (strlen($string) > $maxLength) ? substr($string, 0, $maxLength) . $suffix : $string;
}


/**
 * Convert a camelCase string to snake_case.
 *
 * @param string $input The camelCase string.
 * @return string The converted snake_case string.
 */
function camelToSnake(string $input): string
{
    return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $input));
}


/**
 * Convert a snake_case string to camelCase.
 *
 * @param string $input The snake_case string.
 * @return string The converted camelCase string.
 */
function snakeToCamel(string $input): string
{
    return lcfirst(str_replace('_', '', ucwords($input, '_')));
}
