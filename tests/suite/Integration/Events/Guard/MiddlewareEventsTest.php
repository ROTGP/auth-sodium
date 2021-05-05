<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

class MiddlewareEventsTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
    }
    
    public function test_that_signed_request_with_fails_then_succeeds_and_produces_expected_events()
    {
        $response = $this->signed()->request()->flipSignature()->response();
        $this->assertUnauthorized($response);
        $this->assertUserLoggedOut();

        $response = $this->signed()->request()->flipSignature()->response();
        $response->assertStatus(200);
        $this->assertUserLoggedOut();
        
        $event = $this->events[0];
        $this->assertTrue(is_a($event, 'Illuminate\Auth\Events\Attempting'));
        $this->assertEquals('web', $event->guard);
        $this->assertEquals($event->credentials, [
            "public_key" => "XMa723yCF6cOefVMUK5R+3PO8Z0Y17xmyHE1sfzwc6k=",
            "message" => 'gethttp://localhost/foos{"a":"apple","b":"banana","c":"carrot"}dawn83@yahoo.com16160073200001',
            "signature" => 'UAjRww01gB3nYV0O42oK2+Y1Fs5PGcA2FllwNMmT6UGD+bRezZ9rOR3mrTOaiXrQeoyfUguxdedgLWorJ20/Bg=='
        ]);

        $event = $this->events[1];
        $this->assertTrue(is_a($event, 'Illuminate\Auth\Events\Failed'));
        $this->assertEquals('web', $event->guard);
        $this->assertTrue(is_a($event->user, 'ROTGP\AuthSodium\Test\Models\User'));
        $this->assertTrue($this->users[0]['model']->is($event->user));
        $this->assertEquals($event->credentials, [
            "public_key" => "XMa723yCF6cOefVMUK5R+3PO8Z0Y17xmyHE1sfzwc6k=",
            "message" => 'gethttp://localhost/foos{"a":"apple","b":"banana","c":"carrot"}dawn83@yahoo.com16160073200001',
            "signature" => 'UAjRww01gB3nYV0O42oK2+Y1Fs5PGcA2FllwNMmT6UGD+bRezZ9rOR3mrTOaiXrQeoyfUguxdedgLWorJ20/Bg=='
        ]);

        $event = $this->events[2];
        $this->assertTrue(is_a($event, 'Illuminate\Auth\Events\Attempting'));
        $this->assertEquals('web', $event->guard);
        $this->assertEquals($event->credentials, [
            "public_key" => "XMa723yCF6cOefVMUK5R+3PO8Z0Y17xmyHE1sfzwc6k=",
            "message" => 'gethttp://localhost/foos{"a":"apple","b":"banana","c":"carrot"}dawn83@yahoo.com16160073200001',
            "signature" => 'uAjRww01gB3nYV0O42oK2+Y1Fs5PGcA2FllwNMmT6UGD+bRezZ9rOR3mrTOaiXrQeoyfUguxdedgLWorJ20/Bg=='
        ]);

        $event = $this->events[3];
        $this->assertTrue(is_a($event, 'Illuminate\Auth\Events\Authenticated'));
        $this->assertEquals('web', $event->guard);
        $this->assertTrue(is_a($event->user, 'ROTGP\AuthSodium\Test\Models\User'));
        $this->assertTrue($this->users[0]['model']->is($event->user));
        
        $event = $this->events[4];
        $this->assertTrue(is_a($event, 'ROTGP\AuthSodium\Events\Invalidated'));
        $this->assertEquals('web', $event->guard);
        $this->assertTrue(is_a($event->user, 'ROTGP\AuthSodium\Test\Models\User'));
        $this->assertTrue($this->users[0]['model']->is($event->user));
    }

    public function test_that_signed_request_to_resource_that_uses_named_guard_succeeds_and_produces_expected_events()
    {
        $response = $this->signed()->request()->response();
        $response->assertStatus(200);
        $this->assertUserLoggedOut();

        $event = $this->events[0];
        $this->assertTrue(is_a($event, 'Illuminate\Auth\Events\Attempting'));
        $this->assertEquals('web', $event->guard);
        $this->assertEquals($event->credentials, [
            "public_key" => "XMa723yCF6cOefVMUK5R+3PO8Z0Y17xmyHE1sfzwc6k=",
            "message" => 'gethttp://localhost/foos{"a":"apple","b":"banana","c":"carrot"}dawn83@yahoo.com16160073200001',
            "signature" => 'uAjRww01gB3nYV0O42oK2+Y1Fs5PGcA2FllwNMmT6UGD+bRezZ9rOR3mrTOaiXrQeoyfUguxdedgLWorJ20/Bg=='
        ]);
        
        $event = $this->events[1];
        $this->assertTrue(is_a($event, 'Illuminate\Auth\Events\Authenticated'));
        $this->assertEquals('web', $event->guard);
        $this->assertTrue(is_a($event->user, 'ROTGP\AuthSodium\Test\Models\User'));
        $this->assertTrue($this->users[0]['model']->is($event->user));
        
        $event = $this->events[2];
        $this->assertTrue(is_a($event, 'ROTGP\AuthSodium\Events\Invalidated'));
        $this->assertEquals('web', $event->guard);
        $this->assertTrue(is_a($event->user, 'ROTGP\AuthSodium\Test\Models\User'));
        $this->assertTrue($this->users[0]['model']->is($event->user));
    }
}
