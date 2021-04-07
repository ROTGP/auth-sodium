<?php
use ROTGP\AuthSodium\Test\IntegrationTestCase;

use ROTGP\AuthSodium\Test\Controllers\FooController;

use Carbon\Carbon;

class InvalidTimestampTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
    }

    public function test_that_signed_request_with_timestamp_that_before_leeway_fails()
    {
        $request = $this->signed()->request();
        $this->timestamp(
            Carbon::createFromTimestamp(
                $this->getTimestamp()
            )->subtract(301, 'seconds')->timestamp
        );
        $response = $request->response();
        $json = $this->decodeResponse($response);
        $this->assertValidationError($response, 'invalid_timestamp_range');
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_with_timestamp_that_after_leeway_fails()
    {
        $request = $this->signed()->request();
        $this->timestamp(
            Carbon::createFromTimestamp(
                $this->getTimestamp()
            )->add(301, 'seconds')->timestamp
        );
        $response = $request->response();
        $json = $this->decodeResponse($response);
        $this->assertValidationError($response, 'invalid_timestamp_range');
        $this->assertUserLoggedOut();
    }
}
