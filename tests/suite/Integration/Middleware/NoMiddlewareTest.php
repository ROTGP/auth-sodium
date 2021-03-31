<?php
use ROTGP\AuthSodium\Test\IntegrationTestCase;

use ROTGP\AuthSodium\Test\Controllers\FooController;

class NoMiddlewareTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class);

        config(['authsodium.middleware.name' => null]);
    }
   
    public function test_that_unsigned_request_to_unprotected_resource_succeeds()
    {
        $response = $this->request()->response(false);
        $json = $this->decodeResponse($response);

        $response->assertStatus(200);
        $this->assertCount(10, $json);
        $this->assertEquals('Kallie Langosh', $json[0]['name']);
        $this->assertEquals('Rex Lemke DVM', $json[9]['name']);
    }

    public function test_that_signed_request_to_unprotected_resource_succeeds()
    {
        $response = $this->request()->response(false);
        $json = $this->decodeResponse($response);

        $response->assertStatus(200);
        $this->assertCount(10, $json);
        $this->assertEquals('Kallie Langosh', $json[0]['name']);
        $this->assertEquals('Rex Lemke DVM', $json[9]['name']);
    }
}
