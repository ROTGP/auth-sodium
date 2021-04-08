<?php
use ROTGP\AuthSodium\Test\IntegrationTestCase;

use ROTGP\AuthSodium\Models\Nonce;
use ROTGP\AuthSodium\Test\Controllers\FooController;

use Carbon\Carbon;

class PruneTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
    }

    public function test_that_a_nonce_is_pruned_when_making_a_request_after_leeway_period()
    {
        $request = $this->signed()->request();
        $this->nonce('1');
        $response = $request->response();
        $this->assertEquals(Nonce::count(), 1);

        $twoNinetyNineSecondsInTheFuture = $this->epoch->copy()->add(299, 'seconds');
        Carbon::setTestNow($twoNinetyNineSecondsInTheFuture);
        $this->timestamp($twoNinetyNineSecondsInTheFuture->timestamp);
        $this->nonce('2');
        $response = $request->response();
        $this->assertEquals(Nonce::count(), 2);

        

        $threeHundredSecondsInTheFuture = $this->epoch->copy()->add(300, 'seconds');
        Carbon::setTestNow($threeHundredSecondsInTheFuture);
        $this->timestamp($threeHundredSecondsInTheFuture->timestamp);
        $this->nonce('3');
        $response = $request->response();
        $this->assertEquals(Nonce::count(), 3);

        // dd(Nonce::all()->toArray());

        $threeHundredAndOneSecondsInTheFuture = $this->epoch->copy()->add(301, 'seconds');
        Carbon::setTestNow($threeHundredAndOneSecondsInTheFuture);
        $this->timestamp($threeHundredAndOneSecondsInTheFuture->timestamp);
        $this->nonce('4');
        $response = $request->response();
        $this->assertEquals(Nonce::count(), 3);
        
        
        // dd(Nonce::count());
    }
}
