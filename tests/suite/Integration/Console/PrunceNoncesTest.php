<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;
use ROTGP\AuthSodium\Models\Nonce;

use Carbon\Carbon;

class PrunceNoncesTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
        
        config(['authsodium.prune_nonces_after_request' => false]);
    }

    /**
     * All requests should fall within a valid
     * timeframe, and should progress over time.
     */
    public function test_that_nonces_are_prunced_when_called_via_artisan()
    {
        for ($i = 0; $i < 10; $i++) {
            $request = $this->signed()->request();
            $this->user(self::faker()->randomElement($this->users));
            $this->nonce($i + 1);
            $oneMinuteInTheFuture = $this->epoch->add(2, 'minutes');
            Carbon::setTestNow($oneMinuteInTheFuture);
            $response = $request->response();
        }

        $this->assertEquals(Nonce::all()->count(), 10);
        
        $start = Carbon::createFromTimestamp(Nonce::get()->first()->timestamp);
        $end = Carbon::createFromTimestamp(Nonce::get()->last()->timestamp);
        $timeDiff = $start->diffInSeconds($end);
        $this->assertEquals($timeDiff, 1080000);

        $deleteCount = Artisan::call('authsodium:prune');
        
        $this->assertEquals($deleteCount, 8);
        $this->assertEquals(Nonce::all()->count(), 2);
    }
}
