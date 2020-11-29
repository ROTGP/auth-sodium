<?php

namespace ROTGP\AuthSodium;

use ROTGP\AuthSodium\AuthSodiumMiddleware;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel;
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
        // $router = $this->app['router'];
        // dd($router);
         
        // $router->aliasMiddleware('mymid', MyMiddleware::class);

        // dd($router);
        // $kernel = $this->app->make(Kernel::class);
        // $kernel->pushMiddleware(AuthSodiumMiddleware::class);
        // @TODO investigate ->prependMiddleware()
        $router->aliasMiddleware('auth.sodium', AuthSodiumMiddleware::class);

        if ($this->app->runningInConsole()) {

            // @SEE: https://laravel.com/docs/8.x/packages#migrations
            $this->loadMigrationsFrom( __DIR__.'/../migrations/2014_10_10_000000_create_auth_sodium_tables.php');

            /**
             * @NOTE: the following can be published in
             * the consuming app by calling the following: 
             * php artisan vendor:publish --provider="ROTGP\AuthSodium\AuthSodiumServiceProvider" --tag="config"
             */
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('authsodium.php'),
            ], 'config');
          }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'authsodium');
    }
}