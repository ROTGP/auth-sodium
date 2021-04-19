<?php

namespace ROTGP\AuthSodium\Models;

use Illuminate\Database\Eloquent\Model;

use Exception;
use AuthSodium;

class Nonce extends Model
{  
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'authsodium_nonces';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'value',
        'timestamp'
    ];

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

    public function scopeBetweenTimestamps($query, $start, $end)
    {
        return $query->whereBetween('timestamp', [$start, $end]);
    }
}