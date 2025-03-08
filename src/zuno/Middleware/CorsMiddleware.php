<?php

namespace Zuno\Middleware;

use Closure;
use Zuno\Http\Request;
use Zuno\Http\Response;
use Zuno\Middleware\Contracts\Middleware;

class CorsMiddleware implements Middleware
{
    public function __invoke(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->setHeader('Access-Control-Allow-Origin', '*');
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');

        return $response;
    }
}
