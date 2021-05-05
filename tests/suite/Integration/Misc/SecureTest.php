<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

use Carbon\Carbon;

class SecureTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
        
        config(['authsodium.secure.environments' => ['production', 'testing']]);
    }

    public function test_that_signed_request_over_unsupported_protocol_fails()
    {
        $response = $this->signed()->request()->response();
        $response->assertStatus(426);
        $json = $this->decodeResponse($response);
        $this->assertArrayHasKey('http_status_code', $json);
        $this->assertEquals(426, $json['http_status_code']);
        $this->assertArrayHasKey('http_status_message', $json);
        $this->assertEquals('Upgrade Required', $json['http_status_message']);
        
        $this->assertArrayHasKey('error_message', $json);
        $this->assertEquals('Secure protocol required', $json['error_message']);
    }

    public function test_that_signed_request_over_supported_protocol_succeeds()
    {
        $response = $this->signed()->request()->scheme('https')->response();
        $this->assertSuccessfulRequest($response);
    }
}
