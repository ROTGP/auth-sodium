<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

use Carbon\Carbon;

class HeadersTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
    }

    /**
     * Set the email address of another existing user as
     * the Auth-User
     */
    public function test_that_signed_request_to_protected_resource_with_incorrect_user_identifier_in_header_fails()
    {
        $request = $this->signed()->request();
        $headers = $this->getHeaders();
        $headers['Auth-User'] = 'ritchie.delphia@gmail.com';
        $this->headers($headers);
        $response =  $request->response();
        $this->assertUnauthorized($response);
        $this->assertUserLoggedOut();
    }

    /**
     * Set the email address of non-existent user as
     * the Auth-User
     */
    public function test_that_signed_request_to_protected_resource_with_non_existent_user_identifier_in_header_fails()
    {
        $request = $this->signed()->request();
        $headers = $this->getHeaders();
        $headers['Auth-User'] = 'foo@bar.com';
        $this->headers($headers);
        $response =  $request->response();
        $this->assertValidationError($response, 'user_not_found');
        $this->assertUserLoggedOut();
    }
}
