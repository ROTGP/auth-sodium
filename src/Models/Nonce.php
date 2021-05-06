<?php

namespace ROTGP\AuthSodium\Models;

use Illuminate\Database\Eloquent\Model;

use Exception;

class Nonce extends Model
{  
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'authsodium_nonces';

    /**
     * The attributes that are guarded. As this is the
     * inverse of fillable, we're essentially allowing
     * any field to be mass assignable. This is safe as
     * the input will always be system generated.
     *
     * @var array
     */
    protected $guarded = [];

    public $timestamps = false;

    public function setUserKey($value)
    {
        if (empty($value))
            throw new Exception('Invalid user key');
        $this->attributes[$this->foreignKeyName()] = $value;
    }

    public function authUser()
    {
        return $this->belongsTo(config('authsodium.user.model'), $this->foreignKeyName());
    }

    public function foreignKeyName()
    {
        return authSodium()->authUserModel()->getForeignKey();
    }

    public function scopeForUserIdentifier($query, $value)
    {
        return $query->where($this->foreignKeyName(), '=', $value);
    }
}