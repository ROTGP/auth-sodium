<?php

namespace ROTGP\AuthSodium\Test\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    const GUEST = 1; 
    const FAN = 2;
    const ADMIN = 3;
    const SYSTEM = 4;

    public $timestamps = false;
}
