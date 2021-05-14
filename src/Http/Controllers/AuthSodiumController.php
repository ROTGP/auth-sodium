<?php

namespace ROTGP\AuthSodium\Http\Controllers;

use Illuminate\Routing\Controller;

use Auth;

class AuthSodiumController extends Controller
{  
    public function validate()
    {
        /**
         * Validate the auth sodium user, indicating
         * that we explicitly want to fail if something
         * goes wrong, and that we should be throttling
         */
        authSodium()->validateRequest(false, true, true);
        return response()->json(['auth_user' => authSodium()->user()], 200);
    }
}