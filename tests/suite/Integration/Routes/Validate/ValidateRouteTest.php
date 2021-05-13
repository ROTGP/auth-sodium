<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

class ValidateRouteTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        config([
            'authsodium.routes.validate' => 'validate',
            'authsodium.guard.name' => null,
            'authsodium.middleware.name' => null
        ]);
    }

    public function test_that_signed_request_returns_auth_user()
    {
        $response = $this->signed()->request('get', 'validate')->response();
        $response->assertStatus(200);
        $json = $this->decodeResponse($response);

        $this->assertArrayHasKey('auth_user', $json);
        $this->assertEquals(1, $json['auth_user']['id']);
    }
}
