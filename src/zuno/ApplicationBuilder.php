<?php

namespace Zuno;

use Zuno\Support\Router;
use Zuno\Middleware\Contracts\Middleware as ContractsMiddleware;
use Zuno\Http\Response;
use Zuno\Http\Request;
use Illuminate\Events\Dispatcher;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Container\Container;
use App\Http\Kernel;

class ApplicationBuilder
{
    /**
     * ApplicationBuilder constructor.
     *
     * Initializes the ApplicationBuilder with the application instance and base path.
     * Also instantiates singleton classes required for the application.
     *
     * @param Application $app The application instance.
     * @param string $basePath The base path of the application.
     */
    public function __construct(protected Application $app) {}

    /**
     * Handle global middleware and kernel.
     *
     * @return self
     * @throws \Exception If a middleware dependency is unresolved.
     */
    public function withKernels(): self
    {
        $kernel = app(Kernel::class);

        $globalMiddlewares = [];
        foreach ($kernel->middleware as $middlewareClass) {
            $globalMiddlewares[] = $middlewareClass;
        }

        $request = app(Request::class);
        $finalHandler = fn() => app(Response::class);

        foreach ($globalMiddlewares as $middlewareClass) {
            $middlewareInstance = new $middlewareClass();
            if ($middlewareInstance instanceof ContractsMiddleware) {
                $finalHandler = function (Request $request) use ($middlewareInstance, $finalHandler) {
                    return $middlewareInstance($request, $finalHandler);
                };
            } else {
                throw new \Exception("Unresolved dependency $middlewareClass", 1);
            }
        }

        $response = $kernel->handle($request, $finalHandler);
        $response->send();

        return $this;
    }

    /**
     * Configure Eloquent ORM services.
     *
     * @return self
     */
    public function withEloquentServices(): self
    {
        $capsule = new Capsule;

        $capsule->addConnection([
            'driver'    => env('DB_CONNECTION'),
            'host'      => env('DB_HOST'),
            'database'  => env('DB_DATABASE'),
            'username'  => env('DB_USERNAME'),
            'password'  => env('DB_PASSWORD'),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]);

        $capsule->setEventDispatcher(new Dispatcher(new Container));
        $capsule->bootEloquent();

        return $this;
    }

    /**
     * Build and return the application instance.
     *
     * @return Application
     */
    public function build(): Application
    {
        return $this->app;
    }
}
