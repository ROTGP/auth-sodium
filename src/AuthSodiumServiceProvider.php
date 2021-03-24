<?php

namespace ROTGP\AuthSodium;

use ROTGP\AuthSodium\Http\Middleware\AuthSodiumMiddleware;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Router;

use Artisan;
use Auth;

class AuthSodiumServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(Router $router, Kernel $kernel)
    {
        $this->registerRoutes();
        
        $router->aliasMiddleware('auth.sodium', AuthSodiumMiddleware::class);

        if ($this->app->runningInConsole()) {

            // @SEE: https://laravel.com/docs/8.x/packages#migrations
            $this->loadMigrationsFrom( __DIR__.'/../migrations/2000_01_01_000000_create_auth_sodium_tables.php');

            /**
             * @NOTE: the following can be published in
             * the consuming app by calling the following: 
             * php artisan vendor:publish --provider="ROTGP\AuthSodium\AuthSodiumServiceProvider" --tag="config"
             */
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('authsodium.php'),
            ], 'config');
        }

         // https://laravelpackage.com/09-routing.html#views
         $this->loadViewsFrom(__DIR__.'/../resources/views', 'authsodium');

         $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'authsodium');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'authsodium');
    }

    protected function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/routes.php');
        });
    }

    protected function routeConfiguration()
    {
        return [
            'prefix' => config('authsodium.prefix')
        ];
    }
}