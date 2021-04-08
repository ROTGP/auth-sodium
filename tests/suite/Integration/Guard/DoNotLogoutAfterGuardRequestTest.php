<?php
use ROTGP\AuthSodium\Test\IntegrationTestCase;

use ROTGP\AuthSodium\Test\Controllers\FooController;

class DoNotLogoutAfterGuardRequestTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class);

        config([
            'authsodium.guard.name' => 'authsodium',
            'authsodium.middleware.log_out_after_request' => false,
            'authsodium.middleware.name' => null
        ]);
    }

    public function test_that_signed_request_to_resource_that_uses_no_middleware_succeeds_and_does_not_log_user_out_afterwards()
    {
        $response = $this->signed()->request('post')->response();
        $response->assertStatus(201);
        $this->assertUserLoggedIn();
    }
}
