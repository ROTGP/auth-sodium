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
    }

    public function test_that_signed_request_with_empty_string_nonce_fails()
    {
        $request = $this->signed()->request();
        $this->nonce('');
        $response = $request->response();
        $this->assertValidationError($response, 'nonce_not_found');
    }

    public function test_that_signed_request_with_single_user_and_reused_nonce_fails()
    {
        $request = $this->signed()->request();
        $this->nonce('foo');
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        
        $response = $request->response();
        $this->assertValidationError($response, 'nonce_already_exists');
        
        $response = $request->response();
        $this->assertValidationError($response, 'nonce_already_exists');
        
        $response = $request->response();
        $this->assertValidationError($response, 'nonce_already_exists');
        
        $this->nonce('bar');
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        
        
        $response = $request->response();
        $this->assertValidationError($response, 'nonce_already_exists');
        
        $this->nonce('baz');
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        
        $response = $request->response();
        $this->assertValidationError($response, 'nonce_already_exists');
    }

    public function test_that_signed_request_with_multiple_users_with_same_nonce_succeeds()
    {
        $request = $this->signed()->request();
        $this->nonce('foo');
        $this->user($this->users[0]);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        
        $this->nonce('foo');
        $this->user($this->users[1]);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        
        $this->nonce('foo');
        $this->user($this->users[2]);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        
        $this->nonce('bar');
        $this->user($this->users[2]);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        
        $this->nonce('bar');
        $this->user($this->users[1]);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        
        $this->nonce('bar');
        $this->user($this->users[1]);
        $response = $request->response();
        $this->assertValidationError($response, 'nonce_already_exists');
        
        $this->nonce('bar');
        $this->user($this->users[0]);
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        
    }

    public function test_that_signed_request_with_nonces_pertaining_to_different_time_ranges_fails()
    {
        $request = $this->signed()->request();
        $this->nonce('foo');
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
        
        $response = $request->response();
        $this->assertValidationError($response, 'nonce_already_exists');
        
        $oneSecondInTheFuture = $this->epoch->add(1, 'millisecond');
        $this->setTestNow($oneSecondInTheFuture);
        $response = $request->response();
        $this->assertValidationError($response, 'nonce_already_exists');
        
    }
}
