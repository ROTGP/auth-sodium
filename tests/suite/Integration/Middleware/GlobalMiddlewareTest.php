<?php
use ROTGP\AuthSodium\Test\IntegrationTestCase;

use ROTGP\AuthSodium\Test\Controllers\FooController;

class GlobalMiddlewareTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class);

        config(['authsodium.middleware.use_global' => true]);
    }
   
    public function test_that_unsigned_request_to_resource_protected_by_global_middleware_fails()
    {
        $response = $this->unsigned()->request()->response();
        $this->assertValidationError($response, 'signature_not_found');
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_to_resource_protected_by_global_middleware_succeeds()
    {
        $response = $this->signed()->request()->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();
    }
}
