<?php

namespace Zuno\Middleware;

use Closure;
use Zuno\Http\Request;
use Zuno\Http\Response;
use Zuno\Middleware\Contracts\Middleware;

class CsrfTokenMiddleware implements Middleware
{
    /**
     * Handles an incoming request and verifies the CSRF token.
     *
     * This middleware checks if the request is a POST request and if the 
     * CSRF token is present. If the CSRF token is missing, an exception is thrown.
     *
     * @param Request $request The incoming request instance.
     * @param Closure $next The next middleware or request handler.
     * @return Zuno\Http\Response
     * @throws \Exception
     */
    public function __invoke(Request $request, Closure $next): Response
    {
        if ($request->isPost() && !$request->has('_token')) {
            throw new \Exception("CSRF Token not found", 422);
        }

        if ($request->isPost() && ($_SESSION['_token'] !== $request->_token)) {
            throw new \Exception("CSRF Token mismatched", 422);
        }

        return $next($request);
    }
}
