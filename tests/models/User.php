<?php

namespace ROTGP\AuthSodium\Test\Models;

use ROTGP\AuthSodium\User as AuthUser;

class User extends AuthUser
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function safeScopes($authUser)
    {
        return ['born_after'];
    }

    public function scopeBornAfter($query, $params)
    {
        return $query->where('date_of_birth', '>', Carbon::parse($params));
    }

    public function artists()
    {
        return $this->belongsToMany(Artist::class);
    }

    public function albums()
    {
        return $this->belongsToMany(Album::class)->using(AlbumUser::class)->withTimestamps();
    }

    public function songs()
    {
        return $this->belongsToMany(Song::class)->withTimestamps();;
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
