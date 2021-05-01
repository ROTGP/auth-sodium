<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

use ROTGP\AuthSodium\Models\Throttle;
use Carbon\Carbon;

class ThrottleTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
    }

    public function test_that_requests_from_user_are_throttled_and_eventually_blocked()
    {
        // config(['authsodium.throttle.decay' => [0, 0, 0, 1, 3]]);
        
        $userId = 1;

        // successful request
        $response = $this->signed()->request()->withUser($userId)->nonce(1)->response();
        $this->assertSuccessfulRequest($response);
        $this->assertNull(Throttle::first());
        
        
        /**
         * Bad signature - no throttle will exist, and
         * then on failing the request we log the
         * throttle, which will have zero attempts and
         * the timestamp of 1616007320, which
         * corresponds to the current time (our epoch).
         */
        $response = $this->signed()->request()->withUser($userId)->nonce(2)->flipSignature()->response();
        $this->assertUnauthorized($response);
        $this->assertNotNull(Throttle::first());
        $this->assertEquals(Throttle::first()->attempts, 0);
        $this->assertEquals(Throttle::first()->try_again, 1616007320);
        $this->assertEquals(Throttle::first()->try_again, $this->epoch->getTimestamp());
        
        
        /**
         * We're consuming our first attempt. The value
         * of try_again will remain unchanged because
         * our first decay value is zero.
         */
        $response = $this->response();
        $this->assertUnauthorized($response);
        $this->assertEquals(Throttle::first()->attempts, 1);
        $this->assertEquals(Throttle::first()->try_again, 1616007320);
        
        /**
         * We're consuming our second attempt. The value
         * of try_again will remain unchanged because
         * our second decay value is zero.
         */
        $response = $this->response();
        $this->assertUnauthorized($response);
        $this->assertEquals(Throttle::first()->attempts, 2);
        $this->assertEquals(Throttle::first()->try_again, 1616007320);
        
        /**
         * We're consuming our third attempt. The value
         * of try_again will remain unchanged because
         * our third decay value is zero.
         */
        $response = $this->response();
        $this->assertUnauthorized($response);
        $this->assertEquals(Throttle::first()->attempts, 3);
        $this->assertEquals(Throttle::first()->try_again, 1616007321);
        
        /**
         * We're consuming our fourth attempt. The value
         * of try_again will increase by one second, as
         * our fourth decay value is one.
         */
        $response = $this->response();
        $this->assertTooManyRequests($response, 1616007321);
        $this->assertEquals(Throttle::first()->try_again, 1616007321);
        
        /**
         * Try a bunch of times - throttle won't get
         * updated as the request won't even get looked
         * at - because the system/request time
         * (1616007320) is less than the required
         * try_again time (1616007321).
         */
        $response = $this->response();
        $this->assertTooManyRequests($response, 1616007321);

        $response = $this->response();
        $this->assertTooManyRequests($response, 1616007321);

        $response = $this->response();
        $this->assertTooManyRequests($response, 1616007321);

        $response = $this->response();
        $this->assertTooManyRequests($response, 1616007321);

        $response = $this->response();
        $this->assertTooManyRequests($response, 1616007321);

        /**
         * Advance one second (from epoch) such that
         * system/request time (1616007321) is equal to
         * that of the required try_again time
         * (1616007321). So now, the request will be
         * assessed, and rejected. The 
         */
        $response = $this->updateTestNow(1, 'second')->flipSignature()->response();
        $this->assertUnauthorized($response);
        $this->assertEquals(Throttle::first()->attempts, 4);
        $this->assertEquals(Throttle::first()->try_again, 1616007324);
        

        /**
         * Try a bunch of times (again) - throttle won't
         * get updated as the request won't even get
         * looked at - because the system/request time
         * (1616007321) is less than the required
         * try_again time (1616007324).
         */
        $response = $this->response();
        $this->assertTooManyRequests($response, 1616007324);

        $response = $this->response();
        $this->assertTooManyRequests($response, 1616007324);

        $response = $this->response();
        $this->assertTooManyRequests($response, 1616007324);

        $response = $this->response();
        $this->assertTooManyRequests($response, 1616007324);

        $response = $this->response();
        $this->assertTooManyRequests($response, 1616007324);
        
        /**
         * Advance three seconds (from epoch) such that
         * system/request time (1616007323) is one
         * second less than the required try_again time
         * (1616007324). Once again, it will be rejected
         * and the throttle untouched.
         */
        $response = $this->updateTestNow(3, 'seconds')->flipSignature()->response();
        $this->assertTooManyRequests($response, 1616007324);
        $this->assertEquals(Throttle::first()->attempts, 4);
        $this->assertEquals(Throttle::first()->try_again, 1616007324);
        

        /**
         * Advance five seconds (from epoch) such that
         * system/request time (1616007325) is one
         * second more than the required try_again time
         * (1616007324). It should be ...
         */
        $response = $this->updateTestNow(5, 'seconds')->flipSignature()->response();
        $this->assertForbidden($response);
        $this->assertEquals(Throttle::first()->attempts, 5);
        $this->assertEquals(Throttle::first()->try_again, 1616007324);

        $response = $this->updateTestNow(5, 'seconds')->flipSignature()->response();
        $this->assertForbidden($response);
        $this->assertEquals(Throttle::first()->attempts, 5);
        $this->assertEquals(Throttle::first()->try_again, 1616007324);
        
        $response = $this->updateTestNow(5, 'seconds')->flipSignature()->response();
        $this->assertForbidden($response);
        $this->assertEquals(Throttle::first()->attempts, 5);
        $this->assertEquals(Throttle::first()->try_again, 1616007324);
    }

    public function test_that_requests_from_user_are_throttled_and_eventually_blocked_custom_one()
    {
        config(['authsodium.throttle.decay' => [1, 2, 3]]);
        
        $userId = 1;
        
        $response = $this->signed()->request()->withUser($userId)->nonce(1)->response();
        $this->assertSuccessfulRequest($response);
        
        $response = $this->signed()->request()->withUser($userId)->nonce(2)->flipSignature()->response();
        $this->assertUnauthorized($response);
        
        $response = $this->response();
        $this->assertTooManyRequests($response, 1616007321);
        
        $response = $this->response();
        $this->assertTooManyRequests($response, 1616007321);
        
        $response = $this->response();
        $this->assertTooManyRequests($response, 1616007321);

        $response = $this->updateTestNow(1, 'second')->flipSignature()->response();
        $this->assertUnauthorized($response);

        $response = $this->response();
        $this->assertTooManyRequests($response, 1616007323);
        
        $response = $this->response();
        $this->assertTooManyRequests($response, 1616007323);
        
        $response = $this->response();
        $this->assertTooManyRequests($response, 1616007323);
        
        $response = $this->updateTestNow(3, 'seconds')->response();
        $this->assertSuccessfulRequest($response);

        $this->assertNull(Throttle::first());
    }

    public function test_that_requests_from_user_are_blocked_after_one_immediate_retry()
    {
        config(['authsodium.throttle.decay' => [0]]);
        
        $userId = 1;

        $response = $this->signed()->request()->withUser($userId)->nonce(1)->response();
        $this->assertSuccessfulRequest($response);
        $this->assertNull(Throttle::first());

        $response = $this->signed()->request()->withUser($userId)->nonce(2)->flipSignature()->response();
        $this->assertUnauthorized($response);
        
        $response = $this->response();
        $this->assertForbidden($response);
        $this->assertTrue(boolval(Throttle::first()->blocked));
        
        $response = $this->updateTestNow(5, 'seconds')->response();
        $this->assertForbidden($response);

        $response = $this->updateTestNow(5, 'seconds')->response();
        $this->assertForbidden($response);

        $response = $this->updateTestNow(5, 'seconds')->response();
        $this->assertForbidden($response);

        $response = $this->signed()->request()->withUser($userId)->nonce(2)->response();
        $this->assertForbidden($response);
    }

    public function test_multiple_id_addresses()
    {
        config(['authsodium.throttle.decay' => [0]]);
        
        $userId = 1;

        $this->ipAddress(1);

        $response = $this->signed()->request()->withUser($userId)->nonce(1)->response();
        $this->assertSuccessfulRequest($response);
        $this->assertNull(Throttle::first());

        $response = $this->signed()->request()->withUser($userId)->nonce(2)->flipSignature()->response();
        $this->assertUnauthorized($response);
        
        $response = $this->response();
        $this->assertForbidden($response);
        $this->assertTrue(boolval(Throttle::first()->blocked));
        
        $response = $this->signed()->request()->withUser($userId)->nonce(2)->response();
        $this->assertForbidden($response);

        $response = $this->signed()->request()->withUser($userId)->nonce(2)->response();
        $this->assertForbidden($response);

        $this->ipAddress(2);
        $response = $this->new()->signed()->request()->withUser($userId)->nonce(2)->response();
        $this->assertSuccessfulRequest($response);

        $this->ipAddress(1);
        $response = $this->new()->signed()->request()->withUser($userId)->nonce(3)->response();
        $this->assertForbidden($response);

        $this->ipAddress(2);
        $response = $this->new()->signed()->request()->withUser($userId)->nonce(4)->response();
        $this->assertSuccessfulRequest($response);

        $response = $this->new()->signed()->request()->withUser($userId)->nonce(5)->flipSignature()->response();
        $this->assertUnauthorized($response);

        $throttles = Throttle::all();
        $this->assertEquals(1, $throttles[0]->ip_address);
        $this->assertTrue(boolval($throttles[0]->blocked));
        
        $this->assertEquals(2, $throttles[1]->ip_address);
        $this->assertFalse(boolval($throttles[1]->blocked));
    }

    public function test_multiple_users()
    {
        config(['authsodium.throttle.decay' => [0]]);
        
        $userId = 1;

        $response = $this->signed()->request()->withUser($userId)->nonce(1)->response();
        $this->assertSuccessfulRequest($response);
        $this->assertNull(Throttle::first());

        $response = $this->signed()->request()->withUser($userId)->nonce(2)->flipSignature()->response();
        $this->assertUnauthorized($response);
        
        $response = $this->response();
        $this->assertForbidden($response);
        $this->assertTrue(boolval(Throttle::first()->blocked));
        
        $response = $this->signed()->request()->withUser($userId)->nonce(2)->response();
        $this->assertForbidden($response);

        $response = $this->signed()->request()->withUser($userId)->nonce(2)->response();
        $this->assertForbidden($response);

        $userId = 2;

        $response = $this->new()->signed()->request()->withUser($userId)->nonce(2)->response();
        $this->assertSuccessfulRequest($response);

        $userId = 1;
        $response = $this->new()->signed()->request()->withUser($userId)->nonce(3)->response();
        $this->assertForbidden($response);

        $userId = 2;
        $response = $this->new()->signed()->request()->withUser($userId)->nonce(4)->response();
        $this->assertSuccessfulRequest($response);

        $response = $this->new()->signed()->request()->withUser($userId)->nonce(5)->flipSignature()->response();
        $this->assertUnauthorized($response);

        $throttles = Throttle::all();
        $this->assertEquals('127.0.0.1', $throttles[0]->ip_address);
        $this->assertTrue(boolval($throttles[0]->blocked));
        
        $this->assertEquals('127.0.0.1', $throttles[1]->ip_address);
        $this->assertFalse(boolval($throttles[1]->blocked));
    }

    public function test_clear_throttle_by_user()
    {
        config(['authsodium.throttle.decay' => [0]]);

        $userId = 1;

        $response = $this->signed()->request()->withUser($userId)->nonce(1)->response();
        $this->assertSuccessfulRequest($response);
        $this->assertNull(Throttle::first());

        $response = $this->signed()->request()->withUser($userId)->nonce(2)->flipSignature()->response();
        $this->assertUnauthorized($response);
        
        $response = $this->response();
        $this->assertForbidden($response);
        $this->assertTrue(boolval(Throttle::first()->blocked));
        
        $response = $this->signed()->request()->withUser($userId)->nonce(2)->response();
        $this->assertForbidden($response);

        $response = $this->signed()->request()->withUser($userId)->nonce(2)->response();
        $this->assertForbidden($response);

        $userId = 2;

        $response = $this->new()->signed()->request()->withUser($userId)->nonce(2)->response();
        $this->assertSuccessfulRequest($response);

        $userId = 1;
        $response = $this->new()->signed()->request()->withUser($userId)->nonce(3)->response();
        $this->assertForbidden($response);

        $userId = 2;
        $response = $this->new()->signed()->request()->withUser($userId)->nonce(4)->response();
        $this->assertSuccessfulRequest($response);

        $response = $this->new()->signed()->request()->withUser($userId)->nonce(5)->flipSignature()->response();
        $this->assertUnauthorized($response);

        $throttles = Throttle::all();
        $this->assertEquals(1, $throttles[0]->user_id);
        $this->assertEquals('127.0.0.1', $throttles[0]->ip_address);
        $this->assertTrue(boolval($throttles[0]->blocked));
        
        $this->assertEquals(2, $throttles[1]->user_id);
        $this->assertEquals('127.0.0.1', $throttles[1]->ip_address);
        $this->assertFalse(boolval($throttles[1]->blocked));
        
        $this->assertEquals(1, authsodium()->clearThrottle(1, null));
        $throttles = Throttle::all();

        $this->assertCount(1, $throttles);

        $this->assertEquals(2, $throttles[0]->user_id);
        $this->assertEquals('127.0.0.1', $throttles[0]->ip_address);
        $this->assertFalse(boolval($throttles[0]->blocked));
        
        $this->assertEquals(1, authsodium()->clearThrottle(2, null));
        $this->assertCount(0, Throttle::all()); 
    }

    public function test_clear_throttle_by_ip_addresss()
    {
        config(['authsodium.throttle.decay' => [0]]);

        $userId = 1;
        $this->ipAddress(1);

        $response = $this->signed()->request()->withUser($userId)->nonce(1)->response();
        $this->assertSuccessfulRequest($response);
        $this->assertNull(Throttle::first());

        $response = $this->signed()->request()->withUser($userId)->nonce(2)->flipSignature()->response();
        $this->assertUnauthorized($response);
        
        $response = $this->response();
        $this->assertForbidden($response);
        $this->assertTrue(boolval(Throttle::first()->blocked));
        
        $response = $this->signed()->request()->withUser($userId)->nonce(2)->response();
        $this->assertForbidden($response);

        $response = $this->signed()->request()->withUser($userId)->nonce(2)->response();
        $this->assertForbidden($response);

        $this->ipAddress(2);

        $response = $this->new()->signed()->request()->withUser($userId)->nonce(2)->response();
        $this->assertSuccessfulRequest($response);

        $this->ipAddress(1);
        $response = $this->new()->signed()->request()->withUser($userId)->nonce(3)->response();
        $this->assertForbidden($response);

        $this->ipAddress(2);
        $response = $this->new()->signed()->request()->withUser($userId)->nonce(4)->response();
        $this->assertSuccessfulRequest($response);

        $response = $this->new()->signed()->request()->withUser($userId)->nonce(5)->flipSignature()->response();
        $this->assertUnauthorized($response);

        $throttles = Throttle::all();
        $this->assertEquals(1, $throttles[0]->user_id);
        $this->assertEquals(1, $throttles[0]->ip_address);
        $this->assertTrue(boolval($throttles[0]->blocked));
        
        $this->assertEquals(1, $throttles[1]->user_id);
        $this->assertEquals(2, $throttles[1]->ip_address);
        $this->assertFalse(boolval($throttles[1]->blocked));
        
        $this->assertEquals(1, authsodium()->clearThrottle(null, 1));
        $throttles = Throttle::all();

        $this->assertCount(1, $throttles);

        $this->assertEquals(1, $throttles[0]->user_id);
        $this->assertEquals(2, $throttles[0]->ip_address);
        $this->assertFalse(boolval($throttles[0]->blocked));
        
        $this->assertEquals(1, authsodium()->clearThrottle(null, 2));
        $this->assertCount(0, Throttle::all()); 
    }

    public function test_clear_throttle_by_user_and_ip_address()
    {
        Throttle::create([
            'user_id' => 1,
            'ip_address' => 1,
            'attempts' => 1,
            'try_again' => 1,
            'blocked' => true
        ]);

        Throttle::create([
            'user_id' => 1,
            'ip_address' => 2,
            'attempts' => 1,
            'try_again' => 1,
            'blocked' => true
        ]);

        Throttle::create([
            'user_id' => 2,
            'ip_address' => 1,
            'attempts' => 1,
            'try_again' => 1,
            'blocked' => true
        ]);

        Throttle::create([
            'user_id' => 2,
            'ip_address' => 2,
            'attempts' => 1,
            'try_again' => 1,
            'blocked' => true
        ]);

        $this->assertCount(4, Throttle::all());

        $this->assertEquals(2, authsodium()->clearThrottle(null, 1));
        $this->assertCount(2, Throttle::all());

        $this->assertEquals(0, authsodium()->clearThrottle(1, 1));
        $this->assertCount(2, Throttle::all());

        $this->assertEquals(1, authsodium()->clearThrottle(1, null));
        $this->assertCount(1, Throttle::all());

        $this->assertEquals(1, authsodium()->clearThrottle(2, null));
        $this->assertCount(0, Throttle::all());
    }
}
