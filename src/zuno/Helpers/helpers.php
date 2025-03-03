<?php

use Zuno\Support\Route;
use Zuno\Http\Request;
use Zuno\Http\Redirect;
use Zuno\Logger\Log as Reader;
use Zuno\Http\Controllers\Controller;
use Zuno\Config\Config;
use Zuno\Auth\Security\Auth;
use Zuno\Session\FlashMessage;
use Zuno\Session\Input;

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

// This function retrieves the old input value for a given key from the session.
// It acts as a wrapper around the Request's `old()` method.
/**
 * @param mixed $key
 * @return string|null
 */
function old($key): ?string
{
    // Calls the `old()` method on the Request class to get the stored old input for the given key.
    // The Request class should store and return the old input values that were flashed to the session.
    return Input::old($key);
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

/**
 * Retrieve a configuration value by key.
 *
 * This function acts as a shorthand to retrieve a configuration value from the 
 * `Config` class. It calls the `Config::get()` method to fetch the value for 
 * a given configuration key. If the key does not exist, it returns `null`.
 *
 * @param string $key The configuration key to retrieve.
 * @return string|null The configuration value associated with the key, or null if not found.
 */
function config(string $key): null|string|array
{
    // Fetch the configuration value using the Config::get method.
    // If the value is not found, it returns null.
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
    return new FlashMessage();
}
