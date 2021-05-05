<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

use Carbon\Carbon;

class CustomGlueTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
        
        config(['authsodium.glue' => '::']);
    }

    public function test_that_signed_request_without_custom_glue_fails()
    {
        $response = $this->signed()->request()->response();
        $this->assertUnauthorized($response);
    }

    public function test_that_signed_request_with_custom_glue_succeeds()
    {
        $response = $this->signed()->glue('::')->request()->response();
        $this->assertSuccessfulRequest($response);
    }
}
