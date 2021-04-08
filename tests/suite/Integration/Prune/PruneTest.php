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
        $this->nonce('nonce_1');
        $response = $request->response();

        $this->assertEquals(Nonce::count(), 1);
        $this->assertEquals(
            Nonce::pluck('value')->toArray(),
            ['nonce_1']
        );

        $fastForward = $this->epoch->copy()->add(299, 'seconds');
        Carbon::setTestNow($fastForward);
        $this->timestamp($fastForward->timestamp);
        $this->nonce('nonce_2');
        $response = $request->response();
        $this->assertSuccessfulRequest($response);

        $this->assertEquals(Nonce::count(), 2);
        $this->assertEquals(
            Nonce::pluck('value')->toArray(),
            ['nonce_1', 'nonce_2']
        );

        $fastForward = $this->epoch->copy()->add(300, 'seconds');
        Carbon::setTestNow($fastForward);
        $this->timestamp($fastForward->timestamp);
        $this->nonce('nonce_3');
        $response = $request->response();
        $this->assertSuccessfulRequest($response);

        $this->assertEquals(Nonce::count(), 3);
        $this->assertEquals(
            Nonce::pluck('value')->toArray(),
            ['nonce_1', 'nonce_2', 'nonce_3']
        );

        $fastForward = $this->epoch->copy()->add(301, 'seconds');
        Carbon::setTestNow($fastForward);
        $this->timestamp($fastForward->timestamp);
        $this->nonce('nonce_4');
        $response = $request->response();
        $this->assertSuccessfulRequest($response);

        $this->assertEquals(Nonce::count(), 3);
        $this->assertEquals(
            Nonce::pluck('value')->toArray(),
            ['nonce_2', 'nonce_3', 'nonce_4']
        );

        $fastForward = $this->epoch->copy()->add(600, 'seconds');
        Carbon::setTestNow($fastForward);
        $this->timestamp($fastForward->timestamp);
        $this->nonce('nonce_5');
        $response = $request->response();
        $this->assertSuccessfulRequest($response);

        $this->assertEquals(Nonce::count(), 3);
        $this->assertEquals(
            Nonce::pluck('value')->toArray(),
            ['nonce_3', 'nonce_4', 'nonce_5']
        );

        $fastForward = $this->epoch->copy()->add(1000, 'seconds');
        Carbon::setTestNow($fastForward);
        $this->timestamp($fastForward->timestamp);
        $this->nonce('nonce_6');
        $response = $request->response();
        $this->assertSuccessfulRequest($response);

        $this->assertEquals(Nonce::count(), 1);
        $this->assertEquals(
            Nonce::pluck('value')->toArray(),
            ['nonce_6']
        );
    }
}
