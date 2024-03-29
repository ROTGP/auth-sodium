<?php

namespace ROTGP\AuthSodium\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable;

use Exception;

abstract class AuthSodiumUser extends Model implements Authenticatable
{
    public function nonces()
    {
        return $this->hasMany('ROTGP\AuthSodium\Models\Nonce');
    }

    public function throttle()
    {
        return $this->hasOne('ROTGP\AuthSodium\Models\Throttle');
    }

    // implement Illuminate\Contracts\Auth\Authenticatable methods

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this[$this->getAuthIdentifierName()];
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return null;
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken()
    {
        throw new Exception('not supported');
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        throw new Exception('not supported');
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        throw new Exception('not supported');
    }
}