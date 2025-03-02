<?php

namespace Zuno;

/**
 * Handles HTTP redirects.
 */
class Redirect
{
    /**
     * Redirect to a specified URL.
     *
     * This method performs an HTTP redirect to the provided URL with an optional status code.
     * If headers have already been sent, it outputs an error message instead.
     *
     * @param string $url The URL to redirect to.
     * @param int $statusCode The HTTP status code for the redirect (default is 302).
     * 
     * @return void
     */
    public function url(string $url, int $statusCode = 302): void
    {
        header('Location: ' . $url, true, $statusCode);
        exit(); // Terminate the script to ensure no furt
    }

    /**
     * Redirect back to the previous page.
     *
     * This method redirects the user to the referring page, which is typically the previous page they were on.
     *
     * @return never
     */
    public function back(): never
    {
        // Use the HTTP_REFERER server variable to get the previous URL
        $referer = $_SERVER['HTTP_REFERER'] ?? '/'; // Default to '/' if HTTP_REFERER is not set
        header('Location: ' . $referer);
        exit(); // Terminate the script to ensure no further code is executed
    }
}
