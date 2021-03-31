<?php
use ROTGP\AuthSodium\Test\IntegrationTestCase;

use ROTGP\AuthSodium\Test\Controllers\FooController;

class NamedMiddlewareTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
    }
   
    public function test_that_unsigned_request_to_resource_protected_by_named_middleware_fails()
    {
        $response = $this->request()->response(false);
        $this->assertBadRequest($response);
        $this->assertUserNotLoggedIn();
    }

    public function test_that_signed_request_to_resource_protected_by_named_middleware_succeeds()
    {
        $response = $this->request()->response(true);
       
        $response->assertStatus(200);
        $this->assertUserLoggedIn();
    }
}
