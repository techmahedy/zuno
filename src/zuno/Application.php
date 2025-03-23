<?php

namespace Zuno;

use Zuno\Support\Router;
use Zuno\Providers\ServiceProvider;
use Zuno\Providers\CoreProviders;
use Zuno\Http\Response;
use Zuno\Http\Request;
use Zuno\Http\Exceptions\HttpResponseException;
use Zuno\Http\Exceptions\HttpException;
use Zuno\Error\ErrorHandler;
use Zuno\DI\Container;
use Zuno\Config\Config;
use Zuno\ApplicationBuilder;

class Application extends Container
{
    use CoreProviders;

    /**
     * The current version of the Zuno framework.
     */
    const VERSION = '5.5';

    protected $basePath;
    protected $hasBeenBootstrapped = false;
    protected $booted = false;
    protected $bootstrapPath;
    protected $resourcesPath;
    protected $appPath;
    protected $configPath;
    protected $databasePath;
    protected $publicPath;
    protected $storagePath;
    protected $environmentFile = '.env';
    protected $isRunningInConsole;
    protected $serviceProviders = [];

    /**
     * Application constructor.
     *
     * Initializes the application by:
     * - Setting the application instance in the container.
     * - Loading configuration.
     * - Registering and booting core service providers.
     * - Setting up exception handling.
     * - Defining necessary folder paths.
     * - Detecting if the application is running in the console.
     */
    public function __construct()
    {
        parent::setInstance($this);
        $this->bindSingletonClasses();
        Config::initialize();
        $this->registerCoreProviders();
        $this->bootCoreProviders();
        $this->withExceptionHandler();
        $this->runningInConsole();
    }

    /**
     * Configures the application instance.
     *
     * @return \Zuno\ApplicationBuilder
     *   Returns an ApplicationBuilder instance for further configuration.
     */
    public function configure($app)
    {
        return (new ApplicationBuilder($app))->withMiddlewareStack();
    }

    /**
     * Set the application base path
     *
     * @return self
     */
    public function setBasePath(string $basePath): self
    {
        $this->basePath = $basePath;
        $this->setNecessaryFolderPath();

        return $this;
    }

    /**
     * Registers the exception handler for the application.
     *
     * @return self
     */
    public function withExceptionHandler(): self
    {
        ErrorHandler::handle();

        return $this;
    }

    /**
     * Registers core service providers.
     * If the application is running in the console, it skips registration.
     * @return self
     */
    public function registerCoreProviders(): self
    {
        $coreProviders = $this->loadCoreProviders();
        $this->registerProviders($coreProviders ?? []);
        $this->registerProviders(config('app.providers'));

        return $this;
    }

    /**
     * Boots core service providers.
     *
     * If the application is running in the console, it skips booting.
     *
     * @return self
     *   Returns the current instance for method chaining.
     */
    public function bootCoreProviders(): self
    {
        $coreProviders = $this->loadCoreProviders();

        $this->bootProviders($coreProviders ?? []);
        $this->bootProviders(config('app.providers'));

        return $this;
    }

    /**
     * Registers a list of service providers.
     *
     * @param array|null $providers
     *   An array of service provider classes to register.
     */
    protected function registerProviders(?array $providers = []): void
    {
        foreach ($providers ?? [] as $provider) {
            $providerInstance = new $provider($this);
            if ($providerInstance instanceof ServiceProvider) {
                $providerInstance->register();
                $this->serviceProviders[] = $providerInstance;
            }
        }
    }

    /**
     * Boots a list of service providers.
     *
     * @param array|null $providers
     *   An array of service provider classes to boot.
     */
    protected function bootProviders(?array $providers = []): void
    {
        foreach ($providers ?? [] as $provider) {
            $providerInstance = new $provider($this);
            if ($providerInstance instanceof ServiceProvider && in_array($providerInstance, $this->serviceProviders)) {
                $providerInstance->boot();
                $this->bootstrap();
                $this->bootServices();
            }
        }
    }

    /**
     * Gets the base path of the application.
     *
     * @return string
     *   The base path of the application.
     */
    public function getBasePath(): string
    {
        return $this->basePath = base_path();
    }

