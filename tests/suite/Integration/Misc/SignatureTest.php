<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

use Carbon\Carbon;

class SignatureTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
    }

    public function test_that_signed_request_to_protected_resource_with_invalid_signature_fails()
    {
        $request = $this->signed()->request();
        $signature = $request->getSignature();
        $signature[0] = 'U'; // swap first 'U' for lowwercase 'u'
        $this->signature($signature);
        $response =  $request->response();
        $this->assertUnauthorized($response);
        
        $signature[0] = 'u'; // swap back to original
        $this->signature($signature);
        $response =  $request->response();
        $this->assertSuccessfulRequest($response);
    }

    public function test_that_signed_request_to_protected_resource_with_null_signature_fails()
    {
        $request = $this->signed()->request();
        $this->signature('');
        $response =  $request->response();
        $this->assertValidationError($response, 'signature_not_found');
    }

    public function test_that_signed_request_to_protected_resource_with_short_signature_fails()
    {
        $request = $this->signed()->request();
        $signature = base64_decode($request->getSignature());
        $signature = base64_encode(substr($signature, 0, -1));
        $this->signature($signature);
        $response =  $request->response();
        $this->assertValidationError($response, 'signature_invalid_length');
    }
}
