<?php

namespace Zuno\Middleware;

use Zuno\Middleware\Contracts\Middleware;
use Zuno\Http\Response;
use Zuno\Http\Request;
use Zuno\Http\Exceptions\HttpException;
use Closure;

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
        if (
            ($request->isPost() ||
                $request->isPut() ||
                $request->isPatch() ||
                $request->isDelete()) &&
            !$request->has("_token")
        ) {
            throw new HttpException(422, "CSRF Token not found");
        }

        if (
            ($request->isPost() ||
                $request->isPut() ||
                $request->isPatch() ||
                $request->isDelete()) &&
            !hash_equals($request->session()->token(), $request->_token)
        ) {
            throw new HttpException(422, "Unauthorized, CSRF Token mismatched");
        }

        return $next($request);
    }
}
