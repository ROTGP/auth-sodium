<?php
use ROTGP\AuthSodium\Test\IntegrationTestCase;

use ROTGP\AuthSodium\Test\Controllers\FooController;

class NamedGuardTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class);
            // ->middleware('authsodium');
        
        config(['authsodium.guard.name' => 'authsodium']);
    }
   
    // public function test_that_unsigned_request_to_resource_protected_by_named_middleware_fails()
    // {
    //     $response = $this->unsigned()->request('post')->response();
    //     $this->assertBadRequest($response);
    // }

    public function test_that_signed_request_to_resource_protected_by_named_guard_succeeds()
    {
        // dd('xxxxx', config('authsodium.guard.name'));

        $response = $this->signed()->request('post')->response();
        // dd($response);
        $response->assertStatus(201);
    }
}
