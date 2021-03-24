<?php

namespace ROTGP\AuthSodium\Models;

use Illuminate\Database\Eloquent\Model;
use Exception;

class EmailVerification extends Model
{
    final protected static function booted()
    {
        static::creating(function ($emailVerification)
        {
            authSodium()->emailVerificationWillBeCreated($emailVerification);
        });

        static::created(function ($user)
        {
            // dd('created', $user->toArray());
        });

        static::updating(function ($user)
        {
            // $changes = $user->getDirty();
            // dd('updating', $user->toArray(), $changes);
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
        'user_id',
        'email',
        'public_key'
    ];

    /**
     * Scope a query to only include verification for
     * particular email address.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $email
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForEmail($query, $email)
    {
        return $query->where('email', strtolower($email));
    }

    
    public function user()
    {
        return $this->belongsTo(config('authsodium.model'));
    }
}
