<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

use Carbon\Carbon;

class NonceUniquePerTimestampTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');

        config(['authsodium.schema.nonce.unique_per_timestamp' => true]);
    }

    public function test_that_signed_request_with_nonces_pertaining_to_different_time_ranges_succeeds()
    {
        $request = $this->signed()->request();
        $this->nonce('foo');
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        
        $response = $request->response();
        $this->assertValidationError($response, 'nonce_already_exists');
        
        $oneSecondInTheFuture = $this->epoch->add(1, 'second');
        $this->setTestNow($oneSecondInTheFuture);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
    }
}
