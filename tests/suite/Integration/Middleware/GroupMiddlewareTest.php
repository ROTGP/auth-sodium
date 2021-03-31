<?php
use ROTGP\AuthSodium\Test\IntegrationTestCase;

use ROTGP\AuthSodium\Test\Controllers\FooController;

class GroupMiddlewareTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('web');

        config(['authsodium.middleware.group' => 'web']);
    }
   
    public function test_that_unsigned_request_to_resource_protected_by_group_middleware_fails()
    {
        $response = $this->request()->response(false);
        $this->assertBadRequest($response);
    }

    public function test_that_unsigned_request_to_resource_protected_by_group_middleware_succeeds()
    {
        $response = $this->request()->response(true);
        $json = $this->decodeResponse($response);

        $response->assertStatus(200);
        $this->assertCount(10, $json);
        $this->assertEquals('Kallie Langosh', $json[0]['name']);
        $this->assertEquals('Rex Lemke DVM', $json[9]['name']);
    }
}
