<?php

namespace ROTGP\AuthSodium\Facades;

use Illuminate\Support\Facades\Facade;

class AuthSodium extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'authsodium';
    }
}
