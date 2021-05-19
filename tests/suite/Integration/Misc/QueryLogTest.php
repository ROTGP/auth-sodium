<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

class QueryLogTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
        
        config(['authsodium.prune_nonces_after_request' => false]);
    }

    public function test_that_signed_request_to_resource_protected_by_named_middleware_produces_expected_query_log()
    {
        DB::enableQueryLog();
        DB::flushQueryLog();

        $response = $this->signed()->request()->response();
        $this->assertSuccessfulRequest($response);

        $logs = DB::getQueryLog();

        $log = $logs[0];
        $this->assertEquals('select * from "users" where "email" = ? limit 1', $log['query']);
        $this->assertEquals('dawn83@yahoo.com', $log['bindings'][0]);
        
        $log = $logs[1];
        $this->assertEquals('select * from "authsodium_nonces" where "user_id" = ? and "value" = ? limit 1', $log['query']);
        $this->assertEquals(1, $log['bindings'][0]);
        $this->assertEquals(1, $log['bindings'][1]);

        $log = $logs[2];
        $this->assertEquals('select * from "authsodium_throttles" where "user_id" = ? and "ip_address" = ? limit 1', $log['query']);
        $this->assertEquals(1, $log['bindings'][0]);
        $this->assertEquals('127.0.0.1', $log['bindings'][1]);
        
        $log = $logs[3];
        $this->assertEquals('insert into "authsodium_nonces" ("user_id", "value", "timestamp") values (?, ?, ?)', $log['query']);
        $this->assertEquals(1, $log['bindings'][0]);
        $this->assertEquals(1, $log['bindings'][1]);
        $this->assertEquals(1616007320000, $log['bindings'][2]);
        
        $log = $logs[4];
        $this->assertEquals('select * from "foos"', $log['query']);
        
        DB::disableQueryLog();
    }
}
