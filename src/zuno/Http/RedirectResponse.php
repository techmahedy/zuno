<?php

namespace Zuno\Http;

use Zuno\Session\Input;
use Zuno\Http\Response;

class RedirectResponse extends Response
{
    /**
     * Redirect to a specified URL.
     *
     * @param string $url The URL to redirect to.
     * @param int $statusCode The HTTP status code for the redirect (default is 302).
     * @return self The response object with the redirect header.
     */
    public function to(string $url, int $statusCode = 302): RedirectResponse
    {
        // Set the status code for the redirect
        $this->setStatusCode($statusCode);

        // Set the Location header for the redirect
        header('Location: ' . $url, true, $statusCode);
        Input::clear();

        return $this;
    }

    /**
     * Redirect back to the previous page.
     *
     * @return self The response object with the redirect header.
     */
    public function back(): RedirectResponse
    {
        // Get the referring URL or default to '/'
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';

        // Set the status code for the redirect (default is 302)
        $this->setStatusCode(302);

        // Set the Location header for the redirect
        header('Location: ' . $referer);

        return $this;
    }

    /**
     * Flash the current input to the session.
     *
     * @return self The response object for method chaining.
     */
    public function withInput(): RedirectResponse
    {
        Input::flashInput();

        return $this;
    }

    /**
     * Generates a URL for a named route and redirects to it.
     *
     * @param string $name The route name.
     * @param array $params The parameters for the route.
     * @return Response The response object with the redirect header.
     */
    public function route(string $name, array $params = []): RedirectResponse
    {
        // Resolve the route URL
        $url = $this->resolveRouteUrl($name, $params);

        if (!$url) {
            throw new \InvalidArgumentException("Route [{$name}] not found.");
        }

        // Redirect to the resolved URL
        return $this->to($url);
    }

    /**
     * Resolve the URL for a named route.
     *
     * @param string $name The route name.
     * @param array $params The parameters for the route.
     * @return string|null The resolved URL or null if the route doesn't exist.
     */
    protected function resolveRouteUrl(string $name, array $params = []): ?string
    {
        if (!isset(\Zuno\Support\Router::$namedRoutes[$name])) {
            return null;
        }

        $route = \Zuno\Support\Router::$namedRoutes[$name];

        // Replace route parameters with actual values
        foreach ($params as $key => $value) {
            $route = preg_replace('/\{' . $key . '(:[^}]+)?}/', $value, $route, 1);
        }

        return $route;
    }

    /**
     * Flash validation errors to the session.
     *
     * @param array $errors The validation errors to store.
     * @return self The response object for method chaining.
     */
    public function withErrors(array $errors): RedirectResponse
    {
        Input::set('errors', $errors);

        return $this;
    }

    /**
     * Redirect to an external URL.
     *
     * @param string $url The external URL to redirect to.
     * @param int $statusCode The HTTP status code for the redirect (default is 302).
     * @return self The response object with the redirect header.
     */
    public function away(string $url, int $statusCode = 302): RedirectResponse
    {
        return $this->to($url, $statusCode);
    }
}
