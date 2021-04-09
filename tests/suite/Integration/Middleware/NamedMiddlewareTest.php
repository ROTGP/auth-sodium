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
        $response = $this->unsigned()->request()->response();
        $this->assertValidationError($response, 'signature_not_found');
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_to_resource_protected_by_named_middleware_succeeds()
    {
        $response = $this->signed()->request()->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();
    }
}
