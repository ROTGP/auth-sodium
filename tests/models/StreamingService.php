<?php

namespace ROTGP\AuthSodium\Test\Models;

use Illuminate\Database\Eloquent\Model;

class StreamingService extends Model
{
    const YOUTUBE = 1; 
    const SPOTIFY = 2;
    const AMAZON = 3;
    const TIDAL = 4;

    public $timestamps = false;

    public function plays()
    {
        return $this->hasMany(Play::class);
    }
}
