<?php

namespace ROTGP\AuthSodium;

use Closure;
use ROTGP\AuthSodium\User;

use Auth;

class AuthSodiumMiddleware
{
    public function handle($request, Closure $next)
    {
        // https://laravelpackage.com/11-middleware.html#testing-after-middleware
        
        // https://laracasts.com/discuss/channels/general-discussion/register-middleware-via-service-provider?page=2

        // $foo = 'bar';
        // Auth::login($foo);
        // dd(Auth::user());

        dd(config('authsodium.model'));

        // if (config('authsodium.model'))
        //     dd('middleware!!!', $this, User::class, $request->headers);

        $user = new User(['name' => 'Tim Allen']);
        dd('getAuthIdentifier', $user->getAuthIdentifier());
        Auth::login($user);
        dd('mkkkkkkkx', $user, Auth::user());


        // Auth::login($user);
        // Auth::user();

        // if ($request->has('title')) {
        //     $request->merge([
        //         'title' => ucfirst($request->title)
        //     ]);
        // }

        return $next($request);
    }
}