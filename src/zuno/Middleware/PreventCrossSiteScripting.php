<?php

namespace Zuno\Middleware;

use Zuno\Middleware\Contracts\Middleware;
use Zuno\Http\Response;
use Zuno\Http\Request;
use Closure;

class PreventCrossSiteScripting implements Middleware
{
    /**
     * Handles an incoming request and verifies the CSRF token.
     *
     * This middleware checks if the request is a POST request and if the 
     * CSRF token is present. If the CSRF token is missing, an exception is thrown.
     *
     * @param Request $request The incoming request instance.
     * @param Closure $next The next middleware or request handler.
     * @return Response The result of the next middleware or request handler.
     * @throws \Exception If the CSRF token is not present in a POST request.
     */
    public function __invoke(Request $request, Closure $next): Response
    {
        $input = $request->all();

        array_walk_recursive($input, function (&$input) {
            $input = strip_tags(html_entity_decode($input));
        });

        $request->merge($input);

        return $next($request);
    }
}
