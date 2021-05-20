<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;
use ROTGP\AuthSodium\Models\Nonce;

use Carbon\Carbon;

class PruneAfterTerminateTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');

        config([
            'authsodium.prune.after_request' => false,
            'authsodium.prune.on_terminate' => true,
        ]);
    }

    public function test_that_nonces_are_pruned_on_application_termination_when_making_requests_over_time()
    {
        $request = $this->signed()->request();
        $this->nonce('nonce_1');
        $response = $request->response();

        $this->assertEquals(Nonce::count(), 1);
        $this->assertEquals(
            Nonce::pluck('value')->toArray(),
            ['nonce_1']
        );

        $fastForward = $this->epoch->copy()->add(299999, 'milliseconds');
        $this->setTestNow($fastForward);
        $this->nonce('nonce_2');
        $response = $request->response();
        $this->assertSuccessfulRequest($response);

        $this->assertEquals(Nonce::count(), 2);
        $this->assertEquals(
            Nonce::pluck('value')->toArray(),
            ['nonce_1', 'nonce_2']
        );

        $fastForward = $this->epoch->copy()->add(300000, 'milliseconds');
        $this->setTestNow($fastForward);
        $this->nonce('nonce_3');
        $response = $request->response();
        $this->assertSuccessfulRequest($response);

        $this->assertEquals(Nonce::count(), 3);
        $this->assertEquals(
            Nonce::pluck('value')->toArray(),
            ['nonce_1', 'nonce_2', 'nonce_3']
        );

        $fastForward = $this->epoch->copy()->add(300001, 'milliseconds');
        $this->setTestNow($fastForward);
        $this->nonce('nonce_4');
        $response = $request->response();
        $this->assertSuccessfulRequest($response);

        $this->assertEquals(Nonce::count(), 3);
        $this->assertEquals(
            Nonce::pluck('value')->toArray(),
            ['nonce_2', 'nonce_3', 'nonce_4']
        );

        $fastForward = $this->epoch->copy()->add(600000, 'milliseconds');
        $this->setTestNow($fastForward);
        $this->nonce('nonce_5');
        $response = $request->response();
        $this->assertSuccessfulRequest($response);

        $this->assertEquals(Nonce::count(), 3);
        $this->assertEquals(
            Nonce::pluck('value')->toArray(),
            ['nonce_3', 'nonce_4', 'nonce_5']
        );

        $fastForward = $this->epoch->copy()->add(1000000, 'milliseconds');
        $this->setTestNow($fastForward);
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
