<?php

namespace Zuno\Providers;

use Zuno\Support\UrlGenerator;
use Zuno\Support\Storage\StorageFileService;
use Zuno\Support\Session;
use Zuno\Support\Mail\MailService;
use Zuno\Support\Encryption;
use Zuno\Http\Support\RequestAbortion;
use Zuno\Http\Response;
use Zuno\Http\RedirectResponse;
use Zuno\Config\Config;
use Zuno\Auth\Security\PasswordHashing;
use Zuno\Auth\Security\Authenticate;

/**
 * FacadeServiceProvider is responsible for binding key application services
 * into the service container. These services can then be resolved and used
 * throughout the application via facades or dependency injection.
 *
 * This provider registers singleton instances of various services, ensuring
 * that the same instance is reused whenever the service is requested.
 */
class FacadeServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * This method binds singleton instances of various services into the
     * service container. These services are essential for the application's
     * functionality, such as configuration, session management, authentication,
     * encryption, and more.
     *
     * @return void
     */
    public function register()
    {
        // Bind the 'config' service to a singleton instance of the Config class.
        // This allows the application to access configuration settings globally.
        $this->app->singleton('config', function () {
            return new Config();
        });

        // Bind the 'session' service to a singleton instance of the Session class.
        // This manages user session data throughout the application.
        $this->app->singleton('session', function () {
            return new Session();
        });

        // Bind the 'response' service to a singleton instance of the Response class.
        // This is used to generate HTTP responses.
        $this->app->singleton('response', function () {
            return app(Response::class);
        });

        // Bind the 'hash' service to a singleton instance of the PasswordHashing class.
        // This provides password hashing and verification functionality.
        $this->app->singleton('hash', function () {
            return new PasswordHashing();
        });

        // Bind the 'auth' service to a singleton instance of the Authenticate class.
        // This handles user authentication and authorization.
        $this->app->singleton('auth', function () {
            return new Authenticate();
        });

        // Bind the 'crypt' service to a singleton instance of the Encryption class.
        // This provides encryption and decryption functionality.
        $this->app->singleton('crypt', function () {
            return new Encryption();
        });

        // Bind the 'redirect' service to a singleton instance of the RedirectResponse class.
        // This is used to generate HTTP redirect responses.
        $this->app->singleton('redirect', function () {
            return app(RedirectResponse::class);
        });

        // Bind the 'abort' service to a singleton instance of the RequestAbortion class.
        // This is used to abort requests and return error responses.
        $this->app->singleton('abort', function () {
            return new RequestAbortion();
        });

        // Bind the 'mail' service to a singleton instance of the MailService class.
        // This handles sending emails.
        $this->app->singleton('mail', function () {
            return new MailService();
        });

        // Bind the 'url' service to a singleton instance of the UrlGenerator class.
        // This generates URLs for the application, using the base URL from the environment
        // or configuration.
        $this->app->singleton('url', function () {
            return new UrlGenerator(env('APP_URL') ?? config('app.url'));
        });

        // Bind the 'storage' service to a singleton instance of the Storage class.
        // This handles file uploads.
        $this->app->singleton('storage', function () {
            return new StorageFileService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * This method is called after all service providers have been registered.
     * It can be used to perform additional setup or initialization for the services.
     *
     * @return void
     */
    public function boot()
    {
        // No bootstrapping logic is required for this provider.
    }
}
