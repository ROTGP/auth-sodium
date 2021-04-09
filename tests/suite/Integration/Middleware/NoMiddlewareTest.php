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
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_to_unprotected_resource_succeeds()
    {
        $response = $this->signed()->request()->response();
        $this->assertSuccessfulRequest($response);

        // route is unprotected and no named guard was
        // called so user won't have been logged in at
        // any stage of the request, ever if the request
        // was successful
        $this->assertUserLoggedOut();
    }
}
