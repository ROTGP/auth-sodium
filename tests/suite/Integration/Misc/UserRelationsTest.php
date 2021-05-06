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
        $this->assertUnauthorized($response);
        $json = $this->decodeResponse($response);
        $this->assertArrayHasKey('error_message', $json);
        $this->assertEquals('User not enabled', $json['error_message']);
        
        $user->update(['enabled' => true]);

        $response = $this->signed()->request()->response();
        $this->assertSuccessfulRequest($response);
    }
}
