<?php

namespace ROTGP\AuthSodium\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable;

use Exception;

abstract class AuthSodiumUser extends Model implements Authenticatable
{    
    final protected static function booted()
    {
        static::creating(function ($user)
        {
            authSodium()->userWillBeCreated($user);
        });

        static::created(function ($user)
        {
            authSodium()->userWasCreated($user);
        });

        static::updating(function ($user)
        {
            authSodium()->userWillBeUpdated($user);
        });

        static::didBoot();
    }

    /**
     * Extending classes may overide this boot method
     * as the default booted is marked as final.
     *
     * @return void
     */
    protected static function didBoot()
    {
        //
    }
    

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'public_key'
    ];

    /**
     * Set the user's email.
     *
     * @param  string  $value
     * @return void
     */
    public function setEmailAttribute($value)
    {
        if (is_string($value)) {
            $value = strtolower($value);
        }
        $this->attributes['email'] = $value;
    }

    public function emailVerification()
    {
        return $this->hasOne('ROTGP\AuthSodium\Models\EmailVerification');
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
        throw new Exception('not implemented');
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        throw new Exception('not implemented');
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        throw new Exception('not implemented');
    }
}
