<?php

namespace ROTGP\AuthSodium\Test\Models;

use Illuminate\Database\Eloquent\Model;

class RecordLabel extends Model
{
    const WARNER_BROS = 1; 
    const ISLAND_DEF_JAM = 2;
    const AFTERMATH = 3;
    const EPIC = 4;
    const ATLANTIC = 5;
    const YOUNG_MONEY_ENTERTAINMENT = 6;
    const CASH_MONEY_BILLIONAIRE_RECORDS = 7;
    const COLUMBIA = 8;

    public $timestamps = false;
}
