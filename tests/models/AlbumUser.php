<?php

namespace ROTGP\AuthSodium\Test\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class AlbumUser extends Pivot
{
    public function canCreate($authUser)
    {
        return true;
    }

    public function canDelete($authUser)
    {
        return true;
    }
}