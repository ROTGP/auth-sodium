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
        
         config(['authsodium.leeway' => 10]);
    }

    public function test_that_signed_request_with_timestamp_before_custom_leeway_fails()
    {
        $request = $this->signed()->request();
        $this->setTimestampFromDate(
            $this->epoch->copy()->subtract(11, 'milliseconds')
        );
        $response = $request->response();
        $this->assertValidationError($response, 'invalid_timestamp_range');
    }

    public function test_that_signed_request_with_timestamp_equal_to_negative_custom_leeway_succeeds()
    {
        $request = $this->signed()->request();
        $this->setTimestampFromDate(
            $this->epoch->copy()->subtract(10, 'milliseconds')
        );
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
    }

    public function test_that_signed_request_with_timestamp_equal_to_positive_custom_leeway_succeeds()
    {
        $request = $this->signed()->request();
        $this->setTimestampFromDate(
            $this->epoch->copy()->add(10, 'milliseconds')
        );
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
    }

    public function test_that_signed_request_with_timestamp_after_leeway_fails()
    {
        $request = $this->signed()->request();
        $this->setTimestampFromDate(
            $this->epoch->copy()->add(11, 'milliseconds')
        );
        $response = $request->response();
        $this->assertValidationError($response, 'invalid_timestamp_range');
    }
}
