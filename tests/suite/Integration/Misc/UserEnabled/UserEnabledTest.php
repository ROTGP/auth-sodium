<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

class UserEnabledTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
    }

    public function test_that_signed_request_with_disabled_user_fails_then_enabled_user_succeeds()
    {
        $user = $this->users[0]['model'];
        
        $user->update(['enabled' => false]);

        $response = $this->signed()->request()->response();
        $this->assertValidationError($response, 'user_not_enabled');
        $this->assertUserLoggedOut();
        
        $user->update(['enabled' => true]);

        $response = $this->signed()->request()->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();
    }
}
