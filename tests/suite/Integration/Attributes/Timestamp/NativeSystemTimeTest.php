<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

use Carbon\Carbon;

/**
 * For the majority of tests, we mock the getSystemTime
 * method so that it returns `Carbon::now()` - which in
 * turn returns the result of `Carbon::setTestNow`
 *
 * In this test - $shouldMock is false, so the real
 * getSystemTime method will be called and will return
 * the real system time of when the test is run.
 */
class NativeSystemTimeTest extends IntegrationTestCase
{
    protected $shouldMock = false;

    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
    }

    public function test_that_signed_request_with_st_patricks_day_timestamp_fails()
    {
        $request = $this->signed()->request();
        $response = $request->response();
        $this->assertValidationError($response, 'invalid_timestamp_range');
        $this->assertUserLoggedOut();
        $this->assertEquals(1616007320000, $this->getTimestamp());
    }

    public function test_that_signed_request_with_system_timestamp_succeeds()
    {
        $request = $this->signed()->request();
        $this->setTimestampToNow();
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_with_system_timestamp_within_leeway_succeeds()
    {
        $request = $this->signed()->request();
        $this->setTimestampToNow(299500);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_with_system_timestamp_exceeding_leeway_fails()
    {
        $request = $this->signed()->request();
        $this->setTimestampToNow(300500);
        $response = $request->response();
        $this->assertValidationError($response, 'invalid_timestamp_range');
        $this->assertUserLoggedOut();
    }
}
