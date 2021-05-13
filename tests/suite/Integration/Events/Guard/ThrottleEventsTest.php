<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

class ThrottleEventsTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
    }
    
    public function test_that_signed_request_with_fails_then_succeeds_and_produces_expected_throttle_events()
    {
        $response = $this->signed()->request()->flipSignature()->response();
        $this->assertUnauthorized($response);

        $response = $this->response();
        $this->assertUnauthorized($response);

        $response = $this->response();
        $this->assertUnauthorized($response);

        $response = $this->response();
        $this->assertUnauthorized($response);

        $response = $this->response();
        $this->assertTooManyRequests($response, 1616007321000);

        $response = $this->response();
        $this->assertTooManyRequests($response, 1616007321000);

        $response = $this->response();
        $this->assertTooManyRequests($response, 1616007321000);

        $response = $this->updateTestNow(1000, 'milliseconds')->flipSignature()->response();
        $this->assertUnauthorized($response);

        $response = $this->response();
        $this->assertTooManyRequests($response, 1616007324000);
        
        $response = $this->updateTestNow(3000, 'milliseconds')->flipSignature()->response();
        $this->assertTooManyRequests($response, 1616007324000);
        
        $response = $this->updateTestNow(3000, 'milliseconds')->flipSignature()->response();
        $this->assertTooManyRequests($response, 1616007324000);
        
        $response = $this->updateTestNow(3000, 'milliseconds')->flipSignature()->response();
        $this->assertTooManyRequests($response, 1616007324000);
        
        $response = $this->updateTestNow(3000, 'milliseconds')->flipSignature()->response();
        $this->assertTooManyRequests($response, 1616007324000);
        
        $response = $this->updateTestNow(5000, 'milliseconds')->flipSignature()->response();
        $this->assertForbidden($response);

        $response = $this->response();
        $this->assertForbidden($response);

        $response = $this->response();
        $this->assertForbidden($response);

        $response = $this->response();
        $this->assertForbidden($response);


        for ($i = 0; $i < count($this->events); $i++) {
            $eventClasses[] = $this->events[$i]::class;
        }
        
        $this->assertEquals([
            'Illuminate\Auth\Events\Attempting',
            'Illuminate\Auth\Events\Failed',
            'Illuminate\Auth\Events\Attempting',
            'Illuminate\Auth\Events\Failed',
            'Illuminate\Auth\Events\Attempting',
            'Illuminate\Auth\Events\Failed',
            'Illuminate\Auth\Events\Attempting',
            'Illuminate\Auth\Events\Failed',
            'ROTGP\AuthSodium\Events\Throttled',
            'ROTGP\AuthSodium\Events\Throttled',
            'ROTGP\AuthSodium\Events\Throttled',
            'Illuminate\Auth\Events\Attempting',
            'Illuminate\Auth\Events\Failed',
            'ROTGP\AuthSodium\Events\Throttled',
            'ROTGP\AuthSodium\Events\Throttled',
            'ROTGP\AuthSodium\Events\Throttled',
            'ROTGP\AuthSodium\Events\Throttled',
            'ROTGP\AuthSodium\Events\Throttled',
            'Illuminate\Auth\Events\Attempting',
            'ROTGP\AuthSodium\Events\Blocked'
        ], $eventClasses);
        
        $event = $this->events[8];
        $this->assertTrue(is_a($event, 'ROTGP\AuthSodium\Events\Throttled'));
        $this->assertEquals('web', $event->guard);
        $this->assertTrue(is_a($event->user, 'ROTGP\AuthSodium\Test\Models\User'));
        $this->assertTrue(is_a($event->throttle, 'ROTGP\AuthSodium\Models\Throttle'));
        $this->assertTrue($event->user->is($this->users[0]['model']));
        $this->assertEquals($event->throttle->user_id, $this->users[0]['model']->id);
        $this->assertEquals($event->throttle->try_again, 1616007321000);
        $this->assertEquals($event->throttle->blocked, 0);
        
        $event = $this->events[13];
        $this->assertTrue(is_a($event, 'ROTGP\AuthSodium\Events\Throttled'));
        $this->assertEquals('web', $event->guard);
        $this->assertTrue(is_a($event->user, 'ROTGP\AuthSodium\Test\Models\User'));
        $this->assertTrue(is_a($event->throttle, 'ROTGP\AuthSodium\Models\Throttle'));
        $this->assertTrue($event->user->is($this->users[0]['model']));
        $this->assertEquals($event->throttle->user_id, $this->users[0]['model']->id);
        $this->assertEquals($event->throttle->try_again, 1616007324000);
        $this->assertEquals($event->throttle->blocked, 0);
        
        $event = $this->events[19];
        $this->assertTrue(is_a($event, 'ROTGP\AuthSodium\Events\Blocked'));
        $this->assertEquals('web', $event->guard);
        $this->assertTrue(is_a($event->user, 'ROTGP\AuthSodium\Test\Models\User'));
        $this->assertTrue(is_a($event->throttle, 'ROTGP\AuthSodium\Models\Throttle'));
        $this->assertTrue($event->user->is($this->users[0]['model']));
        $this->assertEquals($event->throttle->user_id, $this->users[0]['model']->id);
        $this->assertEquals($event->throttle->try_again, 1616007324000);
        $this->assertEquals($event->throttle->blocked, 1);

        $this->assertEquals([
            'eloquent.booting: ROTGP\AuthSodium\Models\Nonce',
            'eloquent.booted: ROTGP\AuthSodium\Models\Nonce',
            'eloquent.booting: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.booted: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.saving: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.creating: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.created: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.saved: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.retrieved: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.saving: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.updating: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.updated: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.saved: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.retrieved: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.saving: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.updating: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.updated: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.saved: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.retrieved: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.saving: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.updating: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.updated: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.saved: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.retrieved: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.retrieved: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.retrieved: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.retrieved: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.saving: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.updating: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.updated: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.saved: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.retrieved: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.retrieved: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.retrieved: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.retrieved: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.retrieved: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.retrieved: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.saving: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.updating: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.updated: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.saved: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.retrieved: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.retrieved: ROTGP\AuthSodium\Models\Throttle',
            'eloquent.retrieved: ROTGP\AuthSodium\Models\Throttle',
        ], $this->modelEvents);
    }
}
