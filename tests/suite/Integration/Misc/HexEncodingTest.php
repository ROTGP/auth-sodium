<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

use Carbon\Carbon;

class HexEncodingTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
        
        config(['authsodium.encoding' => 'hex']);
    }

    public function test_that_signed_request_with_hex_encoding_returns_successful_response()
    {
        $response = $this->signed()->request()->response();
        $this->assertValidationError($response, 'invalid_signature_encoding');
        
        $this->signature(bin2hex(base64_decode($this->getSignature())));
        $response = $this->response();
        $this->assertUnauthorized($response);

        $json = $this->decodeResponse($response);
        $this->assertArrayHasKey('error_key', $json);
        $this->assertEquals('invalid_public_key_encoding', $json['error_key']);
        
        $model = $this->users[0]['model'];
        $model->public_key = bin2hex(base64_decode($model->public_key));
        $model->save();
        
        $response = $this->response();
        $this->assertSuccessfulRequest($response);
    }
}
