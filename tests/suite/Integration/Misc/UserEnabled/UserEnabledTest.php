<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

class UserRelationsTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
    }

    public function test_that_signed_request_with_disabled_user_fails_then_enabled_user_succeeds()
    {
        $user = $this->users[0]['model'];
        
        $response = $this->signed()->request()->nonce(1)->response();
        $this->assertSuccessfulRequest($response);

        $response = $this->signed()->request()->nonce(2)->response();
        $this->assertSuccessfulRequest($response);

        $response = $this->signed()->request()->nonce(3)->response();
        $this->assertSuccessfulRequest($response);

        $response = $this->signed()->request()->nonce(4)->flipSignature()->response();
        $this->assertUnauthorized($response);

        $response = $this->signed()->request()->nonce(4)->flipSignature()->response();
        $this->assertUnauthorized($response);

        $response = $this->signed()->request()->nonce(4)->flipSignature()->response();
        $this->assertUnauthorized($response);

        $response = $this->signed()->request()->nonce(4)->flipSignature()->response();
        $this->assertUnauthorized($response);
        
        $this->assertEquals([
            [
                'id' => 1,
                'value' => '1',
                'timestamp' => 1616007320000,
                'user_id' => '1'
            ],
            [
                'id' => 2,
                'value' => '2',
                'timestamp' => 1616007320000,
                'user_id' => '1'
            ],
            [
                'id' => 3,
                'value' => '3',
                'timestamp' => 1616007320000,
                'user_id' => '1'
            ]
        ], $user->nonces->toArray());

        $this->assertEquals([
            'id' => 1,
            'user_id' => '1',
            'ip_address' => '127.0.0.1',
            'attempts' => 3,
            'try_again' => 1616007321000,
            'blocked' => '0',
        ], $user->throttle->toArray());
    }
}
