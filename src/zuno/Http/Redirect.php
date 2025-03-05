<?php

namespace Zuno\Http;

use Zuno\Session\Input;

/**
 * Handles HTTP redirects.
 */
class Redirect extends Response
{
    /**
     * Redirect to a specified URL.
     *
     * This method performs an HTTP redirect to the provided URL with an optional status code.
     *
     * @param string $url The URL to redirect to.
     * @param int $statusCode The HTTP status code for the redirect (default is 302).
     *
     * @return self The response object with the redirect header.
     */
    public function to(string $url, int $statusCode = 302): Response
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
     * This method redirects the user to the referring page, which is typically the previous page they were on.
     *
     * @return self The response object with the redirect header.
     */
    public function back(): Response
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
     * This method stores the current request input in the session so it can be accessed after the redirect.
     *
     * @return self The response object for method chaining.
     */
    public function withInput(): Response
    {
        Input::flashInput();

        return $this;
    }

    /**
     * Flash validation errors to the session.
     *
     * This method stores validation errors in the session so they can be accessed after the redirect.
     *
     * @param array $errors The validation errors to store.
     *
     * @return self The response object for method chaining.
     */
    public function withErrors(array $errors): Response
    {
        Input::set('errors', $errors);

        return $this;
    }
}
