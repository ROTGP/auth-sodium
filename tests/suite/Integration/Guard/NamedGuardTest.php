<?php
use ROTGP\AuthSodium\Test\IntegrationTestCase;

use ROTGP\AuthSodium\Test\Controllers\FooController;

class NamedGuardTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class);

        // config([ 'authsodium.middleware.abort_on_invalid_signature' => false]);
    }
   
    // public function test_that_unsigned_request_to_resource_protected_by_named_middleware_fails()
    // {
    //     $response = $this->unsigned()->request()->response();
    //     $this->assertBadRequest($response);
    //     $this->assertUserNotLoggedIn();
    // }

    public function test_that_signed_request_to_resource_protected_by_named_guard_succeeds()
    {
        $response = $this->signed()->request('post')->response();
        $response->assertStatus(201);
    }
}
