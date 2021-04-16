<?php

namespace ROTGP\AuthSodium\Test\Models;

use ROTGP\AuthSodium\Models\AuthSodiumUser;

class User extends AuthSodiumUser
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'public_key',
        'enabled'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}
