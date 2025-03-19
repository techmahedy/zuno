<?php

namespace Zuno\Middleware;

use Zuno\Support\Facades\Auth;
use Zuno\Middleware\Contracts\Middleware;
use Zuno\Http\Response;
use Zuno\Http\Request;
use Zuno\Http\Exceptions\HttpResponseException;
use Zuno\Http\Exceptions\HttpException;
use Closure;

class CsrfTokenMiddleware implements Middleware
{
    /**
     * Handles an incoming request and verifies the CSRF token.
     *
     * This middleware checks if the request is a POST, PUT, PATCH, or DELETE request
     * and if the CSRF token is present and valid. If the CSRF token is missing or invalid,
     * a JSON response with a 422 status code is returned for AJAX requests, and an exception
     * is thrown for non-AJAX requests.
     *
     * @param Request $request The incoming request instance.
     * @param Closure $next The next middleware or request handler.
     * @return Zuno\Http\Response
     * @throws HttpException
     */
    public function __invoke(Request $request, Closure $next): Response
    {
        $token = $request->headers->get('X-CSRF-TOKEN') ?? $request->_token;
        $request->merge(['name' => 'mahedi']);
        if ($this->isModifyingRequest($request) && empty($token)) {
            return $this->handleError($request, "Unauthorized, CSRF Token not found");
        }

        if ($this->isModifyingRequest($request) && !hash_equals($request->session()->token(), $token)) {
            return $this->handleError($request, "Unauthorized, CSRF Token mismatched");
        }

        return $next($request);
    }

    /**
     * Checks if the request is a modifying request (POST, PUT, PATCH, DELETE).
     *
     * @param Request $request The incoming request instance.
     * @return bool
     */
    protected function isModifyingRequest(Request $request): bool
    {
        return $request->isPost() || $request->isPut() || $request->isPatch() || $request->isDelete();
    }

    /**
     * Handles CSRF validation errors.
     *
     * @param Request $request The incoming request instance.
     * @param string $message The error message.
     * @return Response
     * @throws HttpException
     */
    protected function handleError(Request $request, string $message): Response
    {
        if ($request->isAjax()) {
            throw new HttpResponseException(
                response()->json(['errors' => $message]),
                $message,
                Response::HTTP_UNAUTHORIZED
            );
        }

        throw new HttpException(Response::HTTP_UNAUTHORIZED, $message);
    }
}
