<?php

namespace ROTGP\AuthSodium\Test\Models;

use Illuminate\Database\Eloquent\Model;

class Song extends Model
{
    protected $fillable = [
        'name',
        'album_id',
        'length_seconds' 
    ];

    public function plays()
    {
        return $this->hasMany(Play::class);
    }

    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
