<?php
use ROTGP\AuthSodium\Test\IntegrationTestCase;

use ROTGP\AuthSodium\Test\Controllers\FooController;

class NoMiddlewareTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class);

        config(['authsodium.middleware.name' => null]);
    }
   
    public function test_that_unsigned_request_to_unprotected_resource_succeeds()
    {
        $response = $this->unsigned()->request()->response();
        $this->assertSuccessfulRequest($response);
    }

    public function test_that_signed_request_to_unprotected_resource_succeeds()
    {
        $response = $this->signed()->request()->response();
        $this->assertSuccessfulRequest($response);
    }
}
