<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

use Carbon\Carbon;

class TimestampTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
    }

    public function test_that_signed_request_with_timestamp_before_leeway_fails()
    {
        $request = $this->signed()->request();
        $newDate = $this->epoch->copy()->subtract(300001, 'milliseconds');
        $this->setTimestampFromDate($newDate);
        $response = $request->response();
        $this->assertValidationError($response, 'invalid_timestamp_range');
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_with_timestamp_equal_to_negative_leeway_succeeds()
    {
        $request = $this->signed()->request();
        $newDate = $this->epoch->copy()->subtract(300000, 'milliseconds');
        $this->setTimestampFromDate($newDate);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_with_timestamp_equal_to_positive_leeway_succeeds()
    {
        $request = $this->signed()->request();
        $newDate = $this->epoch->copy()->subtract(300000, 'milliseconds');
        $this->setTimestampFromDate($newDate);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_with_timestamp_after_leeway_fails()
    {
        $request = $this->signed()->request();
        $this->timestamp(
            $this->epoch->copy()->add(300001, 'milliseconds')->timestamp
        );
        $response = $request->response();
        $this->assertValidationError($response, 'invalid_timestamp_range');
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_with_numeric_string_timestamp_succeeds()
    {
        $request = $this->signed()->request();
        $stringTimestamp = (string) intval($this->epoch->getPreciseTimestamp(3));
        $this->timestamp($stringTimestamp);
        $this->assertEquals($stringTimestamp, $this->getTimestamp());
        $this->assertEquals('1616007320000', $stringTimestamp);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_with_non_numeric_timestamp_fails()
    {
        $request = $this->signed()->request();
        $this->timestamp('123abc');
        $response = $request->response();
        $this->assertValidationError($response, 'invalid_timestamp_format');
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_with_decimal_timestamp_fails()
    {
        $request = $this->signed()->request();
        $this->timestamp('123.4');
        $response = $request->response();
        $this->assertValidationError($response, 'invalid_timestamp_format');
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_with_null_timestamp_fails()
    {
        $request = $this->signed()->request();
        $this->timestamp(null);
        $response = $request->response();
        $this->assertValidationError($response, 'timestamp_not_found');
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_with_empty_string_timestamp_fails()
    {
        $request = $this->signed()->request();
        $this->timestamp('');
        $response = $request->response();
        $this->assertValidationError($response, 'timestamp_not_found');
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_with_old_timestamp_fails()
    {
        $request = $this->signed()->request();
        $this->timestamp(
            $this->epoch->copy()->subtract(300001, 'milliseconds')->timestamp
        );
        $response = $request->response();
        $this->assertValidationError($response, 'invalid_timestamp_range');
        $this->assertUserLoggedOut();

        $newDate = $this->epoch->copy()->subtract(300000, 'milliseconds');
        $this->setTimestampFromDate($newDate);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_with_future_timestamp_fails()
    {
        $request = $this->signed()->request();
        $this->timestamp(
            $this->epoch->copy()->add(300001, 'milliseconds')->timestamp
        );
        $response = $request->response();
        $this->assertValidationError($response, 'invalid_timestamp_range');
        $this->assertUserLoggedOut();

        $newDate = $this->epoch->copy()->add(300000, 'milliseconds');
        $this->setTimestampFromDate($newDate);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();
    }
}
