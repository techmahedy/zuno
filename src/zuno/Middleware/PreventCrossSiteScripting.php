<?php

namespace Zuno\Middleware;

use Closure;
use Zuno\Http\Request;
use Zuno\Middleware\Contracts\Middleware;

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
     * @return mixed The result of the next middleware or request handler.
     * @throws \Exception If the CSRF token is not present in a POST request.
     */
    public function __invoke(Request $request, Closure $next): mixed
    {
        $input = $request->all();
        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });

        collect($request)->merge($input);

        return $next($request);
    }
}
