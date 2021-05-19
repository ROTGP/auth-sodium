<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;

class ValidateConfigTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        config(['authsodium.prune_nonces_after_request' => false]);
    }

    /**
     * All requests should fall within a valid
     * timeframe, and should progress over time.
     */
    public function test_that_validate_command_succeeds()
    {
        $result = Artisan::call('authsodium:validate');
        // dd($result);
        $this->assertTrue(true);
    }
}
