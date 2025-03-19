<?php

namespace Zuno\Middleware;

use Closure;
use Zuno\Http\Request;
use Zuno\Http\Response;
use Zuno\Middleware\Contracts\Middleware;

class CorsMiddleware implements Middleware
{
    /**
     * CORS configuration settings.
     *
     * @var array
     */
    protected $config;

    /**
     * Constructor to load CORS configuration.
     */
    public function __construct()
    {
        $this->config = include app()->basePath() . '/config/cors.php';
    }

    /**
     * Handles an incoming request and sets the appropriate CORS headers.
     *
     * @param Request $request The incoming request instance.
     * @param Closure $next The next middleware or request handler in the pipeline.
     * @return Response The response with the CORS headers added.
     */
    public function __invoke(Request $request, Closure $next): Response
    {
        if ($request->getMethod() === 'OPTIONS') {
            return $this->handlePreflightRequest();
        }

        $response = $next($request);

        return $this->addCorsHeaders($response);
    }

    /**
     * Handle preflight requests.
     *
     * @return Response
     */
    protected function handlePreflightRequest(): Response
    {
        $headers = [
            'Access-Control-Allow-Origin' => $this->getAllowedOrigins(),
            'Access-Control-Allow-Methods' => implode(', ', $this->config['allowed_methods']),
            'Access-Control-Allow-Headers' => implode(', ', $this->config['allowed_headers']),
            'Access-Control-Max-Age' => $this->config['max_age'],
        ];

        return new Response('', 204, $headers);
    }

    /**
     * Add CORS headers to the response.
     *
     * @param Response $response
     * @return Response
     */
    protected function addCorsHeaders(Response $response): Response
    {
        $response->setHeader('Access-Control-Allow-Origin', $this->getAllowedOrigins());
        $response->setHeader('Access-Control-Allow-Methods', implode(', ', $this->config['allowed_methods']));
        $response->setHeader('Access-Control-Allow-Headers', implode(', ', $this->config['allowed_headers']));

        if (!empty($this->config['exposed_headers'])) {
            $response->setHeader('Access-Control-Expose-Headers', implode(', ', $this->config['exposed_headers']));
        }

        if ($this->config['allow_credentials']) {
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }

    /**
     * Get the allowed origins for the CORS header.
     *
     * @return string
     */
    protected function getAllowedOrigins(): string
    {
        $allowedOrigins = $this->config['allowed_origins'];

        // If '*' is specified, allow all origins
        if (in_array('*', $allowedOrigins)) {
            return '*';
        }

        // Otherwise, check if the request origin is in the allowed list
        $origin = request()->headers->get('Origin');
        if (in_array($origin, $allowedOrigins)) {
            return $origin;
        }

        return '';
    }
}
