<?php

use Zuno\Log as Reader;
use Zuno\Request;
use Zuno\Session;
use Zuno\Redirect;
use Zuno\Controllers\Controller;
use Zuno\Route;

/**
 * Renders a view with the given data.
 *
 * @param	string $view The name of the view file to render.
 * @param	array  $data An associative array of data to pass to the view (default is an empty array).
 * @return	mixed The rendered view output.
 */
function view($view, $data = []): mixed
{
    return (new Controller())->render($view, $data);
}

/**
 * Creates a new redirect instance for handling HTTP redirects.
 *
 * @return	Redirect A new instance of the Redirect class.
 */
function redirect(): Redirect
{
    return new Redirect();
}

/**
 * Creates a new request instance to handle HTTP requests.
 *
 * @return	Request A new instance of the Request class.
 */
function request(): Request
{
    return new Request();
}

/**
 * Creates a new session instance for handling user sessions.
 *
 * @return	Session A new instance of the Session class.
 */
function session(): Session
{
    return new Session();
}

/**
 * Creates and returns a logger instance for logging messages.
 *
 * @return	\Monolog\Logger An instance of the Monolog Logger.
 */
function logger(): \Monolog\Logger
{
    return (new Reader())->logReader();
}

/**
 * Creates and returns a Faker generator instance for generating fake data.
 *
 * @return	\Faker\Generator An instance of the Faker Generator.
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
    // Determine HTTP or HTTPS scheme
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

    // Get the base URL dynamically
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost'; // Fallback to 'localhost' if not set
    $basePath = rtrim($scheme . '://' . $host, '/');

    // Get the route path
    $routePath = Route::route($name, $params);

    return $routePath ? $basePath . '/' . ltrim($routePath, '/') : null;
}
