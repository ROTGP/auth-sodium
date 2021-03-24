<?php

namespace ROTGP\AuthSodium\Http\Middleware;

use ROTGP\AuthSodium\Models\User;

use Closure;
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

        // $url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        // $url = urldecode($url);

        // dd('yepes', $url, $request->method(), config('authsodium.model'), $request->fullUrl(), $request->url(), $request->query());

        // if (config('authsodium.model'))
        //     dd('middleware!!!', $this, User::class, $request->headers);

        // $user = new User(['name' => 'Tim Allen']);
        // dd('getAuthIdentifier', $user->getAuthIdentifier());
        // Auth::login($user);
        // dd('mkkkkkkkx', $user, Auth::user());

        return $next($request);
    }
}