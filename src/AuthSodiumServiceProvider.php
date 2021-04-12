<?php

namespace ROTGP\AuthSodium;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;

use Config;
use Auth;
use Arr;

class AuthSodiumServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(Router $router, Kernel $kernel)
    {
        // if (!defined('AUTH_SODIUM_CONFIG')) {
        define("AUTH_SODIUM_CONFIG", Arr::dot(config('authsodium')));
        // }

        $delegateNS = authSodiumConfig('delegate');
        $delegate = app()->make($delegateNS);
        $this->app->instance($delegateNS, $delegate);

        $middlewareName = $delegate->middlewareName();
        $middlewareGroup = $delegate->middlewareGroup();
        $useGlobalMiddleware = $delegate->useGlobalMiddleware();
        $usingMiddleware = !empty($middlewareName) || 
            !empty($middlewareGroup) ||
            $useGlobalMiddleware === true;

        /**
         * https://yish.dev/ordering-laravel-middleware-priority
         * We want this to run early, so we prepend it
         * to the beginning of the array.
         */
        if ($usingMiddleware)
            $kernel->prependToMiddlewarePriority($delegateNS);

        /**
         * This will run the middleware ONLY if the
         * route specifies it, ie: 
         *
         * `Route::resource('foos', FooController::class)->middleware('authsodium');`
         */
        if ($middlewareName)
            $router->aliasMiddleware($middlewareName, $delegateNS);
        
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

        if ($middlewareGroup)
            $router->pushMiddlewareToGroup($middlewareGroup, $delegateNS);

        /**
         * This will run the AuthSodium middleware on
         * ALL requests, regardless of the middleware
         * specified for the route. Not very flexible as
         * soon routes will not need an auth user (ie,
         * user registration).
         */
        if ($useGlobalMiddleware)
            $kernel->pushMiddleware($delegateNS);

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
        if ($guardName) {
            
            config(['auth.guards.' . $guardName => ['driver' => $guardName]]);
            
            // Return an instance of
            // Illuminate\Contracts\Auth\Guard
            // https://laravel.com/api/8.x/Illuminate/Contracts/Auth/Guard.html#method_setUser
            // https://github.com/laravel/framework/blob/c62385a23c639742b3b74a4a78640da25e6b782b/src/Illuminate/Auth/SessionGuard.php#L725
            // https://github.com/laravel/framework/blob/7.x/src/Illuminate/Auth/SessionGuard.php#L823
            // https://github.com/laravel/framework/blob/c62385a23c639742b3b74a4a78640da25e6b782b/src/Illuminate/Auth/GuardHelpers.php#L81
                
            Auth::extend($guardName, function ($app, $name) use ($delegate) {
                return $delegate;
            });
        
        /**
         *  For a consistent API, if we're not using a
         *  custom guard name, then add the following
         *  methods to the Auth facade.
         */ 
        } else {

            Auth::macro('authenticateSignature', function () {
                return authSodium()->authenticateSignature();
            });
    
            Auth::macro('invalidateUser', function () {
                $this->user = null;
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

        $this->app->terminating(function () use ($delegate) {
            if (authSodiumConfig('log_out_after_request', true)) {
                $delegate->invalidateUser();
            }

            if (authSodiumConfig('database.prune_nonces_after_request', true)) {
                $delegate->pruneNonces();
            }
         });
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'authsodium');
    }
}
