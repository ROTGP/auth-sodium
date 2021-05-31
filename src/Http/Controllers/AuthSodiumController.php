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
         * explicitly that it should fail if something
         * goes wrong, and that it should be throttling.
         */
        authSodium()->validateRequest(false, true, true);
        return response()->json(['auth_user' => authSodium()->user()], 200);
    }
}