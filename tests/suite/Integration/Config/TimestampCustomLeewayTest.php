<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

use Carbon\Carbon;

class TimestampCustomLeewayTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
        
         config(['authsodium.timestamp.leeway' => 10]);
    }

    public function test_that_signed_request_with_timestamp_before_custom_leeway_fails()
    {
        $request = $this->signed()->request();
        $this->timestamp(
            Carbon::createFromTimestamp(
                $this->getTimestamp()
            )->subtract(11, 'seconds')->timestamp
        );
        $response = $request->response();
        $this->assertValidationError($response, 'invalid_timestamp_range');
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_with_timestamp_equal_to_negative_custom_leeway_succeeds()
    {
        $request = $this->signed()->request();
        $this->timestamp(
            Carbon::createFromTimestamp(
                $this->getTimestamp()
            )->subtract(10, 'seconds')->timestamp
        );
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_with_timestamp_equal_to_positive_custom_leeway_succeeds()
    {
        $request = $this->signed()->request();
        $this->timestamp(
            Carbon::createFromTimestamp(
                $this->getTimestamp()
            )->add(10, 'seconds')->timestamp
        );
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_with_timestamp_after_leeway_fails()
    {
        $request = $this->signed()->request();
        $this->timestamp(
            Carbon::createFromTimestamp(
                $this->getTimestamp()
            )->add(11, 'seconds')->timestamp
        );
        $response = $request->response();
        $this->assertValidationError($response, 'invalid_timestamp_range');
        $this->assertUserLoggedOut();
    }
}
