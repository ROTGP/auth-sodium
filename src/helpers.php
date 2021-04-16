<?php

function authSodium()
{
    return app()->make(config('authsodium.delegate', ROTGP\AuthSodium\AuthSodiumDelegate::class));
}
