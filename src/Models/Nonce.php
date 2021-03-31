<?php

namespace ROTGP\AuthSodium\Models;

use Illuminate\Database\Eloquent\Model;
use Exception;

class Nonce extends Model
{  
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'value'
    ];

    public function setUserKey($value)
    {
        if (empty($value))
            throw new Exception('Invalid user key');
        $this->attributes[$this->foreignKeyName()] = $value;
    }

    public function authUser()
    {
        // @TODO authSodium()->authUserModel()
        return $this->belongsTo(config('authsodium.user.model'), $this->foreignKeyName());
    }

    public function foreignKeyName()
    {
        return authSodium()->authUserModel()->getForeignKey();
    }

    public function scopeForUserKey($query, $value)
    {
        return $query->where($this->foreignKeyName(), '=', $value);
    }
}