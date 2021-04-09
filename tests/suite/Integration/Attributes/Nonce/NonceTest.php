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

    public function test_that_signed_request_with_single_user_and_reused_nonce_fails()
    {
        $request = $this->signed()->request();
        $this->nonce('foo');
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();

        $response = $request->response();
        $this->assertValidationError($response, 'nonce_already_exists');
        $this->assertUserLoggedOut();

        $response = $request->response();
        $this->assertValidationError($response, 'nonce_already_exists');
        $this->assertUserLoggedOut();

        $response = $request->response();
        $this->assertValidationError($response, 'nonce_already_exists');
        $this->assertUserLoggedOut();

        $this->nonce('bar');
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();

        $response = $request->response();
        $this->assertValidationError($response, 'nonce_already_exists');
        $this->assertUserLoggedOut();

        $this->nonce('baz');
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();

        $response = $request->response();
        $this->assertValidationError($response, 'nonce_already_exists');
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_with_multiple_users_with_same_nonce_succeeds()
    {
        $request = $this->signed()->request();
        $this->nonce('foo');
        $this->user($this->users[0]);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();

        $this->nonce('foo');
        $this->user($this->users[1]);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();

        $this->nonce('foo');
        $this->user($this->users[2]);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();

        $this->nonce('bar');
        $this->user($this->users[2]);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();

        $this->nonce('bar');
        $this->user($this->users[1]);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();

        $this->nonce('bar');
        $this->user($this->users[1]);
        $response = $request->response();
        $this->assertValidationError($response, 'nonce_already_exists');
        $this->assertUserLoggedOut();

        $this->nonce('bar');
        $this->user($this->users[0]);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();
    }

    public function test_that_signed_request_with_nonces_pertaining_to_different_time_ranges_fails()
    {
        $request = $this->signed()->request();
        $this->nonce('foo');
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        $this->assertUserLoggedOut();

        $response = $request->response();
        $this->assertValidationError($response, 'nonce_already_exists');
        $this->assertUserLoggedOut();

        $oneSecondInTheFuture = $this->epoch->add(1, 'second');
        Carbon::setTestNow($oneSecondInTheFuture);
        $this->timestamp($oneSecondInTheFuture->timestamp);
        $response = $request->response();
        $this->assertValidationError($response, 'nonce_already_exists');
        $this->assertUserLoggedOut();
    }
}
