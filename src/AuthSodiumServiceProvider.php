<?php

namespace ROTGP\AuthSodium;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;

use ROTGP\AuthSodium\AuthSodiumDelegate;
use ROTGP\AuthSodium\Console\PruneNonces;
use Illuminate\Console\Scheduling\Schedule;

use ROTGP\AuthSodium\Models\Nonce;
use ROTGP\AuthSodium\Models\Throttle;

use Config;
use Auth;
use Route;
use DateTime;

class AuthSodiumServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(Router $router, Kernel $kernel)
    {
        if (!defined('SODIUM_LIBRARY_VERSION')) {
            throw new Exception("Sodium is not available");
        }

        $delegateNamespace = config('authsodium.delegate', AuthSodiumDelegate::class);
        
        $this->app->instance($delegateNamespace, authSodium());
        
        $middlewareName = authSodium()->middlewareName();
        $middlewareGroup = authSodium()->middlewareGroup();
        $useGlobalMiddleware = authSodium()->useGlobalMiddleware();
        $usingMiddleware = !empty($middlewareName) || 
            !empty($middlewareGroup) ||
            $useGlobalMiddleware === true;

        /**
         * https://yish.dev/ordering-laravel-middleware-priority
         * This should run early, so it is prepended to
         * the beginning of the array.
         */
        if ($usingMiddleware)
            $kernel->prependToMiddlewarePriority($delegateNamespace);
            

        /**
         * This will run the middleware ONLY if the
         * route specifies it, ie: 
         *
         * `Route::resource('foos', FooController::class)->middleware('authsodium');`
         */
        if ($middlewareName)
            $router->aliasMiddleware($middlewareName, $delegateNamespace);
        
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
            $router->pushMiddlewareToGroup($middlewareGroup, $delegateNamespace);

        /**
         * This will run the AuthSodium middleware on
         * ALL requests, regardless of the middleware
         * specified for the route. Not very flexible as
         * soon routes will not need an auth user (ie,
         * user registration).
         */
        if ($useGlobalMiddleware)
            $kernel->pushMiddleware($delegateNamespace);

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
            
            /**
             * See the following source code for references:
             *  - https://bit.ly/34lvZm3
             *  - https://bit.ly/34njs1h
             *  - https://bit.ly/3wBTWBx
             *  - https://bit.ly/3wCUJ5k
             */
            Auth::extend($guardName, function ($app, $name) {
                return authSodium();
            });
        
        /**
         *  For a consistent API, if custom guard name
         *  is not being used, then add the following
         *  methods to the Auth facade.
         */ 
        } else {

            Auth::macro('authenticateSignature', function () {
                return authSodium()->authenticateSignature();
            });
    
            Auth::macro('invalidate', function () {
                $this->user = null;
            });
        }
        
        if ($this->app->runningInConsole()) {

            $this->loadMigrationsFrom( __DIR__.'/../database/migrations/3000_01_01_000000_create_auth_sodium_tables.php');
            
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

            $this->commands([
                PruneNonces::class
            ]);

            $pruneDailyAt = config('authsodium.prune.daily_at', false);
        
            if ($pruneDailyAt && DateTime::createFromFormat('H:i', $pruneDailyAt) !== false) {
                app()->afterResolving(Schedule::class, function (Schedule $schedule) use ($pruneDailyAt) {
                    $schedule->command('authsodium:prune')->dailyAt($pruneDailyAt);
                });
            }
        }

        if (config('authsodium.routes.validate')) {
            $this->loadRoutesFrom(__DIR__.'/../routes/validate.php');
        }

        $invalidateUserOnTerminate = config('authsodium.invalidate_user.on_terminate', true);
        $pruneOnTerminate = config('authsodium.prune.on_terminate', true);
        
        
        if (!$invalidateUserOnTerminate && !$pruneOnTerminate) {
            return;
        }

        $this->app->terminating(function () use ($invalidateUserOnTerminate, $pruneOnTerminate) {
            if ($invalidateUserOnTerminate) {
                authSodium()->invalidate();
            }

            if ($pruneOnTerminate) {
                authSodium()->pruneNonces();
            }
        });
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'authsodium');
    }
}
