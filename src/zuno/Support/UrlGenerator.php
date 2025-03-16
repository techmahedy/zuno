<?php

namespace Zuno\Support;

class UrlGenerator
{
    /**
     * The base URL for generating URLs.
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Indicates whether the generated URLs should use HTTPS.
     *
     * @var bool
     */
    protected $secure;

    /**
     * Create a new UrlGenerator instance.
     *
     * @param string $baseUrl The base URL for generating URLs.
     * @param bool $secure Whether to use HTTPS by default.
     */
    public function __construct($baseUrl = '/', $secure = false)
    {
        // Remove any trailing slashes from the base URL to ensure consistency.
        $this->baseUrl = rtrim($baseUrl, '/');

        // Set the default secure flag (HTTPS).
        $this->secure = $secure;
    }

    /**
     * Generate a full URL for the given path.
     *
     * This method constructs a full URL by combining the scheme (HTTP or HTTPS),
     * the base URL, and the provided path. It ensures that the base URL does not
     * already contain a scheme to avoid duplication.
     *
     * @param string $path The path to append to the base URL.
     * @param bool|null $secure Whether to force HTTPS. If null, the default secure flag is used.
     * @return string The fully generated URL.
     */
    public function enqueue($path = '/', $secure = null)
    {
        $secure = $secure ?? $this->secure;

        $scheme = $secure ? 'https://' : 'http://';

        // If the base URL already starts with "http://" or "https://", remove the scheme
        // to avoid duplicating it in the final URL.
        if (strpos($this->baseUrl, 'http://') === 0 || strpos($this->baseUrl, 'https://') === 0) {
            $this->baseUrl = preg_replace('#^https?://#', '', $this->baseUrl);
        }

        // Construct the full URL by combining the scheme, base URL, and path.
        // The path is trimmed of leading slashes to ensure proper formatting.
        return $scheme . $this->baseUrl . '/' . ltrim($path, '/');
    }

    /**
     * Generate a full URL without query parameters.
     *
     * @param string $url The URL to process.
     * @return string The URL without query parameters.
     */
    public function full()
    {
        return base_url() . request()->uri();
    }

    /**
     * Get the current URL without query parameters.
     *
     * This method returns the current URL, excluding any query parameters.
     * It uses the `full()` method and removes the query string if present.
     *
     * @return string The current URL without query parameters.
     */
    public function current()
    {
        // Get the full URL (including query parameters).
        $fullUrl = $this->full();

        // Remove the query string from the URL.
        return strtok($fullUrl, '?');
    }
}
