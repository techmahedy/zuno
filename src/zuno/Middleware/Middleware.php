<?php

namespace Zuno\Middleware;

use Closure;
use Zuno\Http\Request;
use Zuno\Http\Response;
use Zuno\Middleware\Contracts\Middleware as ContractsMiddleware;

class Middleware
{
    /**
     * Closure that handles the request processing.
     *
     * @var Closure(Request, Closure): Response
     */
    public Closure $start;

    /**
     * Initialize the middleware with a default request handler.
     */
    public function __construct()
    {
        $this->start = fn(Request $request, Closure $next): Response => $next($request);
    }

    /**
     * Apply a given middleware to the current middleware chain.
     *
     * @param ContractsMiddleware $middleware The middleware to apply.
     * @return void
     */
    public function applyMiddleware(ContractsMiddleware $middleware): void
    {
        $next = $this->start;

        $this->start = fn(Request $request, Closure $next): Response => $middleware($request, $next);
    }

    /**
     * Handle the incoming request through the middleware chain.
     *
     * @param Request $request The incoming request.
     * @return Response The response returned by the middleware chain.
     */
    public function handle(Request $request): Response
    {
        $finalHandler = fn(Request $request): Response => new Response();

        return ($this->start)($request, $finalHandler);
    }
}
