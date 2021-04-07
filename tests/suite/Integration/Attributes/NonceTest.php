<?php
use ROTGP\AuthSodium\Test\IntegrationTestCase;

use ROTGP\AuthSodium\Test\Controllers\FooController;

use Carbon\Carbon;

class NonceTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');

        config(['authsodium.middleware.log_out_after_request' => true]);
    }

    public function test_that_signed_request_with_null_nonce_fails()
    {
        $request = $this->signed()->request();
        $this->nonce(null);
        $response = $request->response();
        $this->assertValidationError($response, 'nonce_not_found');
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_with_empty_string_nonce_fails()
    {
        $request = $this->signed()->request();
        $this->nonce('');
        $response = $request->response();
        $this->assertValidationError($response, 'nonce_not_found');
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_with_reused_nonce_fails()
    {
        $request = $this->signed()->request();
        $this->nonce('foobar');
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();

        // $request = $this->signed()->request();
        // $this->nonce('foobarz');
        $response = $request->response();
        $this->assertValidationError($response, 'nonce_already_exists');
        $this->assertUserLoggedOut();
    }
}
