<?php

namespace ROTGP\AuthSodium\Test\Models;

use ROTGP\AuthSodium\Models\AuthSodiumUser;

class AltUser extends AuthSodiumUser
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

    protected $table = 'users';

    public function getForeignKey()
    {
        return 'user_id';
    }
}
