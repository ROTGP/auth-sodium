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
            'authsodium.middleware.log_out_after_request' => true,
            'authsodium.middleware.name' => null
        ]);
    }

    public function test_that_signed_request_to_resource_that_uses_no_middleware_succeeds_and_does_not_log_user_out_afterwards()
    {
        // despite specifying 'log_out_after_request' -
        // and having called
        // Auth::guard('authsodium')->user()`, the user
        // will still be logged in after the request
        // because we are not using any middleware and
        // thus the terminate() is not called.
        $response = $this->signed()->request('post')->response();
        $response->assertStatus(201);
        $this->assertUserLoggedIn();
    }
}
