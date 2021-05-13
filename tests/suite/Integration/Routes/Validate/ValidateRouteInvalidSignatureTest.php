<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;

class ValidateRouteInvalidSignatureTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        config(['authsodium.routes.validate' => 'validate']);
    }

    public function test_that_signed_request_with_invalid_signature_returns_unauthorized()
    {
        $response = $this->signed()->request('get', 'validate')->flipSignature()->response();
        $this->assertUnauthorized($response);

        $response = $this->response();
        $this->assertUnauthorized($response);

        $response = $this->response();
        $this->assertUnauthorized($response);

        $response = $this->response();
        $this->assertUnauthorized($response);

        $response = $this->response();
        $this->assertTooManyRequests($response, 1616007321000);
    }
}
