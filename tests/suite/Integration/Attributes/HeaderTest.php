<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

use Carbon\Carbon;

class HeaderTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
    }

    public function test_that_signed_request_to_protected_resource_with_incorrect_user_identifier_in_header_fails()
    {
        $request = $this->signed()->request();
        $headers = $this->getHeaders();
        // set the email address of another existing
        // user as the Auth-User 
        $headers['Auth-User'] = 'ritchie.delphia@gmail.com';
        $this->headers($headers);
        $response =  $request->response();
        $this->assertUnauthorized($response);
        $this->assertUserLoggedOut();
    }
}
