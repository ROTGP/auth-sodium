<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

use Carbon\Carbon;

class InvalidSignatureTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
    }

    public function test_that_signed_request_to_protected_resource_with_invalid_signature_fails()
    {
        // @TODO
        $request = $this->signed()->request();
        $signature = $request->getSignature();
        $response =  $request->response();
        
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_with_old_timestamp_fails()
    {
        $request = $this->signed()->request();
        $this->timestamp(
            Carbon::createFromTimestamp(
                $this->getTimestamp()
            )->subtract(15, 'minutes')->timestamp
        );
        $response = $request->response();
        $json = $this->decodeResponse($response);
        $this->assertValidationError($response, 'invalid_timestamp_range');
        $this->assertUserLoggedOut();
    }
}
