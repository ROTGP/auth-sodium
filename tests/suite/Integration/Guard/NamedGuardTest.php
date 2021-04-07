<?php
use ROTGP\AuthSodium\Test\IntegrationTestCase;

use ROTGP\AuthSodium\Test\Controllers\FooController;

class NamedGuardTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class);
        
        config(['authsodium.guard.name' => 'authsodium']);
    }
    
    /**
     * Here we try to create a new Foo resource, with a
     * post to the unprotected resource FooController,
     * which in turn tries to build a payload containing
     * our named guard `Auth::guard('authsodium')->id()`;
     * This should fail and cause a validation error,
     * because the request is not signed. If the
     * resource had been protected by middleware (and
     * assuming that 'abort_on_invalid_signature' were
     * true), then the error code would be 400 instead
     * of 422.
     */
    public function test_that_unsigned_request_to_resource_that_uses_named_guard_fails()
    {
        $response = $this->unsigned()->request('post')->response();
        $this->assertValidationError($response, 'user id can not be null');
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_to_resource_that_uses_named_guard_succeeds()
    {
        $response = $this->signed()->request('post')->response();
        $response->assertStatus(201);
        $this->assertUserLoggedIn();
    }
}
