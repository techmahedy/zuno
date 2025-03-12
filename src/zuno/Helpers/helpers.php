<?php

use Zuno\Application;
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
    if (php_sapi_name() === 'cli' || defined('STDIN')) {
        return \Zuno\Config\Config::get($key) ?? $default;
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
    if (php_sapi_name() === 'cli' || defined('STDIN')) {
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
 * Remove base URL from the URL
 *
 * @param string
 * @return string
 */
function removeBaseUrl($url)
{
    $baseUrl = base_url();

    $fileName = str_replace($baseUrl, '', $url);

    return ltrim($fileName, '/');
}

/**
 * Get the storage path of the application.
 *
 * @param string $path An optional path to append to the storage path.
 * @return string The full storage path.
 */
function storage_path(string $path = ''): string
{
    if (php_sapi_name() === 'cli' || defined('STDIN')) {
        return base_url('storage') . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    return app()->storagePath($path);
}

/**
 * Get the public path of the application.
 *
 * @param string $path An optional path to append to the public path.
 * @return string The full public path.
 */
function public_path(string $path = ''): string
{
    if (php_sapi_name() === 'cli' || defined('STDIN')) {
        return base_url('public') . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
    return app()->publicPath($path);
}

/**
 * Get the resources path of the application.
 *
 * @param string $path An optional path to append to the resources path.
 * @return string The full resources path.
 */
function resource_path(string $path = ''): string
{
    return app()->resourcesPath($path);
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
