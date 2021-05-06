<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

use Carbon\Carbon;
use ROTGP\AuthSodium\Facades\AuthSodium;

class CustomTimezoneTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');

        config(['app.timezone' => 'Asia/Bangkok']);
    }

    public function test_that_signed_requests_to_an_app_with_bangkok_time_succeed()
    {
        $request = $this->signed()->request();
        $response = $request->response();
        $this->assertSuccessfulRequest($response);
    }
}
