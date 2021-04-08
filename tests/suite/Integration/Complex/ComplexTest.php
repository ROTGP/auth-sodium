<?php
use ROTGP\AuthSodium\Test\IntegrationTestCase;

use ROTGP\AuthSodium\Models\Nonce;
use ROTGP\AuthSodium\Test\Controllers\FooController;

use Carbon\Carbon;

class ComplexTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
    }

    /**
     * All requests should fall within a valid
     * timeframe, and should progress over time.
     */
    public function test_that_many_users_with_many_valid_signed_requests_succeed()
    {
        for ($i = 0; $i < 1000; $i++) {
            $request = $this->signed()->request();
            $this->user(self::faker()->randomElement($this->users));
            $this->nonce($i + 1);
            $oneSecondInTheFuture = $this->epoch->add(1, 'second');
            Carbon::setTestNow($oneSecondInTheFuture);
            $response = $request->response();
        }

        $this->assertEquals(Nonce::all()->count(), 1000);

        $start = Carbon::createFromTimestamp(Nonce::get()->first()->timestamp);
        $end = Carbon::createFromTimestamp(Nonce::get()->last()->timestamp);
        $timeDiff = $start->diffInSeconds($end);
        $this->assertEquals($timeDiff, 999);
    }
}
