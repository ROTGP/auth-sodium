<?php
use ROTGP\AuthSodium\Test\IntegrationTestCase;

use ROTGP\AuthSodium\Test\Controllers\FooController;

class GroupMiddlewareTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');

        config(['authsodium.middleware.use_global' => true]);
    }
   
    public function test_that_unsigned_request_to_resource_protected_by_group_middleware_fails()
    {
        $response = $this->request()->response(false);
        $this->assertBadRequest($response);
    }

    public function test_that_signed_request_to_resource_protected_by_group_middleware_succeeds()
    {
        $response = $this->request()->response(true);
        $this->assertSuccessfulRequest($response);
    }
}
