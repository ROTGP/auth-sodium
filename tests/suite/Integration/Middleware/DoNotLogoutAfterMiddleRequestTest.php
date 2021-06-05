<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

class DoNotLogoutAfterMiddleRequestTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');

        config([
            'authsodium.invalidate_user.on_terminate' => false,
            'authsodium.invalidate_user.after_request' => false
        ]);
    }

    public function test_that_signed_request_to_resource_protected_by_group_middleware_logs_user_out_afterwards()
    {
        $response = $this->signed()->request()->response();
        $this->assertSuccessfulRequest($response);
        
        $this->assertUserLoggedIn();
    }
}
