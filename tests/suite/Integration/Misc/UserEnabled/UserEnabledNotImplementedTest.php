<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;
use ROTGP\AuthSodium\Test\Models\AltUser;

class UserEnabledNotImplementedTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
    
        config(['authsodium.user.model' => AltUser::class]);
    }

    public function test_that_signed_request_with_user_that_does_not_implement_enabled_succeeds_despite_enabled_status()
    {
        $user = $this->users[0]['model'];
        
        $user->update(['enabled' => false]);

        $response = $this->signed()->request()->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();
        
        $user->update(['enabled' => true]);
        
        $request = $this->signed()->request();
        $this->nonce(2);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();
    }
}
