<?php

namespace ROTGP\AuthSodium\Http\Middleware;

use ROTGP\AuthSodium\Models\User;

use Closure;
use Auth;

class AuthSodiumMiddleware
{
    public function handle($request, Closure $next)
    {
        // dd(request()->route()->getName());

        echo("AuthSodiumMiddleware!\n\n\n");

        // https://laravelpackage.com/11-middleware.html#testing-after-middleware
        // https://laracasts.com/discuss/channels/general-discussion/register-middleware-via-service-provider?page=2

        // if we're already logged in - then abandon auth
        // if (Auth::check() === true) {
        //     return $next($request);
        // }
        
        // $foo = 'bar';
        // Auth::login($foo);
        // dd(Auth::user());

        // $ok = authSodium()->validateRequest($request);

        
        // dd('should be ok');

        // dd('ok?', $ok);

        // if (config('authsodium.model'))
        //     dd('middleware!!!', $this, User::class, $request->headers);

        // $user = new User(['name' => 'Tim Allen']);
        // dd('getAuthIdentifier', $user->getAuthIdentifier());
        // Auth::login($user);
        // dd('mkkkkkkkx', $user, Auth::user());

        return $next($request);
    }
}