    /**
     * Sets necessary folder paths for the application.
     */
    protected function setNecessaryFolderPath(): void
    {
        $this->basePath = $this->basePath();
        $this->configPath = $this->configPath();
        $this->appPath = $this->appPath();
        $this->bootstrapPath = $this->bootstrapPath();
        $this->databasePath = $this->databasePath();
        $this->publicPath = $this->publicPath();
        $this->storagePath = $this->storagePath();
        $this->resourcesPath = $this->resourcesPath();
    }

    /**
     * Returns the path for a given folder name.
     *
     * @param string $folder
     *   The folder name.
     * @return string
     *   The full path to the folder.
     */
    protected function getPath(string $folder): string
    {
        return base_path($folder);
    }

    /**
     * Gets the resources path.
     *
     * @return string
     *   The path to the resources folder.
     */
    public function resourcesPath($path = ''): string
    {
        return $this->resourcesPath = $this->getPath("resources/{$path}");
    }

    /**
     * Gets the bootstrap path.
     *
     * @return string
     *   The path to the bootstrap folder.
     */
    public function bootstrapPath($path = ''): string
    {
        return $this->bootstrapPath = $this->getPath("bootstrap/{$path}");
    }

    /**
     * Gets the database path.
     *
     * @return string
     *   The path to the database folder.
     */
    public function databasePath($path = ''): string
    {
        return $this->databasePath = $this->getPath("database/{$path}");
    }

    /**
     * Gets the public path.
     *
     * @return string
     *   The path to the public folder.
     */
    public function publicPath($path = ''): string
    {
        return $this->publicPath = $this->getPath("public/{$path}");
    }

    /**
     * Gets the storage path.
     *
     * @return string
     *   The path to the storage folder.
     */
    public function storagePath($path = ''): string
    {
        return $this->storagePath = $this->getPath("storage/{$path}");
    }

    /**
     * Gets the application path.
     *
     * @return string
     *   The path to the application folder.
     */
    public function appPath(): string
    {
        return $this->appPath = $this->basePath();
    }

    /**
     * Gets the base path of the application.
     *
     * @return string
     *   The base path of the application.
     */
    public function basePath(): string
    {
        return $this->basePath = $this->getBasePath();
    }

    /**
     * Gets the configuration path.
     *
     * @return string
     *   The path to the configuration folder.
     */
    public function configPath($path = ''): string
    {
        return $this->configPath = $this->getPath("config/{$path}");
    }

    /**
     * Determines if the application is running in the console.
     *
     * @return bool
     *   True if running in the console, false otherwise.
     */
    public function runningInConsole(): bool
    {
        if ($this->isRunningInConsole === null) {
            $this->isRunningInConsole = env('APP_RUNNING_IN_CONSOLE') ?? (\PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg');
        }

        return $this->isRunningInConsole;
    }

    /**
     * Checks if the application has been bootstrapped.
     *
     * @return bool
     *   True if bootstrapped, false otherwise.
     */
    public function hasBeenBootstrapped(): bool
    {
        return $this->hasBeenBootstrapped;
    }

    /**
     * Checks if the application has booted.
     *
     * @return bool
     *   True if booted, false otherwise.
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Bootstraps the application.
     */
    public function bootstrap(): void
    {
        if (!$this->hasBeenBootstrapped) {
            $this->hasBeenBootstrapped = true;
        }
    }

    /**
     * Boots the application services.
     */
    public function bootServices(): void
    {
        if (!$this->booted) {
            $this->booted = true;
        }
    }

    /**
     * Resolves a service from the container.
     *
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     */
    public function make($abstract, array $parameters = [])
    {
        return parent::get($abstract, $parameters);
    }

    /**
     * Bind all the application core singleton classes
     * @return void
     */
    public function bindSingletonClasses(): void
    {
        $this->singleton(Request::class, Request::class);
        $this->singleton(Router::class, Router::class);
        $this->singleton(Response::class, Response::class);
    }


    /**
     * Dispatches the application request.
     *
     * Resolves the request using the router and sends the response.
     * Handles any HTTP exceptions that may occur during the process.
     */
    public function dispatch($request): void
    {
        try {
            $response = app('route')->resolve($this, $request);
            $response->prepare($request);

            if ($response instanceof Response) {
                $response->send();
            } else {
                echo $response;
            }
        } catch (HttpException $exception) {
            if ($request->isAjax()) {
                throw new HttpResponseException(
                    $exception->getMessage(),
                    $exception->getCode()
                );
            }

            Response::dispatchHttpException($exception);
        }
    }
}
