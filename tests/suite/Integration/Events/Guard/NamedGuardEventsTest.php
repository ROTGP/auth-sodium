<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

class NamedGuardEventsTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class);
        
        config(['authsodium.guard.name' => 'authsodium']);
    }
    
    /**
     * Here we try to create a new Foo resource, with a
     * post to the unprotected resource FooController,
     * which in turn tries to build a payload containing
     * our named guard `Auth::guard('authsodium')->id()`;
     * This should fail and cause a validation error,
     * because the request is not signed. If the
     * resource had been protected by middleware (and
     * assuming that 'abort_on_invalid_signature' were
     * true), then the error code would be 400 instead
     * of 422.
     */
    public function test_that_signed_request_with_fails_then_succeeds_and_produces_expected_events()
    {
        $response = $this->unsigned()->request('post')->response();
        $this->assertValidationError($response, 'user id can not be null');
        $this->assertUserLoggedOut();

        $request = $this->signed()->request('post');
        $signature = $request->getSignature();
        $signature[0] = 'k'; // swap first 'K' for lowwercase 'k'

        $this->signature($signature);
        $response =  $request->response();
        $this->assertValidationError($response, 'user id can not be null');
        $this->assertUserLoggedOut();
        
        $signature[0] = 'K'; // swap back to original
        $this->signature($signature);
        $response = $request->response();
        $response->assertStatus(201);
        $this->assertUserLoggedOut();
        
        $event = $this->events[0];
        $this->assertTrue(is_a($event, 'Illuminate\Auth\Events\Attempting'));
        $this->assertEquals('authsodium', $event->guard);
        $this->assertEquals($event->credentials, [
            "public_key" => "XMa723yCF6cOefVMUK5R+3PO8Z0Y17xmyHE1sfzwc6k=",
            "message" => 'posthttp://localhost/foos{"a":"apple","b":"banana","c":"carrot"}{"name":"Jim"}dawn83@yahoo.com16160073200001',
            "signature" => "k9kU+dka556x9d101yjha/Ij3PLbBrjOUx/WnSLuWKYoAWA/79mqD/KDeXjOuK3iXIHdum+yMp3S0sfPh+f+DQ=="
        ]);

        $event = $this->events[1];
        $this->assertTrue(is_a($event, 'Illuminate\Auth\Events\Failed'));
        $this->assertEquals('authsodium', $event->guard);
        $this->assertTrue(is_a($event->user, 'ROTGP\AuthSodium\Test\Models\User'));
        $this->assertTrue($this->users[0]['model']->is($event->user));
        $this->assertEquals($event->credentials, [
            "public_key" => "XMa723yCF6cOefVMUK5R+3PO8Z0Y17xmyHE1sfzwc6k=",
            "message" => 'posthttp://localhost/foos{"a":"apple","b":"banana","c":"carrot"}{"name":"Jim"}dawn83@yahoo.com16160073200001',
            "signature" => "k9kU+dka556x9d101yjha/Ij3PLbBrjOUx/WnSLuWKYoAWA/79mqD/KDeXjOuK3iXIHdum+yMp3S0sfPh+f+DQ=="
        ]);

        $event = $this->events[2];
        $this->assertTrue(is_a($event, 'Illuminate\Auth\Events\Attempting'));
        $this->assertEquals('authsodium', $event->guard);
        $this->assertEquals($event->credentials, [
            "public_key" => "XMa723yCF6cOefVMUK5R+3PO8Z0Y17xmyHE1sfzwc6k=",
            "message" => 'posthttp://localhost/foos{"a":"apple","b":"banana","c":"carrot"}{"name":"Jim"}dawn83@yahoo.com16160073200001',
            "signature" => "K9kU+dka556x9d101yjha/Ij3PLbBrjOUx/WnSLuWKYoAWA/79mqD/KDeXjOuK3iXIHdum+yMp3S0sfPh+f+DQ=="
        ]);

        $event = $this->events[3];
        $this->assertTrue(is_a($event, 'Illuminate\Auth\Events\Authenticated'));
        $this->assertEquals('authsodium', $event->guard);
        $this->assertTrue(is_a($event->user, 'ROTGP\AuthSodium\Test\Models\User'));
        $this->assertTrue($this->users[0]['model']->is($event->user));
        
        $event = $this->events[4];
        $this->assertTrue(is_a($event, 'ROTGP\AuthSodium\Events\Invalidated'));
        $this->assertEquals('authsodium', $event->guard);
        $this->assertTrue(is_a($event->user, 'ROTGP\AuthSodium\Test\Models\User'));
        $this->assertTrue($this->users[0]['model']->is($event->user));
    }

    public function test_that_signed_request_to_resource_that_uses_named_guard_succeeds_and_produces_expected_events()
    {
        $response = $this->signed()->request('post')->response();
        $response->assertStatus(201);
        $this->assertUserLoggedOut();

        $event = $this->events[0];
        $this->assertTrue(is_a($event, 'Illuminate\Auth\Events\Attempting'));
        $this->assertEquals('authsodium', $event->guard);
        $this->assertEquals($event->credentials, [
            "public_key" => "XMa723yCF6cOefVMUK5R+3PO8Z0Y17xmyHE1sfzwc6k=",
            "message" => 'posthttp://localhost/foos{"a":"apple","b":"banana","c":"carrot"}{"name":"Jim"}dawn83@yahoo.com16160073200001',
            "signature" => "K9kU+dka556x9d101yjha/Ij3PLbBrjOUx/WnSLuWKYoAWA/79mqD/KDeXjOuK3iXIHdum+yMp3S0sfPh+f+DQ=="
        ]);
        
        $event = $this->events[1];
        $this->assertTrue(is_a($event, 'Illuminate\Auth\Events\Authenticated'));
        $this->assertEquals('authsodium', $event->guard);
        $this->assertTrue(is_a($event->user, 'ROTGP\AuthSodium\Test\Models\User'));
        $this->assertTrue($this->users[0]['model']->is($event->user));
        
        $event = $this->events[2];
        $this->assertTrue(is_a($event, 'ROTGP\AuthSodium\Events\Invalidated'));
        $this->assertEquals('authsodium', $event->guard);
        $this->assertTrue(is_a($event->user, 'ROTGP\AuthSodium\Test\Models\User'));
        $this->assertTrue($this->users[0]['model']->is($event->user));
    }
}
