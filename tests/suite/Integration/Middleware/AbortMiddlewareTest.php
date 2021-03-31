<?php
use ROTGP\AuthSodium\Test\IntegrationTestCase;

use ROTGP\AuthSodium\Test\Controllers\FooController;

class AbortMiddlewareTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');

        config([ 'authsodium.middleware.abort_on_invalid_signature' => false]);
    }
    
    public function test_that_unsigned_request_to_resource_protected_by_global_middleware_should_not_abort()
    {
        $response = $this->request()->response(false);
        $this->assertSuccessfulRequest($response);
        $this->assertUserNotLoggedIn();
    }

    public function test_that_signed_request_to_resource_protected_by_global_middleware_should_not_abort()
    {
        $response = $this->request()->response(true);
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedIn();
    }
}
