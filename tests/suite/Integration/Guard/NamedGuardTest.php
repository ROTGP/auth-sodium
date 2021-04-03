<?php
use ROTGP\AuthSodium\Test\IntegrationTestCase;

use ROTGP\AuthSodium\Test\Controllers\FooController;

use Event;

class NamedGuardTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class);
        
        config(['authsodium.guard.name' => 'authsodium']);
    }
   
    public function test_that_unsigned_request_to_resource_that_uses_named_guard_fails()
    {
        $response = $this->unsigned()->request('post')->response();
        $this->assertValidationError($response);
    }

    public function test_that_signed_request_to_resource_that_uses_named_guard_succeeds()
    {
        $response = $this->signed()->request('post')->response();
        $response->assertStatus(201);
        // $this->dde();
    }
}
