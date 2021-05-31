<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

use Carbon\Carbon;

/**
 * For the majority of tests, getSystemTime is mocked so
 * that it returns `Carbon::now()` - which in turn
 * returns the result of `Carbon::setTestNow`
 *
 * In this test - $shouldMock is false, so the real
 * getSystemTime method will be called and will return
 * the real system time of when the test is run.
 */
class NativeSystemTimeSecondsTest extends IntegrationTestCase
{
    protected $shouldMock = false;

    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');

        config([
            'authsodium.timestamp.milliseconds' => false,
            'authsodium.timestamp.leeway' => 300,
        ]);
    }

    public function test_that_signed_request_with_st_patricks_day_timestamp_in_seconds_fails()
    {
        $request = $this->signed()->request();
        $response = $request->response();
        $this->setTimestampFromDate($this->epoch);
        $this->assertValidationError($response, 'invalid_timestamp_range');
        $this->assertEquals(1616007320, $this->getTimestamp());
    }

    public function test_that_signed_request_with_system_timestamp_in_seconds_succeeds()
    {
        $request = $this->signed()->request();
        $this->setTimestampToNow();
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
    }

    public function test_that_signed_request_with_system_timestamp_in_seconds_within_leeway_succeeds()
    {
        $request = $this->signed()->request();
        $this->setTimestampToNow(299);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
    }

    public function test_that_signed_request_with_system_timestamp_in_seconds_exceeding_leeway_fails()
    {
        $request = $this->signed()->request();
        $this->setTimestampToNow(301);
        $response = $request->response();
        $this->assertValidationError($response, 'invalid_timestamp_range');
    }
}
