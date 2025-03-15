<?php

namespace Zuno\Middleware;

use Closure;
use Zuno\Http\Request;
use Zuno\Http\Response;
use Zuno\Middleware\Contracts\Middleware;

class CorsMiddleware implements Middleware
{
    /**
     * Handles an incoming request and sets the appropriate CORS headers.
     *
     * This middleware is responsible for adding the necessary CORS (Cross-Origin Resource Sharing)
     * headers to the response. These headers allow or restrict web pages from making requests
     * to a different domain than the one that served the web page.
     *
     * The headers set by this middleware include:
     * - Access-Control-Allow-Origin: Allows any domain to access the resource.
     * - Access-Control-Allow-Methods: Specifies the HTTP methods allowed when accessing the resource.
     * - Access-Control-Allow-Headers: Specifies the headers that can be used during the actual request.
     *
     * @param Request $request The incoming request instance.
     * @param Closure $next The next middleware or request handler in the pipeline.
     * @return Response The response with the CORS headers added.
     */
    public function __invoke(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->setHeader('Access-Control-Allow-Origin', '*');
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');

        return $response;
    }
}