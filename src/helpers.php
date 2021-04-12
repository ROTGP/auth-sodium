<?php

function authSodium()
{
    return app()->make(authSodiumConfig('delegate'));
}

function authSodiumConfig($value, $default = null)
{
    if (array_key_exists($value, AUTH_SODIUM_CONFIG)) {
        return AUTH_SODIUM_CONFIG[$value];
    }
    return $default;
}