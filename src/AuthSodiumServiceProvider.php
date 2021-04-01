<?php

namespace ROTGP\AuthSodium;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;

use Config;
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
        $middlewareName = authSodium()->middlewareName();
        $middlewareGroup = authSodium()->middlewareGroup();
        $useGlobalMiddleware = authSodium()->useGlobalMiddleware();
        $usingMiddleware = !empty($middlewareName) || 
            !empty($middlewareGroup) ||
            $useGlobalMiddleware === true;

        /**
         * https://yish.dev/ordering-laravel-middleware-priority
         * We want this to run early, so we prepend it
         * to the beginning of the array.
         */
        if ($usingMiddleware)
            $kernel->prependToMiddlewarePriority(config('authsodium.delegate'));

        /**
         * This will run the middleware ONLY if the
         * route specifies it, ie: 
         *
         * `Route::resource('foos', FooController::class)->middleware('authsodium');`
         */
        if ($middlewareName !== null)
            $router->aliasMiddleware($middlewareName, config('authsodium.delegate'));
        
        /**
         * This adds the AuthSodium middleware to the
         * 'web' group (for example), as such, the
         * middleware will automatically run when the
         * route contains 'web' middleware
         *
         * `Route::resource('foos', FooController::class)->middleware('web');`
         *
         * Or of course when the route exists in
         * `routes/web.php`
         */
        if ($middlewareGroup !== null)
            $router->pushMiddlewareToGroup($middlewareGroup, config('authsodium.delegate'));

        /**
         * This will run the AuthSodium middleware on
         * ALL requests, regardless of the middleware
         * specified for the route. Not very flexible as
         * soon routes will not need an auth user (ie,
         * user registration).
         */
        if ($useGlobalMiddleware === true)
            $kernel->pushMiddleware(config('authsodium.delegate'));

        /**
         * Auth::viaRequest is a closure - it will not
         * get executed until it is called explicitly
         * with the following: 
         *  - `Auth::guard('authsodium')->user()`
         *  - `Auth::guard('authsodium')->check()`
         *
         * It will NOT get called just because a route
         * is using the authsodium middleware.
         */
        $guardName = authSodium()->guardName();
        if ($guardName !== null) {
            Auth::viaRequest($guardName, function ($request) use ($guardName) {
                return authSodium()->validateRequest($request, false, $guardName);
            });
        }

        if ($this->app->runningInConsole()) {

            $this->loadMigrationsFrom( __DIR__.'/../migrations/3000_01_01_000000_create_auth_sodium_tables.php');
            
            /**
             * @NOTE: the following can be published in
             * the consuming app by calling the
             * following: 
             * 
             * `php artisan vendor:publish --provider="ROTGP\AuthSodium\AuthSodiumServiceProvider" --tag="config"`
             */
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('authsodium.php'),
            ], 'config');
        }
    }

    

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'authsodium');

        /**
         * Makes the Auth::viaRequest('authsodium', ...)
         * declaration above executable. 
         */

        $guardName = authSodium()->guardName();
        if (!empty($guardName))
            config(['auth.guards.' . $guardName => ['driver' => $guardName]]);
    }
}
