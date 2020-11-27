<?php

namespace ROTGP\AuthSodium\Test\Models;

use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    const ROCK = 1; 
    const FOLK = 2;
    const HEAVY_METAL = 3;
    const PUNK = 4;
    const HIP_HOP = 5;
    const POP = 6;
    const FUNK = 7;
    const JAZZ = 8;
    const HOUSE = 9;
    const BLUES = 10;
    const CLASSICAL = 11;
    const TECHNO = 12;

    public $timestamps = false;
}
