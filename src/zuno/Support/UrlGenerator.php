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
        // Use the provided secure flag or fall back to the default secure flag.
        $secure = $secure ?? $this->secure;

        // Determine the scheme (HTTP or HTTPS) based on the secure flag.
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
}
