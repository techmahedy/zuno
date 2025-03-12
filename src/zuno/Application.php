<?php

namespace Zuno;

use Zuno\Http\Response;
use Zuno\Http\Exceptions\HttpException;
use Zuno\DI\Container;
use Zuno\Support\Router;

class Application extends Container
{
    /**
     * The Zuno framework version.
     *
     * @var string
     */
    const VERSION = '4.1';

    /**
     * The base path for the Laravel installation.
     *
     * @var string
     */
    protected $basePath;

    /**
     * Indicates if the application has been bootstrapped before.
     *
     * @var bool
     */
    protected $hasBeenBootstrapped = false;

    /**
     * Indicates if the application has "booted".
     *
     * @var bool
     */
    protected $booted = false;

    /**
     * The custom bootstrap path defined by the developer.
     *
     * @var string
     */
    protected $bootstrapPath;

    /**
     * Resources path where the views are located
     *
     * @var string
     */
    protected $resourcesPath;

    /**
     * The custom application path defined by the developer.
     *
     * @var string
     */
    protected $appPath;

    /**
     * The custom configuration path defined by the developer.
     *
     * @var string
     */
    protected $configPath;

    /**
     * The custom database path defined by the developer.
     *
     * @var string
     */
    protected $databasePath;

    /**
     * The custom public / web path defined by the developer.
     *
     * @var string
     */
    protected $publicPath;

    /**
     * The custom storage path defined by the developer.
     *
     * @var string
     */
    protected $storagePath;

    /**
     * The environment file to load during bootstrapping.
     *
     * @var string
     */
    protected $environmentFile = '.env';

    /**
     * Indicates if the application is running in the console.
     *
     * @var bool|null
     */
    protected $isRunningInConsole;

    /**
     * Application constructor.
     *
     * Initializes the application by setting necessary folder paths,
     * determining if it's running in the console, and setting the instance.
     */
    public function __construct()
    {
        $this->setNecessaryFolderPath();
        $this->runningInConsole();
        $this->hasBeenBootstrapped();
        $this->isBooted();
        parent::setInstance($this);
    }

    /**
     * Configures the application instance.
     *
     * @return \Zuno\ApplicationBuilder
     */
    public static function configure($basePath = null)
    {
        $app = new static();
        self::setInstance($app);

        return (new \Zuno\ApplicationBuilder($app, $basePath))
            ->withEnvironments()
            ->withAppSession()
            ->withKernels()
            ->withBootedProviders()
            ->withBootingProviders()
            ->withEloquentServices()
            ->withExceptionHandler();
    }

    /**
     * Get the application base path
     *
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath = base_path();
    }

    /**
     * Binds all of the application paths in the container.
     *
     * @return void
     */
    protected function setNecessaryFolderPath()
    {
        $this->basePath();
        $this->configPath();
        $this->appPath();
        $this->bootstrapPath();
        $this->databasePath();
        $this->publicPath();
        $this->storagePath();
        $this->resourcesPath();
    }

    /**
     * Returns the resources path.
     *
     * @return string
     */
    public function resourcesPath(): string
    {
        return $this->resourcesPath = base_path('resources');
    }

    /**
     * Returns the bootstrap path.
     *
     * @return string
     */
    public function bootstrapPath(): string
    {
        return $this->bootstrapPath = base_path('bootstrap');
    }

    /**
     * Returns the database path.
     *
     * @return string
     */
    public function databasePath(): string
    {
        return $this->databasePath = base_path('database');
    }

    /**
     * Returns the public path.
     *
     * @return string
     */
    public function publicPath(): string
    {
        return $this->publicPath = base_path('public');
    }

    /**
     * Returns the storage path.
     *
     * @return string
     */
    public function storagePath(): string
    {
        return $this->storagePath = base_path('storage');
    }

    /**
     * Returns the application path.
     *
     * @return string
     */
    public function appPath(): string
    {
        return $this->appPath = $this->basePath();
    }

    /**
     * Returns the base path of the application.
     *
     * @return string
     */
    public function basePath(): string
    {
        return $this->basePath = $this->getBasePath();
    }

    /**
     * Returns the configuration path.
     *
     * @return string
     */
    public function configPath(): string
    {
        return $this->configPath = base_path('config');
    }

    /**
     * Determines if the application is running in the console.
     *
     * @return bool
     */
    public function runningInConsole()
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
     */
    public function hasBeenBootstrapped(): bool
    {
        return $this->hasBeenBootstrapped;
    }

    /**
     * Checks if the application has booted.
     *
     * @return bool
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Bootstraps the application.
     *
     * Sets the `hasBeenBootstrapped` flag to true if not already set.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        if ($this->hasBeenBootstrapped) {
            return;
        }

        $this->hasBeenBootstrapped = true;
    }

    /**
     * Boots the application.
     *
     * Sets the `booted` flag to true if not already set.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function make($abstract, array $parameters = [])
    {
        return parent::get($abstract, $parameters);
    }

    /**
     * Dispatches the application request.
     *
     * Resolves the request using the router and sends the response.
     * Handles any HTTP exceptions that may occur during the process.
     *
     * @return void
     */
    public function dispatch(): void
    {
        try {
            $response = app(Router::class)->resolve($this, request());

            if ($response instanceof \Zuno\Http\Response) {
                $response->send();
            } else {
                echo $response;
            }
        } catch (HttpException $exception) {
            Response::dispatchHttpException($exception);
        }
    }
}
