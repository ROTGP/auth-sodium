<?php
use ROTGP\AuthSodium\Test\IntegrationTestCase;

use ROTGP\AuthSodium\Test\Controllers\FooController;

class LogoutAfterGuardRequestTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class);

        config([
            'authsodium.guard.name' => 'authsodium',
            'authsodium.middleware.log_out_after_request' => true
        ]);
    }

    public function test_that_signed_request_to_resource_that_uses_named_guard_succeeds_and_logs_user_out_afterwards()
    {
        $response = $this->signed()->request()->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();
    }
}
