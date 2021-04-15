<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

use Carbon\Carbon;

class CustomHeadersTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
        
        config([
            'authsodium.header_keys' => [
                'nonce' => 'foo-Nonce',
                'user_identifier' => 'foo-User',
                'signature' => 'foo-Signature'
            ]
        ]);
    }


    public function test_that_signed_request_to_protected_resource_with_custom_headers_succeeds()
    {
        $request = $this->signed()->request();
        $headers = $this->getHeaders();
        $newHeaders = [
            'foo-Nonce' => $headers['Auth-Nonce'],
            'Auth-Timestamp' => $headers['Auth-Timestamp'],
            'foo-User' => $headers['Auth-User'],
            'foo-Signature' => $headers['Auth-Signature'],
        ];
        $this->headers($newHeaders);
        $response =  $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();
    }
}
