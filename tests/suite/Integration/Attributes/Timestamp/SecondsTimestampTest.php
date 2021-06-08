<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

use Carbon\Carbon;

class SecondsTimestampTest extends IntegrationTestCase
{
    protected $shouldMock64Bit = true;
    protected $is64Bit = false;
    
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');

        config(['authsodium.leeway' => 300]);
    }

    public function test_that_signed_request_in_seconds_with_timestamp_in_seconds_succeeds()
    {
        $request = $this->signed()->request();
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertEquals(1616007320, $this->getTimestamp());
    }

    public function test_that_signed_request_in_seconds_with_timestamp_in_milliseconds_fails()
    {
        $request = $this->signed()->request();
        $this->timestamp(intval($this->epoch->getPreciseTimestamp(3)));
        $response = $request->response();
        $this->assertValidationError($response, 'invalid_timestamp_range');
        $this->assertEquals(1616007320000, $this->getTimestamp());
    }

    public function test_that_signed_request_in_seconds_with_timestamp_equal_to_negative_leeway_succeeds()
    {
        $request = $this->signed()->request();
        $newDate = $this->epoch->copy()->subtract(300, 'seconds');
        $this->setTimestampFromDate($newDate);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
    }

    public function test_that_signed_request_in_seconds_with_timestamp_equal_to_positive_leeway_succeeds()
    {
        $request = $this->signed()->request();
        $newDate = $this->epoch->copy()->subtract(300, 'seconds');
        $this->setTimestampFromDate($newDate);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
    }

    public function test_that_signed_request_in_seconds_with_timestamp_after_leeway_fails()
    {
        $request = $this->signed()->request();
        $this->setTimestampFromDate(
            $this->epoch->copy()->add(301, 'seconds')
        );
        $response = $request->response();
        $this->assertValidationError($response, 'invalid_timestamp_range');
    }

    public function test_that_signed_request_in_seconds_with_numeric_string_timestamp_succeeds()
    {
        $request = $this->signed()->request();
        $stringTimestamp = (string) intval($this->epoch->timestamp);
        $this->timestamp($stringTimestamp);
        $this->assertEquals($stringTimestamp, $this->getTimestamp());
        $this->assertEquals('1616007320', $stringTimestamp);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
    }

    public function test_that_signed_request_in_seconds_with_non_numeric_timestamp_fails()
    {
        $request = $this->signed()->request();
        $this->timestamp('123abc');
        $response = $request->response();
        $this->assertValidationError($response, 'invalid_timestamp_format');
    }

    public function test_that_signed_request_in_seconds_with_decimal_timestamp_fails()
    {
        $request = $this->signed()->request();
        $this->timestamp('123.4');
        $response = $request->response();
        $this->assertValidationError($response, 'invalid_timestamp_format');
    }

    public function test_that_signed_request_in_seconds_with_null_timestamp_fails()
    {
        $request = $this->signed()->request();
        $this->timestamp(null);
        $response = $request->response();
        $this->assertValidationError($response, 'timestamp_not_found');
    }

    public function test_that_signed_request_in_seconds_with_empty_string_timestamp_fails()
    {
        $request = $this->signed()->request();
        $this->timestamp('');
        $response = $request->response();
        $this->assertValidationError($response, 'timestamp_not_found');
    }

    public function test_that_signed_request_in_seconds_with_old_timestamp_fails()
    {
        $request = $this->signed()->request();
        $this->setTimestampFromDate(
            $this->epoch->copy()->subtract(301, 'seconds')
        );
        $response = $request->response();
        $this->assertValidationError($response, 'invalid_timestamp_range');

        $newDate = $this->epoch->copy()->subtract(300, 'seconds');
        $this->setTimestampFromDate($newDate);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
    }

    public function test_that_signed_request_in_seconds_with_future_timestamp_fails()
    {
        $request = $this->signed()->request();
        $this->setTimestampFromDate(
            $this->epoch->copy()->add(301, 'seconds')
        );
        $response = $request->response();
        $this->assertValidationError($response, 'invalid_timestamp_range');
        
        $newDate = $this->epoch->copy()->add(300, 'seconds');
        $this->setTimestampFromDate($newDate);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
    }
}
