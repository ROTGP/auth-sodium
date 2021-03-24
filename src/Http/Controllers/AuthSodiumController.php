<?php

namespace ROTGP\AuthSodium\Http\Controllers;

use Illuminate\Routing\Controller;

class AuthSodiumController extends Controller
{    
    public function verifyEmail()
    {
        return authSodium()->verifyEmail(request()->get('code'));
    }
}