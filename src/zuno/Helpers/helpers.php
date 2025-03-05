<?php

use Zuno\Support\Route;
use Zuno\Http\Support\Abort;
use Zuno\Session\Input;
use Zuno\Session\FlashMessage;
use Zuno\Logger\Log as Reader;
use Zuno\Http\Request;
use Zuno\Http\Redirect;
use Zuno\Http\Controllers\Controller;
use Zuno\Config\Config;
use Zuno\Auth\Security\Auth;

/**
 * Renders a view with the given data.
 *
 * @param string $view The name of the view file to render.
 * @param array $data An associative array of data to pass to the view (default is an empty array).
 * @return mixed The rendered view output.
 */
function view($view, $data = []): mixed
{
    static $instance = null;
    if ($instance === null) {
        $instance = new Controller();
    }
    return $instance->render($view, $data);
}

/**
 * Creates a new redirect instance for handling HTTP redirects.
 *
 * @return Redirect A new instance of the Redirect class.
 */
function redirect(): Redirect
{
    static $instance = null;
    if ($instance === null) {
        $instance = new Redirect();
    }
    return $instance;
}

/**
 * Creates a new request instance to handle HTTP requests.
 *
 * @return Request A new instance of the Request class.
 */
function request(): Request
{
    static $instance = null;
    if ($instance === null) {
        $instance = new Request();
    }
    return $instance;
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
    static $instance = null;
    if ($instance === null) {
        $instance = new Reader();
    }
    return $instance->logReader();
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
    $routePath = Route::route($name, $params);
    return $routePath ? $basePath . '/' . ltrim($routePath, '/') : null;
}

/**
 * Retrieve a configuration value by key.
 *
 * @param string $key The configuration key to retrieve.
 * @return string|array|null The configuration value associated with the key, or null if not found.
 */
function config(string $key): null|string|array
{
    return Config::get($key) ?? null;
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
 * Create a new flash message instance.
 *
 * @return FlashMessage Returns a new FlashMessage object.
 */
function flash(): FlashMessage
{
    static $instance = null;
    if ($instance === null) {
        $instance = new FlashMessage();
    }
    return $instance;
}

/**
 * Get the base path of the application.
 *
 * @param string $path An optional path to append to the base path.
 * @return string The full base path.
 */
function base_path(string $path = ''): string
{
    if (php_sapi_name() === 'cli' || defined('STDIN')) {
        return realpath(__DIR__ . '/../../../../../../') . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__, 3);
    return dirname($documentRoot) . ($path ? DIRECTORY_SEPARATOR . $path : '');
}

/**
 * Get the base URL of the application.
 *
 * @param string $path An optional path to append to the base URL.
 * @return string The full base URL.
 */
function base_url(string $path = ''): string
{
    if (php_sapi_name() === 'cli' || defined('STDIN')) {
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
    return base_url('storage') . ($path ? DIRECTORY_SEPARATOR . $path : '');
}

/**
 * Get the public path of the application.
 *
 * @param string $path An optional path to append to the public path.
 * @return string The full public path.
 */
function public_path(string $path = ''): string
{
    return base_url('public') . ($path ? DIRECTORY_SEPARATOR . $path : '');
}

/**
 * Get the resources path of the application.
 *
 * @param string $path An optional path to append to the resources path.
 * @return string The full resources path.
 */
function resource_path(string $path = ''): string
{
    return base_url('resources') . ($path ? DIRECTORY_SEPARATOR . $path : '');
}

/**
 * Generate the URL for an asset in the public directory.
 *
 * @param string $path The path to the asset relative to the public directory.
 * @return string The full URL to the asset.
 */
function enqueue(string $path = ''): string
{
    return base_url('public') . ($path ? '/' . ltrim($path, '/') : '');
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
