<?php

namespace Zuno\Providers;

use Zuno\Support\Mail\MailService;
use Zuno\Support\Encryption;
use Zuno\Http\Support\RequestAbortion;
use Zuno\Http\Response;
use Zuno\Http\RedirectResponse;
use Zuno\Config\Config;
use Zuno\Auth\Security\PasswordHashing;
use Zuno\Auth\Security\Authenticate;
use Zuno\Support\Session;
use Zuno\Support\UrlGenerator;

class FacadeServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('config', function () {
            return new Config();
        });

        $this->app->singleton('session', function () {
            return new Session();
        });

        $this->app->singleton('response', function () {
            return app(Response::class);
        });

        $this->app->singleton('hash', function () {
            return new PasswordHashing();
        });

        $this->app->singleton('auth', function () {
            return new Authenticate();
        });

        $this->app->singleton('crypt', function () {
            return new Encryption();
        });

        $this->app->singleton('redirect', function () {
            return app(RedirectResponse::class);
        });

        $this->app->singleton('abort', function () {
            return new RequestAbortion();
        });

        $this->app->singleton('mail', function () {
            return new MailService();
        });

        $this->app->singleton('url', function () {
            return new UrlGenerator(env('APP_URL') ?? config('app.url'));
        });
    }

    public function boot() {}
}
