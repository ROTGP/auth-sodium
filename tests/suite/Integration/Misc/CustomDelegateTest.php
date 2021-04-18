<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;
use ROTGP\AuthSodium\Test\CustomAuthSodiumDelegate;

class CustomDelegateTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
    
        config(['authsodium.delegate' => CustomAuthSodiumDelegate::class]);
    }

    public function test_that_signed_request_with_custom_signature_string_succeeds()
    {
        $request = $this->signed()->request();
        $response = $request->response();
        $this->assertUnauthorized($response);
        $this->assertUserLoggedOut();

        $this->signatureString($this->getSignatureString() . 'foo');
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();
    }
}
