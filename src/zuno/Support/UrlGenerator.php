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
     * The path for the URL.
     *
     * @var string
     */
    protected $path = '/';

    /**
     * The query parameters for the URL.
     *
     * @var array
     */
    protected $query = [];

    /**
     * The fragment for the URL.
     *
     * @var string
     */
    protected $fragment = '';

    /**
     * The expiration time for signed URLs.
     *
     * @var int
     */
    protected $expiration = 0;

    /**
     * Create a new UrlGenerator instance.
     *
     * @param string $baseUrl The base URL for generating URLs.
     * @param bool $secure Whether to use HTTPS by default.
     */
    public function __construct($baseUrl = '/', $secure = false)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->secure = $secure;
    }

    /**
     * Generate a full URL for the given path.
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
     * @return string The URL without query parameters.
     */
    public function full()
    {
        return base_url() . request()->uri();
    }

    /**
     * Get the current URL without query parameters.
     *
     * @return string The current URL without query parameters.
     */
    public function current()
    {
        $fullUrl = $this->full();

        return strtok($fullUrl, '?');
    }

    /**
     * Generate a URL for a named route.
     *
     * @param string $name The route name.
     * @param array $parameters Route parameters.
     * @param bool|null $secure Whether to force HTTPS.
     * @return string The route URL.
     */
    public function route($name, array $parameters = [], $secure = null)
    {
        $path = app('route')->route($name, $parameters);

        return $this->enqueue($path, $secure);
    }
    /**
     * Set the base path for the URL.
     *
     * @param string $path The path to append to the base URL.
     * @return self
     */
    public function to($path = '/')
    {
        $this->path = ltrim($path, '/');

        return $this;
    }

    /**
     * Add query parameters to the URL.
     *
     * @param array $query An associative array of query parameters.
     * @return self
     */
    public function withQuery(array $query = [])
    {
        $this->query = array_merge($this->query, $query);

        return $this;
    }

    /**
     * Add a signature to the URL.
     *
     * @param int $expiration Expiration time in seconds.
     * @return self
     */
    public function withSignature($expiration = 3600)
    {
        $this->expiration = $expiration;

        return $this;
    }

    /**
     * Add a fragment to the URL.
     *
     * @param string $fragment The fragment to append (e.g., "#section").
     * @return self
     */
    public function withFragment($fragment = '')
    {
        $this->fragment = ltrim($fragment, '#');
        return $this;
    }

    /**
     * Generate the final URL.
     *
     * @return string The fully generated URL.
     */
    public function make()
    {
        $scheme = $this->secure ? 'https://' : 'http://';

        if (strpos($this->baseUrl, 'http://') === 0 || strpos($this->baseUrl, 'https://') === 0) {
            $this->baseUrl = preg_replace('#^https?://#', '', $this->baseUrl);
        }

        $url = $scheme . $this->baseUrl . '/' . $this->path;

        $queryParameters = $this->query;

        if ($this->expiration > 0) {
            $queryParameters['expires'] = time() + $this->expiration;
            $queryParameters['signature'] = $this->createSignature($queryParameters);
        }

        if (!empty($queryParameters)) {
            $url .= '?' . http_build_query($queryParameters);
        }

        if (!empty($this->fragment)) {
            $url .= '#' . $this->fragment;
        }

        return $url;
    }

    /**
     * Generate a signed URL.
     *
     * @param string $path The base path.
     * @param array $parameters Query parameters.
     * @param int $expiration Expiration time in seconds.
     * @param bool|null $secure Whether to force HTTPS.
     * @return string The signed URL.
     */
    public function signed($path = '/', array $parameters = [], $expiration = 3600, $secure = null)
    {
        $url = $this->enqueue($path, $secure);

        $parameters['expires'] = time() + $expiration;
        $parameters['signature'] = $this->createSignature($parameters);

        return $url . '?' . http_build_query($parameters);
    }

    /**
     * Create a signature for signed URLs.
     *
     * @param array $parameters The parameters to sign.
     * @return string The signature.
     */
    protected function createSignature(array $parameters)
    {
        $secret = config('app.key');

        return hash_hmac('sha256', http_build_query($parameters), $secret);
    }

    /**
     * Check if a URL is valid.
     *
     * @param string $url The URL to validate.
     * @return bool True if the URL is valid, false otherwise.
     */
    public function isValid(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Get the base URL.
     *
     * @return string The base URL.
     */
    public function base()
    {
        return $this->baseUrl;
    }

    /**
     * Set whether to use HTTPS by default.
     *
     * @param bool $secure Whether to use HTTPS.
     * @return self
     */
    public function setSecure(bool $secure)
    {
        $this->secure = $secure;

        return $this;
    }
}
